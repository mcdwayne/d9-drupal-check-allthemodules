<?php

namespace Drupal\entity_form_monitor\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Entity Form Monitor settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a \Drupal\entity_form_monitor\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_form_monitor_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_form_monitor.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_form_monitor.settings');

    $form['entities'] = [
      '#type' => 'select',
      '#title' => $this->t('Entities to monitor'),
      '#description' => $this->t('Selecting none will apply form monitoring to all possible entity types'),
      '#options' => $this->getEntityOptions(),
      '#default_value' => $config->get('entities'),
      '#multiple' => TRUE,
      '#size' => 10,
      '#attributes' => [
        'data-placeholder' => $this->t('All possible entities'),
      ],
    ];

    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval between monitoring checks'),
      '#default_value' => $config->get('interval'),
      '#min' => 0,
      '#size' => 5,
      '#field_suffix' => $this->t('seconds'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('entity_form_monitor.settings')
      ->set('entities', $values['entities'])
      ->set('interval', $values['interval'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Build a list of entity and bundle options for a select field.
   *
   * @return array
   *   An array of bundle labels, keyed using entity-type-id:bundle, and
   *   grouped by the entity type label.
   */
  private function getEntityOptions() {
    $options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && is_subclass_of($entity_type->getClass(), EntityChangedInterface::class)) {
        $entity_group = (string) $entity_type->getLabel();
        $options[$entity_group] = [];
        if ($bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_id)) {
          foreach ($bundles as $bundle_id => $bundle) {
            $options[$entity_group][$entity_id . ':' . $bundle_id] = (string) $bundle['label'];
          }
          natcasesort($options[$entity_group]);
        }
      }
    }
    ksort($options);
    return $options;
  }

}
