<?php

namespace Drupal\entity_toolbar\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Entity Toolbar Config entity.
 *
 * @ConfigEntityType(
 *   id = "entity_toolbar",
 *   label = @Translation("Entity Toolbar"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\entity_toolbar\EntityToolbarConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\entity_toolbar\Form\EntityToolbarConfigForm",
 *       "edit" = "Drupal\entity_toolbar\Form\EntityToolbarConfigForm",
 *       "delete" = "Drupal\entity_toolbar\Form\EntityToolbarConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity_toolbar\Routing\EntityToolbarConfigRouteProvider",
 *     },
 *   },
 *   config_prefix = "type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "uuid",
 *     "id",
 *     "label",
 *     "status",
 *     "bundleEntityId",
 *     "baseRouteName",
 *     "addRouteName",
 *     "addRouteLinkText",
 *     "noGroup",
 *     "weight"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/entity_toolbar/{entity_toolbar}",
 *     "add-form" = "/admin/config/content/entity_toolbar/add",
 *     "edit-form" = "/admin/config/content/entity_toolbar/{entity_toolbar}/edit",
 *     "delete-form" = "/admin/config/content/entity_toolbar/{entity_toolbar}/delete",
 *     "collection" = "/admin/config/content/entity_toolbar"
 *   }
 * )
 */
class EntityToolbarConfig extends ConfigEntityBase {

  /**
   * The Entity Toolbar ID, also the target entity type.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity Toolbar label.
   *
   * @var string
   */
  protected $label;

  /**
   * Base route name from which to pull menu links.
   *
   * @var string
   */
  protected $baseRouteName;

  /**
   * Route name for "add another'.
   *
   * @var string
   */
  protected $addRouteName;

  /**
   * Link text for "add another'.
   *
   * @var string
   */
  protected $addRouteLinkText;

  /**
   * Entity ID for the bundle entity.
   *
   * @var int
   */
  protected $bundleEntityId;

  /**
   * Whether this toolbar is enabled or not.
   *
   * @var bool
   */
  protected $status;

  /**
   * Do not group by first letter.
   *
   * @var bool
   */
  protected $noGroup;

  /**
   * Weight of this toolbar.
   *
   * @var int
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $this->addDependency('module', 'entity_toolbar');

    if ($bundleEntity = $this->entityTypeManager()->getDefinition($this->get('bundleEntityId'))) {
      $provider = $bundleEntity->getProvider();
      $this->addDependency('module', $provider);
    }

    return $this;
  }

}
