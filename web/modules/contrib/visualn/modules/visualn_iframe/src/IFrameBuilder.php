<?php

namespace Drupal\visualn_iframe;

use Drupal\Core\Url;
use Drupal\visualn_iframe\Entity\VisualNIFrame;

class IFrameBuilder {

  /**
   * Get url by hash.
   */
  public function getIFrameUrl($hash) {
    $url = Url::fromRoute('visualn_iframe.iframe_controller_build', array('hash' => $hash))->setAbsolute()->toString();
    return $url;
  }

  public function buildLink($iframe_url) {
    $build = [];
    // generate link uid
    $link_uid = 'link-uid-' .  substr(\Drupal::service('uuid')->generate(), 0, 5);
    // @todo: maybe use ajax callback instead of link click hander
    $build['#markup'] = "<div class='visualn-iframe-share-link'><a href='' rel='".$link_uid."'>Share</a></div>";
    // @todo: attach js script for the Share link
    $build['#attached']['library'][] = 'visualn_iframe/visualn-iframe-share-link';
    // @todo: generate and #ajax url or even share link url (as a temporary solution) to the script
    $build['#attached']['drupalSettings']['visualn_iframe']['share_iframe_links'][$link_uid] = $iframe_url;

    return $build;
  }


  /**
   * @todo: add docblock
   */
  public function createIFrameEntity($params) {
    // @todo: set default values for $params
    $params += [
      // @todo: review langcode and status values
      'langcode' => 'en',
      'status' => 1,
      'user_id' => \Drupal::currentUser()->id(),
      'settings' => NULL,
      'data' => NULL,
      // @todo: or make not required and set to NULL
      'drawing_id' => 0,
      'displayed' => NULL,
      'location' => NULL,
      'viewed' => NULL,
      'implicit' => NULL,
    ];

    // @todo: hash must be unique for new entities,
    //   throw error if any and return NULL
    $entity = VisualNIFrame::create([
      'drawing_id' => $params['drawing_id'],
      'hash' => $params['hash'],
      // @todo: is status required?
      'status' => $params['status'],
      'langcode' => $params['langcode'],
      'name' => $params['name'],
      'user_id' => $params['user_id'],
      'displayed' => $params['displayed'],
      'location' => $params['location'],
      'viewed' => $params['viewed'],
      // @todo: should be set in paramters, also check the handler_key value itself
      //   throw an error if not set
      'handler_key' => $params['handler_key'],
      // @todo: make sure that data is an array or a string (dont allow objects
      //   to avoid unserialize vunlnerability)
      //'data' => isset($params['data']) ? ['value' => serialize($params['data'])] : NULL,
      'implicit' => $params['implicit'],
    ])
    // use setSettings() to set 'settings' value
    ->setSettings($params['settings'])
    ->setData($params['data']);

    $entity->save();

    return $entity;
  }


  // @todo: add target_entity_type argument when added to the db structure
  // @todo: return entity, not hash
  public function getIFrameEntityByTargetId($target_entity_id, $create = TRUE) {
    $drawing_id = $target_entity_id;
    // @todo: add to parameters along with entity type
    $handler_key = 'visualn_embed_key';

    // @todo:
    // return FALSE (or NULL) if drawing with the given id doesn't exist
    // or when drawing doesn't exist and no hash found (i.e. do not create in that case)
    $drawing_entity = \Drupal::entityTypeManager()->getStorage('visualn_drawing')->load($drawing_id);
    if (!$drawing_entity) {
      return FALSE;
    }

    $query = \Drupal::entityQuery('visualn_iframe');
    //$query->condition('status', 1);
    $query->condition('drawing_id', $drawing_id);
    $query->range(0, 1);
    $entity_ids = $query->execute();
    // @todo: check 'default' flag

    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $entity = VisualNIFrame::load($entity_id);
      $hash = $entity->get('hash')->value;
    }
    elseif ($create) {
      $hash = $this->generateHash();
      // leave settings empty to use default settings for *default* iframe entries,
      // or it would require to create different entries for every other settings set
      $settings = [];
      $data = ['drawing_id' => $drawing_id];
      $params = [
        'drawing_id' => $drawing_id,
        'hash' => $hash,
        // @todo: is status required?
        'status' => 1,
        // @todo: check
        'langcode' => 'en',
        'name' => $drawing_entity->label(),
        'user_id' => \Drupal::currentUser()->id(),
        // @todo: should be set in paramters, also check the handler_key value itself
        //   throw an error if not set
        'handler_key' => $handler_key,
        'settings' => $settings,
        'data' => $data,
        'displayed' => TRUE,
        'viewed' => FALSE,
        'implicit' => TRUE,
      ];

      $entity = $this->createIFrameEntity($params);
    }

    // @todo: remove entries on drawings delete (or not since may be still needed?)


    return $entity;
  }



  // @todo: make sure that the hash is unique
  public function generateHash() {
    // @todo: maybe use 10 digits
    $hash = substr(\Drupal::service('uuid')->generate(), 0, 8);

    return $hash;
  }


  /**
   * Return staged settings entry or false
   */
  public function getStagedIFrameSettings($hash) {
    $result = \Drupal::database()->select('visualn_iframe_staged', 'vs')->fields('vs', [
      'hash', 'user_id', 'created', 'settings'
    ])
    ->condition('vs.hash', $hash)
    ->execute();
    $staged = $result->fetchAssoc();

    if ($staged !== FALSE) {
      $staged_settings = unserialize($staged['settings']);
    }
    else {
      $staged_settings = NULL;
    }

    return $staged_settings;
  }

  /**
   * Remove staged settings entry if any
   */
  public function removeStagedIFrameSettings($hash) {
    $num_deleted = \Drupal::database()->delete('visualn_iframe_staged')
      ->condition('hash', $hash)
      ->execute();

    // @todo: should be 0 or 1
    return $num_deleted;
  }

  /**
   * Remove outdated staged settings entries.
   *
   * @see visualn_iframe_cron()
   */
  public function removeOutdatedStagedIFrameSettings($period) {
    // remove all staged entries for $period <= 0
    if ($period > 0) {
      $num_deleted = \Drupal::database()->delete('visualn_iframe_staged')
        ->condition('created', time() - $period, '<')
        ->execute();
    }
    else {
      $num_deleted = \Drupal::database()->delete('visualn_iframe_staged')
        ->execute();
    }

    return $num_deleted;
  }

  public function stageIFrameSettings($hash, $user_id, $settings) {
    $created = time();

    if (empty($hash)) {
      // @todo: return and log an error
      //   should be also checked on db schema level
      return FALSE;
    }

    // @todo: what if the an earlier iframe settings changes are already staged
    //   also if there are multiple instances with the same hash, one override
    //   another so that practice should be avoided

    // @todo: also the settings array MUST NOT contain objects
    $settings = is_array($settings) ? serialize($settings) : '';
    \Drupal::database()->merge('visualn_iframe_staged')->fields([
      'hash' => $hash,
      'user_id' => $user_id,
      'created' => $created,
      'settings' => $settings,
    ])
    ->key(['hash' => $hash])
    ->execute();


    // @todo: return success status
    return TRUE;
  }

  /**
   * Create hash link to the iframe entity.
   */
  public function getHashLabel($hash) {
    // @todo: also check current user permissions to view iframe entity
    if (!empty($hash)) {
      $iframe_entity = VisualNIFrame::getIFrameEntityByHash($hash);
      if ($iframe_entity) {
        $status = $iframe_entity->isPublished() ? 'published' : 'unpublished';
        $label = "(<span class='hash-" . $status . "'>" . $iframe_entity->toLink($hash)->toString() . "</span>)";
      }
      else {
        $label = "({$hash})";
      }
    }
    else {
      $label = '';
    }

    return $label;
  }
}
