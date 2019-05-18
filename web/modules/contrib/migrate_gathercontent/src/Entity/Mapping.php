<?php
namespace Drupal\migrate_gathercontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\migrate_gathercontent\MappingInterface;

/**
* Defines the Example entity.
*
* @ConfigEntityType(
*   id = "gathercontent_mapping",
*   label = @Translation("GatherContent Mapping"),
*   handlers = {
*     "list_builder" = "Drupal\migrate_gathercontent\Controller\MappingListBuilder",
*     "form" = {
*       "default" = "Drupal\migrate_gathercontent\Form\MappingAddForm",
*       "edit" = "Drupal\migrate_gathercontent\Form\MappingEditForm",
*       "delete" = "Drupal\migrate_gathercontent\Form\MappingDeleteForm"
*     }
*   },
*   admin_permission = "administer site configuration",
*   entity_keys = {
*     "id" = "mapping_id",
 *    "label" = "label",
 *    "project_id" = "project_id",
 *    "template" = "template",
 *    "entity_type" = "entity_type",
 *    "bundle" = "bundle"
*   },
*   links = {
*     "edit-form" = "/admin/config/services/gatherocntent/mappings/manage/{mapping_id}/edit",
*     "delete-form" = "/admin/config/services/gatherocntent/mappings/manage/{mapping_id}/delete"
*   },
*   config_export = {
*     "mapping_id",
*     "label",
 *    "group_id",
 *    "project_id",
*     "template",
*     "entity_type",
*     "bundle",
*     "status",
*     "field_mappings",
*   },
* )
*/
class Mapping extends ConfigEntityBase implements MappingInterface {

  /**
  * The ID.
  *
  * @var string
  */
  protected $mapping_id;

  /**
   * The Label.
   *
   * @var string
   */
   protected $label;

  /**
   * The mapping group id.
   *
   * @var string
   */
  protected $group_id;

  /**
   * The gathercontent project.
   *
   * @var string
   */
  protected $project_id;

  /**
   * The gathercontent template.
   *
   * @var string
   */
   protected $template;

  /**
   * The entity type.
   *
   * @var string
   */
   protected $entity_type;

  /**
   * The entity bundle.
   *
   * @var string
   */
   protected $bundle;

  /**
   * The status, enabled or disabled.
   *
   * @var boolean
   */
  protected $status;

  /**
   * The mapped fields
   *
   * @var array
   */
  protected $field_mappings = [];

  /**
   * The group object this mapping is associated with.
   *
   * @var \Drupal\migrate_gathercontent\Entity\Group
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->mapping_id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    if (!$this->group) {
      $this->group = \Drupal::entityTypeManager()->getStorage('gathercontent_group')->load($this->group_id);
    }
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappings() {
    return $this->field_mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return ($this->status);
  }

  /**
   * Returns the migration id.
   */
  /**
   * {@inheritdoc}
   */
  public function getMigrationId() {
    return 'gathercontent_item:' . $this->mapping_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingDependencies() {
    $migrations = [];
    if (!empty($this->field_mappings)) {
      foreach ($this->field_mappings as $id => $mapping) {
        $migrations[$id] = \Drupal::entityTypeManager()->getStorage('gathercontent_mapping')->load($id);
      }
    }

    return $migrations;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('config', $this->getGroup()->getConfigDependencyName());
    return $this;
  }

}