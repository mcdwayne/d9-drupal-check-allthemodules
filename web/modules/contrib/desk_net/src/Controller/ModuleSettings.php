<?php

/**
 * @file
 * The custom methods for work with Drupal 8 DB.
 */

namespace Drupal\desk_net\Controller;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

class ModuleSettings extends ControllerBase {
  // Desk-Net Rest Service.
  const DN_BASE_URL = 'https://desk-net.com';

  public function getStatuses(RouteMatchInterface $route_match, Request $request) {
    return array(
      '#markup' => t('Desk-Net Generate New Credentials'),
    );
  }

  /**
   * The checking Desk-Net user credentials in Drupal.
   *
   * @return bool
   *   The result of checking Desk-Net user credentials in Drupal.
   */
  public static function checkUserCredentials() {
    if (ModuleSettings::variableGet('desk_net_login') == NULL
        || ModuleSettings::variableGet('desk_net_password') == NULL
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Getting custom Desk-Net variables.
   *
   * @param string $key
   *   The variable key.
   *
   * @return bool|array|string|int
   *   The result of getting Desk-Net variables.
   */
  public static function variableGet($key) {
    $q = \Drupal::database()->select('desk_net_variable', 'v');
    $q->fields('v', ['value']);
    $q->condition('v.name', $key);
    $value = $q->execute()->fetchField();
    if ($value) {
      return unserialize($value);
    }

    return NULL;
  }

  /**
   * Setting custom Desk-Net variables.
   *
   * @param string $key
   *   The variable key.
   * @param string|array|int|bool $value
   *   The variable value.
   *
   * @return bool
   *   The result of setting Desk-Net variables.
   */
  public static function variableSet($key, $value) {
    $variable = \Drupal::database()->merge('desk_net_variable')
      ->key(array('`name`' => $key))
      ->insertFields(array(
        '`name`' => $key,
        '`value`' => serialize($value),
      ))
      ->updateFields(array(
        '`value`' => serialize($value),
      ))
      ->execute();

    if ($variable) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Performs delete custom Desk-Net variables.
   *
   * @param string $key
   *   The variable key.
   *
   * @return bool
   *   The result of deleting Desk-Net variables.
   */
  public static function variableDel($key) {
    $value = \Drupal::database()->delete('desk_net_variable')
     ->condition('name', $key)
     ->execute();

    if ($value) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Getting value Desk-Net revision fields.
   *
   * @param object $node
   *   The entity object.
   *
   * @return bool|string
   *   The result of loading Desk-Net revision.
   */
  public static function deskNetRevisionGet($node) {
    // Getting hash field name.
    $hash_field_name = ModuleSettings::variableGet('desk_net_' . $node->bundle() . '_revision');

    // If field name with Desk-Net revision data was not found.
    if ($hash_field_name === NULL) {
      return FALSE;
    }
    // If field with Desk-Net data was not found.
    if (!($node->__isset($hash_field_name))) {
      return FALSE;
    }

    // Getting Desk-Net revision data.
    $desk_net_revision = $node->get($hash_field_name)->value;

    if ($desk_net_revision == NULL) {
      return NULL;
    }

    try {
      // Json decode.
      $result = json_decode($desk_net_revision, TRUE);
    } catch (\Exception $e) {
      \Drupal::logger('Get field Desk-Net Revision')->warning($e->getMessage());

      return FALSE;
    }

    return $result;
  }

  /**
   * Setting value Desk-Net revision fields.
   *
   * @param object $node
   *   The entity object.
   * @param array $data
   *   The new data for updating.
   *
   * @return bool|object
   *   The result of loading Desk-Net revision.
   */
  public static function deskNetRevisionSet($node, array $data) {
    // Getting hash field name.
    $hash_field_name = ModuleSettings::variableGet('desk_net_' . $node->bundle() . '_revision');
    // If field name with Desk-Net revision data was not found.
    if ($hash_field_name === NULL) {
      return FALSE;
    }

    try {
      // Json encode.
      $result = json_encode($data);
    } catch (\Exception $e) {
      \Drupal::logger('Set field Desk-Net Revision')->warning($e->getMessage());

      return FALSE;
    }

    // Updating Desk-Net revision data.
    $node = $node->set($hash_field_name, (string) $result);

    return $node;
  }

  /**
   * Perform create text field to node.
   *
   * @param array $story_data
   *   The old data from DN.
   * @param object $entity
   *   The new node data.
   * @param string $content_type
   *   The element content type.
   *
   * @return array|bool|string
   *   The result of updating JSON with Desk-Net data, before send to Desk-Net.
   */
  public static function updateDataBeforeSendToDN(array $story_data, $entity, $content_type) {
    global $base_url;
    // Getting Desk-Net revision data.
    $desk_net_revision = ModuleSettings::deskNetRevisionGet($entity);
    // Default category value: 'No Category'.
    $selected_category = NULL;
    // Default category if not found revision.
    $revision_category = NULL;
    // Set publication position.
    $publication_position = 0;
    // Get publication position from publication list.
    if ($desk_net_revision['desk_net_publications_id'] !== NULL) {
      $publications_id = $desk_net_revision['desk_net_publications_id'];

      foreach ((array) $story_data['publications'] as $key => $value) {
        if ($story_data['publications'][$key]['id'] == $publications_id) {
          $publication_position = $key;
          break;

        }
      }
    }

    // Updating Slug value.
    $story_data = ActionController::sendSlugByCMS($entity, $story_data);
    // Get entity id.
    $entity_id = $entity->id();
    // Loading node by id.
    $entity = Node::load($entity_id);
    // Update elements.
    $story_data['publications'][$publication_position]['url_to_published_content'] = "$base_url/node/$entity_id";
    $story_data['publications'][$publication_position]['url_to_content_in_cms'] = "$base_url/node/$entity_id/edit";

    // Loading penultimate revision data, before change entity.
    $penultimate_node_revision = node_revision_load((int)$entity->vid->value - 1);

    // Updating publish date.
    if ($entity->status->value == 1 || $entity->publish_on->value !== NULL) {
      // Getting current date.
      $current_date = \Drupal::time()->getCurrentTime();

      if ($entity->publish_on->value > $current_date) {
        $publish_date = $entity->publish_on->value;
      } else {
        $publish_date = $current_date;
      }

      if (!empty($story_data['publications'][$publication_position]['recurring'])) {
        $story_data['publications'][$publication_position]['recurring']['start'] =
          ModuleSettings::getDateFromUtc('Y-m-d', $publish_date);
        if (ModuleSettings::getDateFromUtc('H:i', $publish_date) != '23:59') {
          $story_data['publications'][$publication_position]['recurring']['time'] =
            ModuleSettings::getDateFromUtc('H:i', $publish_date);
        }
      } else {
        $story_data['publications'][$publication_position]['single']['start']['date'] =
          ModuleSettings::getDateFromUtc('Y-m-d', $publish_date);
        if (ModuleSettings::getDateFromUtc('H:i', $publish_date) != '23:59') {
          $story_data['publications'][$publication_position]['single']['start']['time'] =
            ModuleSettings::getDateFromUtc('H:i', $publish_date);
        }
      }
    }
    // Checking status changes.
    if ($penultimate_node_revision == NULL
        || $penultimate_node_revision->status->value != $entity->status->value
        || ($entity->__isset('publish_on') && $entity->publish_on->value !== NULL)
        || ($entity->__isset('publish_on') && !empty($penultimate_node_revision->publish_on->value) && empty($entity->publish_on->value))
    ) {
      // Loading status by Status Matching.
      $status_id = ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_' . $entity->status->value);

      // Checking Schedule publishing.
      if ($entity->__isset('publish_on') && $entity->publish_on->value !== NULL) {
        $status_id = ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_1');
      }

      // Checking Status Matching on default value after install module.
      if ($status_id !== NULL && $status_id != 0) {
        $story_data['publications'][$publication_position]['status'] = (int) $status_id;
      }
      else {
        $story_data['publications'][$publication_position]['status'] = 1;
      }
      // Update task status.
      if (!empty($story_data['tasks'][0]['status'])) {
        $story_data['tasks'][0]['status'] = 1;
      }
    }

    // Updating author.
    $user_data = \Drupal\user\Entity\User::load($entity->getOwnerId());
    $user = 'anonymous';

    // Checking user on anonymity.
    if ($user_data === NULL || $user_data->uid->value == '0') {
      $account = \Drupal::currentUser();

      if ($account->id() == 1) {
        $user = $account;
      }
    } else {
      $user = $user_data;
    }

    // Getting hash field name.
    $hash_author_field_name = ModuleSettings::variableGet('desk_net_author_id');

    if ($user !== 'anonymous' && $user_data->__isset($hash_author_field_name) && $user_data->get($hash_author_field_name)->value !== NULL) {
      $story_data['tasks'][0]['user'] = intval($user_data->get($hash_author_field_name)->value);
    }

    // Setting active Category ID.
    if (isset($entity->field_channel) && !empty($entity->field_channel->referencedEntities())) {
      $selected_category = $entity->field_channel->referencedEntities();
      // Loading penultimate revision category, before change entity.
      $revision_category = $penultimate_node_revision->field_channel->referencedEntities();
    }
    // Checking update category.
    if ($penultimate_node_revision == NULL || $selected_category != $revision_category) {
      // Updating category value.
      if (!empty($selected_category)) {
        $get_select_category_id_to_desk_net = ModuleSettings::variableGet(
          'desk_net_category_drupal_to_desk_net_' . $selected_category[0]->tid->value);
        if (!empty($get_select_category_id_to_desk_net) &&
            $get_select_category_id_to_desk_net != 'do_not_import' &&
            $get_select_category_id_to_desk_net != 'no_category'
        ) {
          unset($story_data['publications'][$publication_position]['platform']);
          $story_data['publications'][$publication_position]['category'] = (int) $get_select_category_id_to_desk_net;
        }
        elseif ($get_select_category_id_to_desk_net == 'do_not_import') {
          return 'do_not_import';
        }
        else {
          unset($story_data['publications'][$publication_position]['category']);
          $story_data['publications'][$publication_position]['platform'] =
            ModuleSettings::variableGet('desk_net_platform_id');
        }
      }
      else {
        unset($story_data['publications'][$publication_position]['category']);
        $story_data['publications'][$publication_position]['platform'] =
          ModuleSettings::variableGet('desk_net_platform_id');
      }
    }

    return $story_data;
  }

  /**
   * Get the publication Time from article Timestamp.
   *
   * @param string $format_output
   *   The time format.
   * @param int $time_stamp
   *   The article timestamp.
   *
   * @return string
   *   The publication time in UTC.
   */
  public static function getDateFromUtc($format_output, $time_stamp) {
    $date = new \DateTime();
    $date->setTimestamp($time_stamp);
    $date->setTimezone(new \DateTimeZone('UTC'));
    $time_in_utc = $date->format($format_output);

    return $time_in_utc;
  }

  /**
   * Perform update activate and deactivate status list.
   *
   * Update active statuses by triggersExport.
   *
   * @param array $status_list
   *   The default status list.
   *
   * @return array
   *   The refreshed status list.
   */
  public static function checkTriggersExportStatus(array $status_list) {
    if (empty($status_list['deactivatedStatuses'])) {
      $status_list['deactivatedStatuses'] = array();
    }
    foreach ($status_list['activeStatuses'] as $key => $value) {
      if ($status_list['activeStatuses'][$key]['triggersExport'] == FALSE) {
        array_unshift($status_list['deactivatedStatuses'],
          $status_list['activeStatuses'][$key]);
        unset($status_list['activeStatuses'][$key]);
      }
    }
    return $status_list;
  }

  /**
   * Adding new text fields to articles.
   *
   * @param string $content_type
   *   The content-type name.
   */
  public static function createCustomFields($content_type) {
    ModuleSettings::createCustomField('desk_net_' . $content_type . '_revision', $content_type, 'node');
  }

  /**
   * Adding new text field to node.
   *
   * @param string $field_name
   *   The field name.
   * @param string $bundle
   *   The field type.
   * @param string $entity_type
   *   The entity type.
   */
  public static function createCustomField($field_name, $bundle = 'article', $entity_type = 'node') {
    if (ModuleSettings::variableGet($field_name) === NULL) {
      // Generating unique name.
      $hash_name = 'dn_' . substr(md5($field_name), 0, 16);
      // Creating/updating field matching.
      ModuleSettings::variableSet($field_name, $hash_name);

      if (FieldStorageConfig::loadByName($entity_type, $hash_name) == NULL) {
        $field_storage = FieldStorageConfig::create(array(
          'field_name' => $hash_name,
          'entity_type' => $entity_type,
          'type' => 'text',
          'settings' => array('max_length' => 1024),
        ));
        $field_storage->save();

        $field = FieldConfig::create([
          'field_name' => $hash_name,
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'label' => $hash_name,
        ]);
        $field->save();
      }
    }
  }
}
