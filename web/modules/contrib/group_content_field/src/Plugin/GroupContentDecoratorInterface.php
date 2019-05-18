<?php
/**
 * Created by PhpStorm.
 * User: valerij
 * Date: 07.04.17
 * Time: 13:37
 */

namespace Drupal\group_content_field\Plugin;

/**
 * Interface GroupContentDecoratorInterface
 *
 * @package Drupal\group_content_field
 */
interface GroupContentDecoratorInterface {
  function getPluginId();
  function getLabel();

  /**
   * Get default values.
   *
   * @return array
   *   Array with gids.
   */
  function getDefaultValues($parent_entity);

  /**
   * Method which assign selected content to group.
   */
  function createMemberContent($parent_entity, $add_gid);
  /**
   * Method which unassign selected content to group.
   */
  function removeMemberContent($parent_entity, $delete_gid);

  /**
   * Additional plugin spec field settings.
   */
  function fieldStorageSettings();

  function getBuildProperties($parent_entity);

}
