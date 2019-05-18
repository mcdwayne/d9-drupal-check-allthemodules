<?php

namespace Drupal\entity_ui\Plugin;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\entity_ui\Entity\EntityTabInterface;

/**
 * Defines an interface for Entity tab content plugins.
 */
interface EntityTabContentInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Sets the entity tab on the plugin.
   *
   * @param \Drupal\entity_ui\Entity\EntityTabInterface
   *  The entity tab entity.
   */
  public function setEntityTab(EntityTabInterface $entity_tab);

  /**
   * Determines whether the plugin can be used with the given entity type.
   *
   * This should purely concern itself with applicability: whether the entity
   * type supports what this plugin does.
   *
   * Plugins can specify the 'entity_types' annotation property to define the
   * entity types they can work with, or override this method for dynamic
   * handling and not use the annotation.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface
   *  The entity type to check.
   * @param array $definition
   *  The plugin definition.
   *
   * @return bool
   *  TRUE if the plugin can be used with the entity type, FALSE if not.
   */
  public static function appliesToEntityType(EntityTypeInterface $entity_type, $definition);

  /**
   * Provides suggested values for a new entity tab that uses this plugin.
   *
   * These are prepopulated in the form to create a new entity tab.
   *
   * @param array $definition
   *  The plugin definition.
   *
   * @return array
   *  An array of values for the new entity tab entity. The 'path' value will
   *  be removed if it clashes with an existing route.
   */
  public static function suggestedEntityTabValues($definition);

  /**
   * Checks access to use the entity tab this plugin is for.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity that the entity tab is on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user session for which to check access, or NULL to check
   *   access for the current user. Defaults to NULL.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(EntityInterface $target_entity, AccountInterface $account = NULL);

  /**
   * Builds the content for the entity tab.
   *
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity that the entity tab is on.
   *
   * @return
   *   A render array.
   */
  public function buildContent(EntityInterface $target_entity);

  /**
   * Defines the permissions for the tab that owns the plugin instance.
   *
   * @return array
   *   An array of permissions, in the same format as a dynamic permissions
   *   callback.
   */
  public function getPermissions();

}
