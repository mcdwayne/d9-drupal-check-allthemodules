<?php

/**
 * @file
 * Functions needed for refresh elements list(statuses, categories, oauth2 clients).
 */

namespace Drupal\desk_net;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\desk_net\Controller\ModuleSettings;
use Drupal\desk_net\Controller\RequestsController;
use Drupal\desk_net\Collection\NoticesCollection;

class DeleteMethods extends ControllerBase {

  /**
   * Perform shape list of deleted items.
   *
   * @param array $new_items_list
   *   The new element list.
   * @param array $save_items_list
   *   The save element list in Drupal DB.
   * @param array $drupal_items_list
   *   The basic Drupal elements for Node.
   *   array[key] string
   *   The key has ID or Slug element value.
   * @param string $type
   *   The type elements in list.
   */
  public static function shapeDeletedItems(array $new_items_list, array $save_items_list, array $drupal_items_list, $type) {
    $delete_element_list = array();

    if (!empty($save_items_list)) {
      foreach ($save_items_list as $id => $value) {
        $in_stock = FALSE;
        foreach ($new_items_list as $key => $content) {
          if ($save_items_list[$id]["id"] == $new_items_list[$key]["id"]) {
            $in_stock = TRUE;
          }
        }

        if (!$in_stock) {
          array_push($delete_element_list, $save_items_list[$id]['id']);
        }
      }
    }

    if (!empty($delete_element_list)) {
      $delete_element_list = DeleteMethods::checkSubItems($delete_element_list, $save_items_list);
      DeleteMethods::deleteItems($delete_element_list, $drupal_items_list, $type);
    }
  }

  /**
   * Perform checking parent elements in the list of elements to be deleted.
   *
   * @param array $delete_element_list
   *   The element for delete from saved list.
   * @param array $save_items_list
   *   The save element list in Drupal DB.
   *
   * @return array
   *   The list with deleting elements.
   */
  private static function checkSubItems(array $delete_element_list, array $save_items_list) {
    $under_sub_category = array();

    if (!empty($save_items_list)) {
      foreach ($save_items_list as $key => $item) {
        foreach ($delete_element_list as $value) {
          if (isset($save_items_list[$key]["category"]) && $value == $save_items_list[$key]["category"]) {
            array_push($delete_element_list, $save_items_list[$key]['id']);
            array_push($under_sub_category, $save_items_list[$key]['id']);
          }
        }
      }
    }

    if (!empty($under_sub_category)) {
      return DeleteMethods::checkSubItems($under_sub_category, $save_items_list);
    }
    else {
      return $delete_element_list;
    }
  }

  /**
   * Perform deleted items and mapping category.
   *
   * @param array $delete_element_list
   *   The element for delete from saved list.
   * @param array $drupal_items_list
   *   The basic Drupal elements for Node.
   * @param string $type
   *   The type elements in list.
   */
  private static function deleteItems(array $delete_element_list, array $drupal_items_list, $type) {
    if (!empty($drupal_items_list) && !empty($delete_element_list)) {
      foreach ($delete_element_list as $content) {
        foreach ($drupal_items_list as $value) {
          if ($content == ModuleSettings::variableGet('desk_net_' . $type . '_drupal_to_desk_net_' . $value)) {
            ModuleSettings::variableSet('desk_net_' . $type . '_drupal_to_desk_net_' . $value, 'no_' . $type);
          }
        }
        ModuleSettings::variableDel('desk_net_' . $type . '_desk_net_to_drupal_' . $content);
      }
    }
  }

  /**
   * Implements clearing Rest configuration for Desk-Net module.
   */
  public static function deleteRestConfiguration() {
    // Delete oauth2 clients and server.
    if (\Drupal::moduleHandler()->moduleExists('oauth2_server') !== FALSE) {
      DeleteMethods::deleteOauth2Server('oauth2_server', 'desk_net_module');
    }
  }

  /**
   * Performs Delete custom Desk-Net variables.
   *
   * @param string $entity
   *   The entity.
   * @param string $entity_id
   *   The variable key.
   */
  private static function deleteOauth2Server($entity, $entity_id) {
    $oauth2_server_entity = \Drupal::entityTypeManager()->getStorage($entity)->load($entity_id);
    if ($oauth2_server_entity) {
      $oauth2_server_entity->delete();
    }
  }

  /**
   * Deleting all Desk-Net fields for Content-types.
   */
  public static function deleteDeskNetFields() {
    $load_content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    foreach ($load_content_types as $content_type) {
      DeleteMethods::deleteCustomField('desk_net_' . $content_type->id() . '_revision', 'node');
    }
    // Delete Desk-Net user id.
    DeleteMethods::deleteCustomField(ModuleSettings::variableGet('desk_net_author_id'), 'user', 'by_name');
  }

  /**
   * Deleting Desk-Net module fields.
   *
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param string $delete_by
   *   The type deleting element.
   */
  private static function deleteCustomField($field_name, $entity_type = 'node', $delete_by = 'by_hash') {
    switch ($delete_by) {
      case 'by_name':
        if (FieldStorageConfig::loadByName($entity_type, $field_name) !== NULL) {
          FieldStorageConfig::loadByName($entity_type, $field_name)->delete();
        }
        break;

      default:
        // Getting hash name for field.
        $hash_field = ModuleSettings::variableGet($field_name);
        // Deleting matching field.
        ModuleSettings::variableDel($field_name);

        // Deleting field.
        if ($hash_field !== NULL && FieldStorageConfig::loadByName($entity_type, $hash_field) !== NULL) {
          FieldStorageConfig::loadByName($entity_type, $hash_field)->delete();
        }
    }
  }

  /**
   * Deleting node in Thunder/Drupal 8.
   *
   * @param EntityInterface $entity
   *   The node that is being deleted.
   *
   * @return bool
   *   The result of deleting article in Drupal.
   */
  public function deleteElement(EntityInterface $entity) {
    // Getting Desk-Net revision data.
    $desk_net_revision = ModuleSettings::deskNetRevisionGet($entity);

    if (isset($desk_net_revision['desk_net_story_id'])) {
      // Check Deleted/Removed Status.
      if (!empty($desk_net_revision['desk_net_removed_status']) && $desk_net_revision['desk_net_removed_status'] === 'desk_net_removed') {
        return FALSE;
      }
      // Check exist story on Desk-Net side.
      $story_data = json_decode((new RequestsController())->get(ModuleSettings::DN_BASE_URL, 'elements', $desk_net_revision['desk_net_story_id']), TRUE);
      if ($story_data === 'not_show_new_notice' || empty($story_data) || !empty($story_data['message'])) {
        return FALSE;
      }

      if (isset($desk_net_revision['desk_net_publications_id']) && !empty($desk_net_revision['desk_net_publications_id'])) {
        if (ModuleSettings::checkUserCredentials()) {
          (new RequestsController())->customRequest('DELETE', array(), ModuleSettings::DN_BASE_URL, 'elements/publication', $desk_net_revision['desk_net_publications_id']);
        }
        else {
          drupal_set_message(NoticesCollection::getNotice(5), 'error');
        }
      }
    }
  }
}