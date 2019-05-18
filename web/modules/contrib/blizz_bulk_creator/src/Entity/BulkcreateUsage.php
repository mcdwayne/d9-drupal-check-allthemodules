<?php

namespace Drupal\blizz_bulk_creator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Class BulkcreateUsage.
 *
 * Defines the "bulkcreate_usage" entity. The entity stores
 * information about a bulkcreation usage.
 *
 * @package Drupal\blizz_bulk_creator\Entity
 *
 * @ConfigEntityType(
 *   id = "bulkcreate_usage",
 *   label = @Translation("Bulkcreate usage"),
 *   module = "blizz_bulk_creator",
 *   config_prefix = "bulkcreate_usage",
 *   admin_permission = "administer site configuration",
 *   translatable = FALSE,
 *   handlers = {
 *     "storage" = "Drupal\blizz_bulk_creator\EntityStorage\BulkcreateUsage",
 *     "list_builder" = "Drupal\blizz_bulk_creator\ListBuilder\BulkcreateUsage",
 *     "form" = {
 *       "add" = "Drupal\blizz_bulk_creator\Form\BulkcreateUsageFormStep1",
 *       "add-step2" = "Drupal\blizz_bulk_creator\Form\BulkcreateUsageFormStep2",
 *       "add-step3" = "Drupal\blizz_bulk_creator\Form\BulkcreateUsageFormStep3",
 *       "delete" = "Drupal\blizz_bulk_creator\Form\BulkcreateUsageDeleteForm"
 *     },
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/blizz-bulk-creator/usages/manage/{bulkcreate_usage}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "bulkcreate_configuration" = "bulkcreate_configuration",
 *     "entity_type_id" = "entity_type_id",
 *     "bundle" = "bundle",
 *     "target_field" = "target_field",
 *     "multi_stage" = "multi_stage"
 *   }
 * )
 */
class BulkcreateUsage extends ConfigEntityBase implements BulkcreateUsageInterface {

  /**
   * Custom service to ease the handling of media entities.
   *
   * @var \Drupal\blizz_bulk_creator\Services\EntityHelperInterface
   */
  protected $entityHelper;

  /**
   * Custom service to ease administrative tasks.
   *
   * @var \Drupal\blizz_bulk_creator\Services\BulkcreateAdministrationHelperInterface
   */
  protected $administrationHelper;

  /**
   * The bulkcreate configuration in use.
   *
   * @var \Drupal\blizz_bulk_creator\Entity\BulkcreateConfigurationInterface
   */
  protected $bulkcreateConfigurationObject;

  /**
   * BulkcreateUsage constructor.
   */
  public function __construct(array $values, $entity_type) {
    $this->entityHelper = \Drupal::service('blizz_bulk_creator.entity_helper');
    $this->administrationHelper = \Drupal::service('blizz_bulk_creator.administration_helper');
    $this->bulkcreateConfigurationObject = !empty($values['bulkcreate_configuration'])
      ? \Drupal::entityTypeManager()->getStorage('bulkcreate_configuration')->load($values['bulkcreate_configuration'])
      : NULL;
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getBulkcreateConfiguration() {
    return $this->bulkcreateConfigurationObject;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {

    // Let parent sort out it's things first.
    parent::calculateDependencies();

    // We need to flag our own config files to be absolutely dependant
    // upon our module, so they get removed upon deinstallation.
    $this->addDependency('enforced', ['module' => ['blizz_bulk_creator']]);

    // Load the definition of the target entity.
    $targetEntityTypeDefinition = $this->entityTypeManager()->getDefinition($this->entity_type_id);
    $bundleDependencyData = $targetEntityTypeDefinition->getBundleConfigDependency($this->bundle);
    $bundleFields = $this->entityHelper->getBundleFields($this->entity_type_id, $this->bundle);

    $dependencies = [
      'module' => [$targetEntityTypeDefinition->getProvider()],
      'config' => [
        "blizz_bulk_creator.bulkcreate_configuration.{$this->bulkcreate_configuration}",
        $bundleDependencyData['name'],
      ],
    ];

    // Determine the target information.
    $targetStages = $this->administrationHelper->getStructuredBulkcreateTargetFieldArray(
      $this->entity_type_id,
      $this->bundle,
      $this->target_field
    );

    // Add each stage field definition as a dependency.
    foreach ($targetStages as $stage) {
      $dependencies['config'][] = $stage->fieldDefinition->getConfigDependencyName();
    }

    // Remove duplicates and beautify dependencies.
    $dependencies = array_map(
      function ($set) {
        $set = array_unique($set);
        sort($set);
        return $set;
      },
      $dependencies
    );

    // Set the dependencies.
    $this->dependencies = $dependencies;

    // Enforce a dependency on this module to ensure
    // config cleanup upon deinstallation.
    $this->dependencies['enforced'] = ['module' => ['blizz_bulk_creator']];

    return $this;
  }

  /**
   * The machine name of this usage.
   *
   * @var string
   */
  protected $id;

  /**
   * The id of the bulkcreate configuration to use.
   *
   * @var string
   */
  protected $bulkcreate_configuration;

  /**
   * The human readable name of this usage.
   *
   * @var string
   */
  protected $label;

  /**
   * The machine name of the target entity type.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The machine name of the bundle of the target entity type.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The machine name of the target field holding the media entity references.
   *
   * @var string
   */
  protected $target_field;

  /**
   * The stage delta that gets multi-instantiated when using this bulkcreation.
   *
   * @var int
   */
  protected $multi_stage;

}
