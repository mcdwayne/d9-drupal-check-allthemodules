<?php

namespace Drupal\trailing_slash\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trailing_slash\Helper\Settings\TrailingSlashSettingsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm
 *
 * @package Drupal\trailing_slash\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface|ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param EntityTypeBundleInfoInterface                                     $entity_type_bundle_info
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($config_factory);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.bundle.info')
    );
  }



  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'trailing_slash.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trailing_slash_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('trailing_slash.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('enabled')
    ];

    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of paths with trailing slash'),
      '#description' => $this->t("Write a path per line where you want a trailing slash. Paths start with slash. (e.g., '/book')"),
      '#default_value' => $config->get('paths'),
    ];

    $form['enabled_entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types'),
      '#description' => $this->t('Enable to add a trailing slash for the given type.'),
      '#tree' => TRUE,
    ];

    $entity_types = TrailingSlashSettingsHelper::getContentEntityTypes();
    $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
    $enabled_entity_types = unserialize($config->get('enabled_entity_types'));
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $entity_type_bundles = $bundle_info[$entity_type_id];
      $form['enabled_entity_types'][$entity_type_id] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $entity_type->getLabel(),
        '#tree' => TRUE,
      ];
      foreach ($entity_type_bundles as $bundle_id => $bundle) {
        $form['enabled_entity_types'][$entity_type_id][$bundle_id] = [
          '#type' => 'checkbox',
          '#title' => $bundle['label'],
          '#default_value' => $enabled_entity_types[$entity_type_id][$bundle_id],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('trailing_slash.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('paths', $form_state->getValue('paths'))
      ->set('enabled_entity_types', serialize($form_state->getValue('enabled_entity_types')))
      ->save();
  }

}
