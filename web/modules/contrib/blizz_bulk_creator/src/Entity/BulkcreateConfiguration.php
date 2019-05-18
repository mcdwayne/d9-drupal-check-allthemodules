<?php

namespace Drupal\blizz_bulk_creator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class BulkcreateConfiguration.
 *
 * Defines the "bulkcreate_configuration" entity. The entity stores
 * information about a bulkcreation configuration, e.g. which
 * media entity bundle should be generated and which fields get
 * filled with default values.
 *
 * @package Drupal\blizz_bulk_creator\Entity
 *
 * @ConfigEntityType(
 *   id = "bulkcreate_configuration",
 *   label = @Translation("Bulkcreate configuration"),
 *   module = "blizz_bulk_creator",
 *   config_prefix = "bulkcreate_configuration",
 *   admin_permission = "administer site configuration",
 *   translatable = FALSE,
 *   handlers = {
 *     "storage" = "Drupal\blizz_bulk_creator\EntityStorage\BulkcreateConfiguration",
 *     "list_builder" = "Drupal\blizz_bulk_creator\ListBuilder\BulkcreateConfiguration",
 *     "form" = {
 *       "default" = "Drupal\blizz_bulk_creator\Form\BulkcreateConfigurationEditForm",
 *       "add" = "Drupal\blizz_bulk_creator\Form\BulkcreateConfigurationFormStep1",
 *       "add-step2" = "Drupal\blizz_bulk_creator\Form\BulkcreateConfigurationFormStep2",
 *       "delete" = "Drupal\blizz_bulk_creator\Form\BulkcreateConfigurationDeleteForm"
 *     },
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/blizz-bulk-creator/manage/{bulkcreate_configuration}",
 *     "delete-form" = "/admin/structure/blizz-bulk-creator/manage/{bulkcreate_configuration}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "custom_entity_name" = "custom_entity_name",
 *     "target_bundle" = "target_bundle",
 *     "bulkcreate_field" = "bulkcreate_field",
 *     "default_values" = "default_values"
 *   }
 * )
 */
class BulkcreateConfiguration extends ConfigEntityBase implements BulkcreateConfigurationInterface {

  /**
   * Custom service to ease the handling of media entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * BulkcreateConfiguration constructor.
   */
  public function __construct(array $values, $entity_type) {
    $this->entityHelper = \Drupal::service('blizz_bulk_creator.entity_helper');
    parent::__construct($values, $entity_type);
  }

  /**
   * The machine name of this configuration.
   *
   * @var string
   */
  protected $id;

  /**
   * The human readable name of this configuration.
   *
   * @var string
   */
  protected $label;

  /**
   * Is custom naming enabled for bulk generated entities?
   *
   * @var bool
   */
  protected $custom_entity_name;

  /**
   * The bundle machine name of the media entity target bundle.
   *
   * @var string
   */
  protected $target_bundle;

  /**
   * The machine name of the bulkcreate field (unique to all entities).
   *
   * @var string
   */
  protected $bulkcreate_field;

  /**
   * An array of field names carrying default values.
   *
   * @var string[]
   */
  protected $default_values;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {

    // Let parent sort out it's things first.
    parent::calculateDependencies();

    // Add a dependency to the media_entity module.
    $this->addDependency('module', 'media_entity');

    // Add a dependency to the appropriate media bundle.
    $this->addDependency('config', "media_entity.bundle.{$this->target_bundle}");

    // Get the field definitions in order to properly
    // set field config dependencies.
    $bundleFields = $this->entityHelper->getBundleFields('media', $this->target_bundle);

    // Add the field dependencies.
    $this->addDependency('config', $bundleFields[$this->bulkcreate_field]->getConfigDependencyName());
    foreach ($this->default_values as $default_value_field) {
      $this->addDependency('config', $bundleFields[$default_value_field]->getConfigDependencyName());
    }

    // Enforce a dependency on this module to ensure
    // config cleanup upon deinstallation.
    $this->dependencies['enforced'] = ['module' => ['blizz_bulk_creator']];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultPropertyFields() {

    // Get the field definitions of the target bundle.
    $targetMediaEntityBundleFields = $this->entityHelper->getBundleFields(
      'media',
      $this->get('target_bundle')
    );

    // Return the bundle field definitions if they are
    // configured to be included.
    return array_filter(
      $targetMediaEntityBundleFields,
      function ($field_machine_name) {
        return in_array($field_machine_name, $this->get('default_values'));
      },
      ARRAY_FILTER_USE_KEY
    );

  }

}
