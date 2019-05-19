<?php

namespace Drupal\video_sitemap\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VideoSitemapSettingsForm.
 *
 * @package Drupal\video_sitemap\Form
 */
class VideoSitemapSettingsForm extends ConfigFormBase {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Video location plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $locationManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * VideoSitemapSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $location_manager
   *   Video location plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, PluginManagerInterface $location_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->locationManager = $location_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.video_sitemap.video_location'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['video_sitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_sitemap.settings');
    $user_input = $form_state->getUserInput();

    $form['cron_generate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate the video sitemap during cron runs'),
      '#description' => $this->t('Uncheck this if you intend to only regenerate the video sitemaps manually.'),
      '#default_value' => $config->get('cron_generate', TRUE),
    ];

    $form['cron_generate_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Sitemap generation interval'),
      '#options' => [
        0 => $this->t('On every cron run'),
        1 => $this->t('Once an hour'),
        3 => $this->t('Once every @hours hours', ['@hours' => 3]),
        6 => $this->t('Once every @hours hours', ['@hours' => 6]),
        12 => $this->t('Once every @hours hours', ['@hours' => 12]),
        24 => $this->t('Once a day'),
        48 => $this->t('Once every @days days', ['@days' => 48 / 24]),
        72 => $this->t('Once every @days days', ['@days' => 72 / 24]),
        96 => $this->t('Once every @days days', ['@days' => 96 / 24]),
        120 => $this->t('Once every @days days', ['@days' => 120 / 24]),
        144 => $this->t('Once every @days days', ['@days' => 144 / 24]),
        168 => $this->t('Once a week'),
      ],
      '#description' => $this->t('The minimum amount of time that will elapse before the sitemap is regenerated.'),
      '#default_value' => $config->get('cron_generate_interval'),
    ];

    $bundle_options = [];
    $media_bundles = $this->entityTypeBundleInfo->getBundleInfo('media');
    foreach ($media_bundles as $machine_name => $bundle) {
      $bundle_options[$machine_name] = $bundle['label'];
    }
    $form['media_video_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Media video bundle used in the video sitemap'),
      '#options' => $bundle_options,
      '#description' => $this->t('Select Media video bundle used in the video sitemap.'),
      '#default_value' => $config->get('media_video_bundle'),
      '#ajax' => ['callback' => '::ajaxDependentHandler'],
      '#required' => TRUE,
    ];
    $form['bundle_dependent'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'bundle-dependent'],
    ];
    $selected = isset($user_input['media_video_bundle']) ? $user_input['media_video_bundle'] : $config->get('media_video_bundle');
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('media', $selected);
    $description_options = [];
    $allowed = [
      'string',
      'text_long',
      'text_with_summary',
      'string_long',
    ];
    foreach ($field_definitions as $field_name => $field) {
      if ($field instanceof BaseFieldDefinition && $field_name !== 'name') {
        continue;
      }
      if (!in_array($field->getType(), $allowed)) {
        continue;
      }
      $description_options[$field_name] = $field->getLabel();
    }
    $form['bundle_dependent']['video_description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Video Description field'),
      '#default_value' => $config->get('video_description_field'),
      '#options' => $description_options,
      '#description' => $this->t('Select video description field.'),
      '#required' => TRUE,
    ];
    $plugins = $this->locationManager->getDefinitions();
    $plugin_options = [];
    foreach ($plugins as $plugin_id => $definition) {
      $plugin_options[$plugin_id] = $definition['title'];
    }
    $form['video_location_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Video location plugin'),
      '#default_value' => $config->get('video_location_plugin'),
      '#options' => $plugin_options,
      '#description' => $this->t('Select video location plugin. This depends on the source used for the video.'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback triggered by the media bundle select element.
   */
  public function ajaxDependentHandler(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#bundle-dependent', $form['bundle_dependent']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('video_sitemap.settings')
      ->set('cron_generate', $values['cron_generate'])
      ->set('cron_generate_interval', $values['cron_generate_interval'])
      ->set('media_video_bundle', $values['media_video_bundle'])
      ->set('video_location_plugin', $values['video_location_plugin'])
      ->set('video_description_field', $values['video_description_field']);

    $this->config('video_sitemap.settings')->save();

    parent::submitForm($form, $form_state);
  }

}
