<?php

namespace Drupal\nexx_integration\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Class SettingsForm.
 *
 * Defines a form that configures nexx video settings.
 *
 * @package Drupal\nexx_integration\Form
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\entity_embed\EntityEmbedDisplay\EntityEmbedDisplayManager
   */
  protected $displayPluginManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    parent::__construct($config_factory);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nexx_integration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $values = $form_state->getValues();
    $settings = $this->config('nexx_integration.settings');

    $form['notification_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification settings'),
    ];

    $form['notification_settings']['intro'][] = [
      '#markup' => $this->t('Register a new account at <a href=":nexx_url" target="_blank">http://www.nexx.tv/thunder</a> and get a domain ID and an installation code.',
        [':nexx_url' => 'http://www.nexx.tv/thunder']),
    ];

    $api_url = !empty($values['nexx_api_url']) ? $values['nexx_api_url'] : $settings->get('nexx_api_url');
    $form['notification_settings']['nexx_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Url'),
      '#description' => $this->t('The notification endpoint of nexx.tv. The default value does usually not have to be changed.'),
      '#default_value' => $api_url,
    ];

    $api_key = !empty($values['nexx_api_authkey']) ? $values['nexx_api_authkey'] : $settings->get('nexx_api_authkey');
    $form['notification_settings']['nexx_api_authkey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Installation Code'),
      '#description' => $this->t('The installation code you get, when you registered for nexx. In nexx omnia this is also called "API Key (THOR)"'),
      '#default_value' => $api_key,
    ];

    $omnia_id = !empty($values['omnia_id']) ? $values['omnia_id'] : $settings->get('omnia_id');
    $form['notification_settings']['omnia_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain ID'),
      '#description' => $this->t('The unique identifier of the site, given by nexx.'),
      '#default_value' => $omnia_id,
    ];

    $api_secret = !empty($values['api_secret']) ? $values['api_secret'] : $settings->get('api_secret');
    $form['notification_settings']['api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API secret'),
      '#description' => $this->t('API secret key, given by nexx.'),
      '#default_value' => $api_secret,
    ];

    $bigger_thumbnail = !empty($values['bigger_thumbnail']) ? $values['bigger_thumbnail'] : $settings->get('bigger_thumbnail');
    $form['notification_settings']['bigger_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow bigger thumbnail'),
      '#description' => $this->t('Allow to download bigger (x2, x3) thumbnail.'),
      '#default_value' => $bigger_thumbnail,
    ];

    // Add the embed type plugin settings.
    $form['type_settings'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Bundle settings'),
      '#prefix' => '<div id="nexx-type-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    $entity_type = $this->entityManager->getDefinition('media');
    $bundle = !empty($values['type_settings']['video_bundle']) ? $values['type_settings']['video_bundle'] : $settings->get('video_bundle');
    $form['type_settings']['video_bundle'] = [
      '#type' => 'select',
      '#title' => $entity_type->getBundleLabel() ?: $this->t('Bundles'),
      '#options' => $this->getEntityBundleOptions($entity_type),
      '#default_value' => $bundle,
      '#description' => $this->t('The bundle which is used for videos.'),
    ];
    $form['type_settings']['bundles']['#access'] = !empty($form['type_settings']['bundles']['#options']);

    $form['notification_settings']['notification_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NEXX notification access key'),
      '#default_value' => $settings->get('notification_access_key'),
      '#size' => 25,
    ];
    // Add a submit handler function for the key generation.
    $form['notification_settings']['create_key'][] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate new random key'),
      '#submit' => ['::generateRandomKey'],
      // No validation at all is required in the equivocate case, so
      // we include this here to make it skip the form-level validator.
      '#validate' => [],
    ];
    $form['notification_settings']['info'][] = [
      '#markup' => '<p>' . $this->t('The current value to provide in omnia domain settings for the video endpoint is:<br><strong>:endpoint</strong>',
        [
          ':endpoint' => $base_url . Url::fromRoute('nexx_integration.omnia_notification_gateway')->toString() . '?token=' . $settings->get('notification_access_key'),
        ]
      ) . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler for the random key generation.
   *
   * This only fires when the 'Generate new random key' button is clicked.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function generateRandomKey(array &$form, FormStateInterface $form_state) {
    $config = $this->config('nexx_integration.settings');
    $config->set('notification_access_key', substr(md5(rand()), 0, 20));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('nexx_integration.settings')
      ->set('video_bundle', $values['type_settings']['video_bundle'])
      ->set('nexx_api_url', $values['nexx_api_url'])
      ->set('nexx_api_authkey', $values['nexx_api_authkey'])
      ->set('omnia_id', $values['omnia_id'])
      ->set('api_secret', $values['api_secret'])
      ->set('notification_access_key', $values['notification_access_key'])
      ->set('bigger_thumbnail', $values['bigger_thumbnail'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nexx_integration.settings',
    ];
  }

  /**
   * Builds a list of entity type options.
   *
   * @return array
   *   An array of entity type labels, keyed by entity type name.
   */
  protected function getEntityTypeOptions() {
    $options = $this->entityManager->getEntityTypeLabels(TRUE);

    foreach ($options as $group => $group_types) {
      foreach (array_keys($group_types) as $entity_type_id) {
        // Filter out entity types that do not have a view builder class.
        if (!$this->entityManager->getDefinition($entity_type_id)->hasViewBuilderClass()) {
          unset($options[$group][$entity_type_id]);
        }
      }
    }

    return $options;
  }

  /**
   * Ajax callback to update the form fields which depend on embed type.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated options for the embed type.
   */
  public function updateTypeSettings(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Update options for entity type bundles.
    $response->addCommand(new ReplaceCommand(
      '#nexx-type-settings-wrapper',
      $form['type_settings']
    ));

    return $response;
  }

  /**
   * Builds a list of entity type bundle options.
   *
   * Configuration entity types without a view builder are filtered out while
   * all other entity types are kept.
   *
   * @return array
   *   An array of bundle labels, keyed by bundle name.
   */
  protected function getEntityBundleOptions(EntityTypeInterface $entity_type) {
    $bundle_options = [];
    // If the entity has bundles, allow option to restrict to bundle(s).
    if ($entity_type->hasKey('bundle')) {
      foreach ($this->entityManager->getBundleInfo($entity_type->id()) as $bundle_id => $bundle_info) {
        $bundle_options[$bundle_id] = $bundle_info['label'];
      }
      natsort($bundle_options);
    }
    return $bundle_options;
  }

}
