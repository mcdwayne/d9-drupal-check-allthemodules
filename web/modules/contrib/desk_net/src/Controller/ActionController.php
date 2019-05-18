<?php

/**
 * @file
 * The actions wrappers for UPDATE/INSERT article in Drupal.
 */

namespace Drupal\desk_net\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\DeleteMethods;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ActionController extends ControllerBase {

  /**
   * Perform send node params to Desk-Net after insert node.
   *
   * @param object $entity
   *   The node data.
   * @param string $save_publish_date
   *   Trigger for update fields schedule publish_on.
   */
  public static function createNodeDrupalToDN($entity, $save_publish_date = 'save') {
    $response = ActionController::sendNodeParams($entity);

    switch ($response) {
      case 'do_not_import':
      case 'update_module_data':
      case 'not_show_new_notice':
        break;

      case 'unauthorized':
        drupal_set_message(NoticesCollection::getNotice(5), 'error');
        break;

      case FALSE:
        drupal_set_message(NoticesCollection::getNotice(6), 'error');
        break;

      case 'platform_schedule':
        drupal_set_message(NoticesCollection::getNotice(11), 'error');
        break;

      default:
        // Loading node by id.
        $entity = Node::load($entity->id());
        // Default Desk-Net revision data.
        $desk_net_revision = array(
          'desk_net_story_id' => NULL,
          'desk_net_status_name' => NULL,
          'desk_net_publications_id' => NULL,
          'desk_net_removed_status' => NULL,
          'desk_net_description' => NULL,
          'desk_net_content_change_status' => NULL,
          'desk_net_category_id' => NULL,
          'desk_net_status_id' => NULL,
        );
        // Updating Desk-Net revision fields.
        $desk_net_revision['desk_net_story_id'] = $response['id'];
        $desk_net_revision['desk_net_description'] = html_entity_decode(mb_convert_encoding($response['title'], 'UTF-8'));
        $desk_net_revision['desk_net_publications_id'] = $response['publications'][0]['id'];
        $desk_net_revision['desk_net_status_name'] = $response['publication']['status']['name'];
        $desk_net_revision['desk_net_status_id'] = $response['publications'][0]['status'];
        // Checking category on exist.
        if (isset($response['publications'][0]['category'])) {
          $desk_net_revision['desk_net_category_id'] = $response['publications'][0]['category'];
        } else {
          $desk_net_revision['desk_net_category_id'] = 'no_category';
        }
        $entity = ModuleSettings::deskNetRevisionSet($entity, $desk_net_revision);

        // Update date.
        if ($entity->publish_on->value !== NULL && $save_publish_date === 'save') {
          if (!empty($response['publications'][0]['single'])) {
            $entity->set('publish_on', ActionController::generateTimeString(
              $response['publications'][0]['single']['start']['time'],
              $response['publications'][0]['single']['start']['date']));
          }
          elseif (!empty($response['publications'][0]['recurring'])) {
            $entity->set('publish_on', ActionController::generateTimeString(
              $response['publications'][0]['recurring']['start'],
              $response['publications'][0]['recurring']['time']));
          }
        }

        // Update fields.
        $entity->setNewRevision(TRUE);
        $entity->save();

        drupal_set_message(NoticesCollection::getNotice(1), 'status');
    }
  }

  /**
   * Perform send node params to Desk-Net.
   *
   * @param object $entity
   *   The node data.
   *
   * @return array|bool
   *   The result of sending node params to Desk-Net.
   */
  private static function sendNodeParams($entity) {
    global $base_url;

    $publish_date = NULL;

    $platform_id = ModuleSettings::variableGet('desk_net_platform_id');
    // Loading node by id.
    $entity = Node::load($entity->id());

    if (!empty($platform_id) && !empty(ModuleSettings::variableGet('desk_net_token'))) {
      $entity_data['title'] = $entity->title->value;
      // Updating Slug value.
      $entity_data = ActionController::sendSlugByCMS($entity, $entity_data);

      $publication_position = 0;
      $entity_id = $entity->id();
      // Updating publication type.
      $content_type_matching = ModuleSettings::variableGet('desk_net_types_drupal_to_desk_net_' . $entity->bundle());

      if ($content_type_matching !== NULL && $content_type_matching !== 'no_type') {
        $entity_data['publications'][$publication_position]['type'] = (int) $content_type_matching;
      }

      $entity_data['publications'][$publication_position]['assignments'] = [TRUE];
      $entity_data['publications'][$publication_position]['url_to_published_content'] = "$base_url/node/$entity_id";
      $entity_data['publications'][$publication_position]['url_to_content_in_cms'] = "$base_url/node/$entity_id/edit";

      // Updating field date in Desk-Net story.
      if ($entity->status->value == 1 || ($entity->__isset('publish_on') && $entity->publish_on->value !== NULL)) {
        // Getting current date.
        $current_date = \Drupal::time()->getCurrentTime();

        if ($entity->publish_on->value > $current_date) {
          $publish_date = $entity->publish_on->value;
        } else {
          $publish_date = $current_date;
        }

        $entity_data['publications'][$publication_position]['single']['start']['date'] =
          ModuleSettings::getDateFromUtc('Y-m-d', $publish_date);
        if (ModuleSettings::getDateFromUtc('H:i', $publish_date) != '23:59') {
          $entity_data['publications'][$publication_position]['single']['start']['time'] =
            ModuleSettings::getDateFromUtc('H:i', $publish_date);
        }
      }

      $entity_data['publications'][$publication_position]['platform'] = $platform_id;

      // Setting author.
      $user = User::load($entity->getOwnerId());

      // Getting hash field name.
      $hash_author_field_name = ModuleSettings::variableGet('desk_net_author_id');

      if (!empty($user->mail->value) && !empty($user->name->value)) {
        if (isset($user->get($hash_author_field_name)->value) &&
            !empty($user->get($hash_author_field_name)->value)
        ) {
          $entity_data['tasks'][0]['user'] = intval($user->get($hash_author_field_name)->value);
        }
        else {
          $entity_data['tasks'][0]['user']['name'] = $user->name->value;
          $entity_data['tasks'][0]['user']['email'] = $user->mail->value;
        }
      }

      $entity_data['tasks'][0]['format'] = 18;
      $entity_data['tasks'][0]['confirmationStatus'] = -2;

      $entity_data['publications'][$publication_position]['cms_id'] = $entity_id;

      // Getting Desk-Net Status.
      $status_id = ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_' . $entity->status->value);

      if ($entity->__isset('publish_on') && $entity->publish_on->value !== NULL) {
        $status_id = ModuleSettings::variableGet('desk_net_status_drupal_to_desk_net_1');
      }

      if (!empty($status_id) && $status_id !== 0) {
        $entity_data['publications'][$publication_position]['status'] = (int) $status_id;

        $entity_data['tasks'][0]['status'] = 1;
      }
      else {
        $entity_data['tasks'][0]['status'] = 1;
        $entity_data['publications'][$publication_position]['status'] = 1;
      }

      // Selecting active Category ID.
      if ($entity->__isset('field_channel')) {
        $select_list_category = $entity->referencedEntities();

        if (count($select_list_category) > 1) {
          $select_list_category[0] = array_pop($select_list_category);
        }

        if (!empty($select_list_category)) {
          $get_select_category_id_to_desk_net = ModuleSettings::variableGet('desk_net_category_drupal_to_desk_net_' . $select_list_category[0]->tid->value);

          if (!empty($get_select_category_id_to_desk_net) && $get_select_category_id_to_desk_net != 'do_not_import' && $get_select_category_id_to_desk_net != 'no_category') {
            unset($entity_data['publications'][$publication_position]['platform']);
            $entity_data['publications'][$publication_position]['category'] = (int) $get_select_category_id_to_desk_net;
          }
          elseif ('do_not_import' == $get_select_category_id_to_desk_net) {
            return 'do_not_import';
          }
          else {
            unset($entity_data['publications'][$publication_position]['category']);
            $entity_data['publications'][$publication_position]['platform'] =
              ModuleSettings::variableGet('desk_net_platform_id');
          }
        }
        else {
          unset($entity_data['publications'][$publication_position]['category']);
          $entity_data['publications'][$publication_position]['platform'] =
            ModuleSettings::variableGet('desk_net_platform_id');
        }
      }

      // Create element on Desk-Net.
      $addition_post_info_from_desk_net = (new RequestsController())->customRequest('POST',
        $entity_data, ModuleSettings::DN_BASE_URL, 'elements');
      // Clearing double notification message.
      switch ($addition_post_info_from_desk_net) {
        case 'unauthorized':
          return 'unauthorized';

        case 'not_show_new_notice':
          return 'not_show_new_notice';

        case 'update_module_data':
          return 'update_module_data';

        case 'platform_schedule':
          return 'platform_schedule';

        case FALSE:
          return FALSE;
      }

      $addition_post_info_from_desk_net = json_decode(
        $addition_post_info_from_desk_net, TRUE);

      if (!empty($addition_post_info_from_desk_net['message'])) {
        return FALSE;
      }
      // Get Name Desk-Net status.
      $addition_post_info_from_desk_net['publication']['status']['name'] = ActionController::getStatusNameByID($status_id);

      return $addition_post_info_from_desk_net;
    }
  }

  /**
   * Perform get Desk-Net status name by ID.
   *
   * @param int $status_id
   *   The Desk-Net Status ID.
   *
   * @return string
   *   The result of getting Desk-Net status name by ID.
   */
  private static function getStatusNameByID($status_id) {
    $keys_status_field = [
      'desk_net_list_active_status',
      'desk_net_status_deactivate_status_list',
    ];

    foreach ($keys_status_field as $value) {
      $status_list = ModuleSettings::variableGet($value);
      if ($status_list !== NULL) {
        foreach ($status_list as $key => $status) {
          if ($status_list[$key]['id'] == $status_id) {
            return $status_list[$key]['name'];
          }
        }
      }
    }

    return t('No Status');
  }

  /**
   * Perform sending update node data to Desk-Net.
   *
   * @param object $entity
   *   The node data.
   * @param string $content_type
   *   The element content type.
   * @param string $save_publish_date
   *   Trigger for update fields schedule publish_on.
   *
   * @return bool
   *   The result of updating story in Desk-Net.
   */
  public static function updateNodeDrupalToDN($entity, $content_type, $save_publish_date = 'save') {
    // Getting Desk-Net revision data.
    $desk_net_revision = ModuleSettings::deskNetRevisionGet($entity);
    // Checking element on status "Delete".
    if (!empty($desk_net_revision['desk_net_removed_status']) && $desk_net_revision['desk_net_removed_status'] == 'desk_net_removed'
    ) {
      return FALSE;
    }

    if ($entity->id()) {
      // Get JSON with default value.
      $story_data = json_decode((new RequestsController())->get(ModuleSettings::DN_BASE_URL, 'elements', $desk_net_revision['desk_net_story_id']), TRUE);

      if ($story_data === 'not_show_new_notice' || empty($story_data)
          || !empty($story_data['message'])
          || empty($story_data['publications'])
      ) {
        if ($story_data !== FALSE) {
          drupal_set_message(NoticesCollection::getNotice(3), 'error');
        } else {
          drupal_set_message(NoticesCollection::getNotice(4), 'error');
        }
        return FALSE;
      }
      // Update story data before send to Desk-Net.
      $new_story_data = ModuleSettings::updateDataBeforeSendToDN($story_data, $entity, $content_type);
      // Update with error.
      if ($new_story_data == 'do_not_import') {
        return FALSE;
      }
      // Sending request to Desk-Net for update story.
      $status_update = (new RequestsController())->customRequest('PUT', $new_story_data,
        ModuleSettings::DN_BASE_URL, 'elements', $new_story_data['id'] );

      // Check request status on error message.
      switch($status_update) {
        case 'platform_schedule':
          drupal_set_message(NoticesCollection::getNotice(12), 'error');
          return FALSE;

        case 'unauthorized':
          drupal_set_message(NoticesCollection::getNotice(3), 'error');
          return FALSE;

        default:
          if (empty($status_update)) {
            drupal_set_message(NoticesCollection::getNotice(3), 'error');
            return FALSE;
          }

          if ($status_update !== 'not_show_new_notice') {
            drupal_set_message(NoticesCollection::getNotice(1), 'status');
          }
          // Convert response to array.
          $status_update = json_decode($status_update, TRUE);

          if (!empty($status_update['message'])) {
            drupal_set_message(NoticesCollection::getNotice(3), 'error');
            return FALSE;
          }
      }
      // Update field Desk-Net Status Name.
      $desk_net_revision['desk_net_status_name'] = ActionController::getStatusNameByID($status_update['publications'][0]['status']);
      // Updating Desk-Net status revision.
      if (isset($status_update['publications'][0]['status'])) {
        $desk_net_revision['desk_net_status_id'] = $status_update['publications'][0]['status'];
      }
      // Updating Desk-Net category revision.
      if (isset($status_update['publications'][0]['category']) && !empty($status_update['publications'][0]['category'])) {
        $desk_net_revision['desk_net_category_id'] = $status_update['publications'][0]['category'];
      } else {
        $desk_net_revision['desk_net_category_id'] = 'no_category';
      }

      $entity = ModuleSettings::deskNetRevisionSet($entity, $desk_net_revision);

      // Updating fields publish_on on new date.
      if ($entity->publish_on->value !== NULL && $save_publish_date === 'save') {
        if (!empty($status_update['publications'][0]['single'])) {
          $entity->set('publish_on', ActionController::generateTimeString(
            $status_update['publications'][0]['single']['start']['time'],
            $status_update['publications'][0]['single']['start']['date']));
        }
        elseif (!empty($status_update['publications'][0]['recurring'])) {
          $entity->set('publish_on', ActionController::generateTimeString(
            $status_update['publications'][0]['recurring']['start'],
            $status_update['publications'][0]['recurring']['time']));
        }
      } else {
        $entity->set('publish_on', NULL);
      }

      $entity->save();
    }
  }

  /**
   * Perform edit article data.
   *
   * @param array $data
   *   The post data.
   *
   * @return array|bool
   *   The result creating article in Drupal.
   */
  public static function createNode(array $data) {
    global $base_url;
    // Getting current user.
    $user = \Drupal::currentUser();

    // Setting value 'No type' if data from Desk-Net coming without type_id.
    if (empty($data['publication']['type']['id'])) {
      $data['publication']['type']['id'] = 'no_type';
    }
    // Getting value form content-type matching.
    $content_type = ModuleSettings::variableGet('desk_net_types_desk_net_to_drupal_' . $data['publication']['type']['id']);

    if ($content_type == NULL) {
      $content_type = 'article';
    }

    $node = Node::create(['type' => $content_type]);

    if (empty($data['description'])) {
      return FALSE;
    }
    // Adding creator.
    if (is_object($user) && isset($user->uid->value)) {
      $node->set('uid', $user->uid->value);
    }

    // Set author if it exists.
    if (!empty($data['tasks'])) {
      foreach ($data['tasks'] as $entity) {
        if (!empty($entity['format']['name']) && !empty($entity['assignee'])) {
          $user_id = ActionController::validUser($entity['assignee']['name'],
            $entity['assignee']['id']);
          if ($user_id !== FALSE) {
            $node->set('uid', $user_id);
            break;

          }
        }
      }
    }

    // Updating Status.
    if (isset($data['publication']['status']['id'])) {
      $drupal_status_matching = ModuleSettings::variableGet('desk_net_status_desk_net_to_drupal_' . $data['publication']['status']['id']);

      if ($drupal_status_matching !== NULL) {
        $node->set('status', $drupal_status_matching);
      } else {
        $node->set('status', 0);
      }
    }

    // Updating entity Title.
    if (isset($data['slug']) && !empty($data['slug'])) {
      $node = ActionController::setTitle($node, $data['slug']);
      // Updating node Alias.
      $node = ActionController::getSlugByAPI($node, $data['slug']);
    } else {
      $node = ActionController::setTitle($node, $data['description']);
      // Setting default value to the 'alias' field and off automatic generate URL alias.
      $node->set('path', ['pathauto' => FALSE, 'alias' => '']);
    }

    // Updating date.
    if ($node->__isset('publish_on')) {
      if (!empty($data['publication']['single'])) {
        $node->set('publish_on', ActionController::generateTimeString(
          $data['publication']['single']['start']['time'],
          $data['publication']['single']['start']['date']));
      }
      elseif (!empty($data['publication']['recurring'])) {
        $node->set('publish_on', ActionController::generateTimeString(
          $data['publication']['recurring']['start'],
          $data['publication']['recurring']['time']));
      }
    }

    // The default category value.
    $category_id = 'no_category';

    // Updating field tags in Drupal 8.
    if ($node->__isset('field_channel') && !empty($data['publication']['category']['id'])) {
      if (isset($data['publication']['category']['name']) &&
          !empty($data['publication']['category']['name'])
      ) {
        $category_id = $data['publication']['category']['id'];
      }
      if (ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_' . $category_id) == 'do_not_import'
      ) {
        return FALSE;
      }
      else {
        $taxonomy_id = ActionController::getActiveTaxonomyMapping(
          'desk_net_category_desk_net_to_drupal_', $category_id);
        if ($taxonomy_id != FALSE) {
          $node->set('field_channel', [$taxonomy_id]);
        }
      }
    }

    try {
      // Set Desk-Net revision data.
      $desk_net_revision = array(
        'desk_net_story_id' => $data['id'],
        'desk_net_status_name' => $data['publication']['status']['name'],
        'desk_net_publications_id' => $data['publication']['id'],
        'desk_net_removed_status' => NULL,
        'desk_net_description' => preg_replace('/[\r\n\t ]+/', ' ', $data['description']),
        'desk_net_content_change_status' => NULL,
        'desk_net_category_id' => $category_id,
        'desk_net_status_id' => $data['publication']['status']['id'],
      );

      $node = ModuleSettings::deskNetRevisionSet($node, $desk_net_revision);
      // Bad updating Desk-Net revision.
      if ($node === FALSE){
        return FALSE;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('Not_found_Desk-Net_custom_fields')->warning($e->getMessage());
    }

    $node->save();

    $nid = $node->id();

    $response_data = array(
      'id' => $nid,
      'cmsEditLink' => "$base_url/node/" . $nid ."/edit",
      'cmsOpenLink' => "$base_url/node/" . $nid,
    );

    return $response_data;
  }

  /**
   * Updating node Title in Drupal.
   *
   * @param object $node
   *   The node.
   * @param string $title
   *   The new Title value.
   *
   * @return object
   *   The node with updating Title.
   */
  public static function setTitle($node, $title) {
    $title = preg_replace('/[\r\n\t ]+/', ' ', $title);

    if (strlen($title) > 77) {
      if ($node->__isset('title')) {
        $node->set('title', mb_substr($title, 0, 77) . '...');
      }

      if ($node->__isset('field_seo_title')) {
        $node->set('field_seo_title', mb_substr($title, 0, 77) . '...');
      }
    }
    else {
      if ($node->__isset('title')) {
        $node->set('title', $title);
      }

      if ($node->__isset('field_seo_title')) {
        $node->set('field_seo_title', $title);
      }
    }

    return $node;
  }

  /**
   * Perform update node in Drupal by Desk-Net.
   *
   * @param array $data
   *   The node data.
   * @param int $node_id
   *   The node id for update.
   * @param array $desk_net_revision
   *   The Desk-Net revision data.
   *
   * @return array|bool
   *   The result updating article in Drupal from Desk-Net.
   */
  public static function updateNode(array $data, $node_id, array $desk_net_revision) {
    global $base_url;

    // Check Category mapping in Drupal.
    if (!empty($data['publication']['category']['id'])) {
      if ((ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_' . $data['publication']['category']['id']) == 'do_not_import')
          || (!isset($data['publication']['category']['name']) &&
              ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_no_category') == 'do_not_import')
      ) {
        return FALSE;
      }
    }

    // Loading node by id.
    $node = Node::load($node_id);

    // Updating node Status.
    if (isset($data['publication']['status']['id']) && $desk_net_revision['desk_net_status_id'] != $data['publication']['status']['id']) {
      $drupal_status_matching = ModuleSettings::variableGet('desk_net_status_desk_net_to_drupal_' . $data['publication']['status']['id']);

      if ($drupal_status_matching !== NULL) {
        $node->set('status', $drupal_status_matching);
      }
      else {
        $node->set('status', 0);
      }
    }

    // Checking channel fields.
    if ($node->__isset('field_channel') && $desk_net_revision['desk_net_category_id'] != $data['publication']['category']['id']) {
      // Delete old taxonomies value.
      if (!empty($node->field_channel->referencedEntities())) {
        unset($node->field_channel);
        $node->field_channel = array();
      }
      // Set default value category id.
      $desk_net_category_id = 'no_category';

      if (!empty(ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_' . $data['publication']['category']['id']))
          && ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_' . $data['publication']['category']['id']) != 'no_category' &&
          (isset($data['publication']['category']['name']) && !empty($data['publication']['category']['name']))
      ) {
        $node->set('field_channel', ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_' . $data['publication']['category']['id']));
        // Set Desk-Net category id.
        $desk_net_category_id = $data['publication']['category']['id'];
      }
      elseif (!isset($data['publication']['category']['name'])
              && ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_no_category') != NULL
              && ModuleSettings::variableGet('desk_net_category_desk_net_to_drupal_no_category') != 'no_category'
      ) {
        $node->set('field_channel', ModuleSettings::variableGet(
          'desk_net_category_desk_net_to_drupal_no_category'));
      }
      // Updating Desk-Net revision Category id.
      $desk_net_revision['desk_net_category_id'] = $desk_net_category_id;
    }

    // Updating Desk-Net description for Thunder element.
    if (!empty($data['description'])) {
      $desk_net_revision['desk_net_description'] = $data['description'];
    }
    // Set author if it exists.
    if (!empty($data['tasks'])) {

      foreach ($data['tasks'] as $entity) {

        if (!empty($entity['format']['name'])) {

          if (!empty($entity['assignee'])) {

            $user_id = ActionController::validUser($entity['assignee']['name'],
              $entity['assignee']['id']);

            if ($user_id !== FALSE) {
              $node->set('uid', $user_id);
              break;

            }
          }
        }
      }
    }

    // Updating node Alias.
    if (isset($data['slug']) && !empty($data['slug'])) {
      $node = ActionController::getSlugByAPI($node, $data['slug']);
    }

    try {
      // Update date.
      if (isset($data['publication']['single']) && !empty($data['publication']['single'])) {
        // Set default value.
        if (!isset($data['publication']['single']['start']['time'])) {
          $data['publication']['single']['start']['time'] = NULL;
        }

        $node->set('publish_on', ActionController::generateTimeString(
          $data['publication']['single']['start']['time'],
          $data['publication']['single']['start']['date']));
      }
      elseif (isset($data['publication']['single']) && !empty($data['publication']['recurring'])) {
        // Set default value.
        if (!isset($data['publication']['recurring']['time'])) {
          $data['publication']['recurring']['time'] = NULL;
        }

        $node->set('publish_on', ActionController::generateTimeString(
          $data['publication']['recurring']['start'],
          $data['publication']['recurring']['time']));
      }
    } catch (\Exception $e) {
      \Drupal::logger('Desk-Net API: Update publish option')->warning($e->getMessage());
    }

    // Updating Status field in block additional info.
    $desk_net_revision['desk_net_status_name'] = $data['publication']['status']['name'];
    // Updating Desk-Net revision Status id.
    $desk_net_revision['desk_net_status_id'] = $data['publication']['status']['id'];

    // Updating Desk-Net revision.
    $node = ModuleSettings::deskNetRevisionSet($node, $desk_net_revision);

    // Saving changed.
    $node->save();

    $nid = $node->id();

    $response_data = array(
      'id' => $nid,
      'cmsEditLink' => "$base_url/node/" . $nid ."/edit",
      'cmsOpenLink' => "$base_url/node/" . $nid,
    );

    return $response_data;
  }

  /**
   * The updating slug in CMS from Desk-Net.
   *
   * @param object $node
   *   The node object.
   * @param string $slug
   *   The Slug value.
   *
   * @return object
   *   Return updated node object.
   */
  public static function getSlugByAPI($node, $slug) {
    // Deleting line break and other special symbols.
    $slug = preg_replace('/[\r\n\t ]+/', ' ', $slug);
    // Getting Slug setting value.
    $slug_syncing = ModuleSettings::variableGet('desk_net_slug__slug_syncing');
    switch ($slug_syncing) {
      case 'title':
        if ($node->__isset('title')) {
          $node->set('title', $slug);
        }

        break;

      default:
        // Clearing string for use in URLs.
        $slug_url = \Drupal::service('pathauto.alias_cleaner')->cleanString($slug);

        // Updating Node alias and off automatic generate URL alias.
        $node->set('path', ['pathauto' => FALSE, 'alias' => '/' . $slug_url]);
    }

    // Always updating SEO TITLE if we got slug form Desk-Net.
    if ($node->__isset('field_seo_title')) {
      $node->set('field_seo_title', $slug);
    }

    return $node;
  }

  /**
   * The updating slug in Desk-Net from CMS.
   *
   * @param object $node
   *   The node object.
   * @param array $entity_data
   *   The story data.
   *
   * @return array
   *   Return updated story data.
   */
  public static function sendSlugByCMS($node, $entity_data) {
    // Getting Slug setting value.
    $slug_syncing = ModuleSettings::variableGet('desk_net_slug__slug_syncing');
    switch ($slug_syncing) {
      case 'title':
        $entity_data['slug'] = $node->title->value;

        break;

      default:
        $entity_data['slug'] = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $node->id());
        $entity_data['slug'] = ltrim($entity_data['slug'], '/');
    }

    return $entity_data;
  }


  /**
   * The generate string with publication Time.
   *
   * @param string $time
   *   The post time.
   * @param string $date
   *   The post date.
   *
   * @return string
   *   The publication time.
   */
  private static function generateTimeString($time, $date) {
    // Getting current date.
    $get_current_date = new \DateTime('now', new \DateTimeZone('UTC'));
    $current_date = $get_current_date->getTimestamp();

    if (empty($time)) {
      $time = '23:59:00';
    }
    if (empty($date)) {
      $date = new \DateTime('now', new \DateTimeZone('UTC'));
      $date = $date->format('Y-m-d');
    }
    $date = new \DateTime($date . ' ' . $time, new \DateTimeZone('UTC'));
    $desk_net_date = $date->getTimestamp();

    if ($desk_net_date > $current_date) {
      return (string) $date->getTimestamp();
    } else {
      return NULL;
    }
  }

  /**
   * Perform update/insert Active Category for article on Drupal.
   *
   * @param string $sending_path
   *   The path for send category ID.
   * @param string $category_id
   *   The Desk-Net category ID.
   *
   * @return object|bool
   *   The taxonomy id.
   */
  private static function getActiveTaxonomyMapping($sending_path, $category_id) {
    if (!empty(ModuleSettings::variableGet($sending_path . $category_id))) {
      $taxonomy_id = ModuleSettings::variableGet($sending_path . $category_id);
      if ($taxonomy_id && $taxonomy_id != 'no_category' && $taxonomy_id != 'do_not_import') {
        return $taxonomy_id;
      }
    }

    return FALSE;
  }

  /**
   * Perform validate user from Desk-Net by email.
   *
   * @param string $email
   *   The email Desk-Net user.
   * @param int $author_id
   *   The Desk-Net user ID.
   *
   * @return bool|int
   *   The Drupal user id.
   */
  private static function validUser($email, $author_id) {
    $user = user_load_by_mail($email);
    // Getting hash field name.
    $hash_field_name = ModuleSettings::variableGet('desk_net_author_id');

    // If field name with Desk-Net revision data was not found.
    if ($hash_field_name === NULL) {
      return FALSE;
    }

    if ($user !== FALSE && $user->__isset($hash_field_name)) {
      if ($user->get($hash_field_name)->value === NULL) {
        $user->set($hash_field_name, $author_id);
        $user->save();
      }

      $user_uid = $user->uid->value;

      return $user_uid;
    }

    return FALSE;
  }

  /**
   * Perform get Category list from Desk-Net.
   *
   * @return bool|array
   *   The result loading category list from Desk-Net.
   */
  public static function getCategory() {
    $platform_id = ModuleSettings::variableGet('desk_net_platform_id');

    $save_category_list_for_platform = ModuleSettings::variableGet('desk_net_category_list');

    if (!empty($platform_id) && !empty(ModuleSettings::variableGet('desk_net_token'))) {
      $category_list = json_decode((new RequestsController())->get(ModuleSettings::DN_BASE_URL,
        'categories/platform', $platform_id), TRUE);
      if (isset($category_list['message']) || $category_list === 'not_show_new_notice'
          || empty($category_list)) {
        return FALSE;
      }

      if (!empty($save_category_list_for_platform)) {
        $element_list_id = array();

        $drupal_category_list = \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();

        if (!empty($drupal_category_list['channel'])) {
          $vocabulary_term_list = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($drupal_category_list['channel']->id());

          if (!empty($vocabulary_term_list)) {
            foreach ($vocabulary_term_list as $term) {
              array_push($element_list_id, $term->tid);
            }
          }
        }
        DeleteMethods::shapeDeletedItems($category_list,
          $save_category_list_for_platform, $element_list_id, 'category');
      }

      ModuleSettings::variableSet('desk_net_category_list', $category_list);

      return $category_list;
    }

    return FALSE;
  }

  /**
   * Checking: exist content-type - in content-types synchronization list for synchronization with Desk-Net.
   *
   * @param string $content_type
   *   The content-type for validate.
   *
   * @return bool
   *   The result validating content-type.
   */
  public static function validateContentType($content_type) {
    // Load all Thunder Content types.
    $load_content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    if (!empty($load_content_types)) {
      foreach ($load_content_types as $type) {
        if ($content_type == $type->id()) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }
}