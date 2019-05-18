<?php

namespace Drupal\config_selector\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;

/**
 * Defines the Configuration Selector feature configuration entity.
 *
 * @todo the edit/add/delete forms are missing but the plumbing is left in to
 *   make adding at a later date simple.
 *
 * @ConfigEntityType(
 *   id = "config_selector_feature",
 *   label = @Translation("Configuration selector feature"),
 *   handlers = {
 *     "access" = "Drupal\config_selector\Access\FeatureAccessControlHandler",
 *     "list_builder" = "Drupal\config_selector\FeatureListBuilder",
 *     "form" = {
 *       "default" = "Drupal\config_selector\Form\FeatureManageForm",
 *       "add" = "Drupal\config_selector\Form\FeatureAddForm",
 *       "edit" = "Drupal\config_selector\Form\FeatureEditForm",
 *       "delete" = "Drupal\config_selector\Form\FeatureDeleteForm"
 *     }
 *   },
 *   config_prefix = "feature",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "manage" = "/admin/structure/config_selector/manage/{config_selector_feature}",
 *     "delete-form" = "/admin/structure/config_selector/manage/{config_selector_feature}/delete",
 *     "edit-form" = "/admin/structure/config_selector/manage/{config_selector_feature}/edit",
 *     "collection" = "/admin/structure/config_selector",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   }
 * )
 */
class Feature extends ConfigEntityBase implements FeatureInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the feature.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = [];

    foreach ($this->entityTypeManager()->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ConfigEntityTypeInterface) {
        $result = $this->getConfigurationByType($entity_type->id());
        if (!empty($result)) {
          $configuration[$entity_type->id()] = $result;
        }
      }
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationByType($entity_type_id) {
    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);

    $result = $entity_storage
      ->getQuery()
      ->condition('third_party_settings.config_selector.feature', $this->id())
      ->execute();

    if (!empty($result)) {
      // Convert the result to entities.
      $result = $entity_storage->loadMultiple($result);
    }
    return $result;
  }

}
