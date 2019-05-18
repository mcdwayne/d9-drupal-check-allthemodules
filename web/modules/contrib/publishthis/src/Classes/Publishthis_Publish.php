<?php
namespace Drupal\publishthis\Classes;

use \Drupal\publishthis\Classes\Publishthis_API;
use Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class Publishthis_Publish {
  private $pt_settings = null;
  private $obj_api = null;
  private $publishing_actions = [];

  /**
  * Publishthis_Publish constructor
  */
  function __construct() {
    $config = \Drupal::config('publishthis.settings');
    
    $this->pt_settings = $config->get('pt_curated_publish');

    $this->obj_api  = new Publishthis_API();
  }

  /**
   * Returns all publishing actions that are published
   */
  public function get_publishing_actions($publishTypeId) {
    // Find records
    $actions = [];
    $result = \Drupal::database()->select('pt_publishactions', 'pp')
      ->fields('pp')
      ->condition('pp.publish_type_id', $publishTypeId, '=')
      ->execute();

    while($record = $result->fetchAssoc()) {
      $actions[$record['id']] = $record;
    }

    return $actions;
  }
  
  /**
   *   Set docid for specified post ID
   *
   * @param unknown $docid
   */
  private function _set_docid( $nid, $docid, $setName = '') {      
    $query = \Drupal::database()->insert('pt_docid_links')
      ->fields([
        'docId' => $docid,
        'setName' => $setName,
        'nid' => $nid,
        'curateUpdateDate' => time()
      ]);

    $result = $query->execute();
    return $result;
  }

  /**
   *   Get node ID by specified docid value
   *
   * @param unknown $docid
   */
  private function _get_post_by_docid( $docid ) {
    $result = \Drupal::database()->select('pt_docid_links', 'pdl')
      ->fields('pdl', ['docId','nid'])
      ->condition('pdl.docId', $docid, '=')
      ->range(0,1)    
      ->execute()
      ->fetchAssoc();

    return $result ? $result['nid'] : 0;
  }

  /**
   *   Set node curate date for node id
   *
   * @param unknown $nid
   */
  private function _set_curatedate_by_nid( $nid, $curateUpdateDate ) {
    $query = \Drupal::database()->update('pt_docid_links')
      ->fields(['curateUpdateDate' => $curateUpdateDate])
      ->condition( 'nid', $nid, '=');

    $result = $query->execute();
    return $result;
  }

  /**
   * this takes an array of feed ids, and then tries to publish each one of them
   * using all of our helper functions.
   * This will usually be called from our publishing endpoint
   */

  public function publish_specific_feeds($arrFeedIds) {
    try {
      //to publish, we need our actual feed objects
      $arrFeeds = $this->obj_api->get_feeds_by_ids($arrFeedIds);

      //loop feeds to publish
      foreach ( $arrFeeds as $feed ) {

        //get all publishing actions that match up with this feed template (usually 1)
        $arrPublishingActions = $this->get_publishing_actions($feed['publishTypeId']);

        foreach ( $arrPublishingActions as $pubAction ) {
          $action_meta = unserialize($pubAction['value']);
          try {
            $status = $this->publish_feed_with_publishing_action($feed, $action_meta);
            return $status;
          }
          catch( Exception $ex ) {
            $message = [
              'message' => 'Import of Feed Failed - The Feed Id that failed: '. $feed['feedId'] . ' with the following error:' . $ex->getMessage(),
              'status' => 'error',
            ];
            $this->obj_api->LogMessage( $message, '1');
          }
        }
      }
    }
    catch( Exception $ex ) {
      $message = [
        'message' => 'Import of Feed Failed - A general exception happened during the publishing of specific feeds. Feed Ids not published:'  . $ex->getMessage() ,
        'status' => 'error',
      ];
      $this->obj_api->LogMessage($message, '1');
    }
  }

  /**
   * Publishes the single feed with a Publishing Actions meta information
   *
   * @param int     $feed_id     Publishthis Feed id
   * @param array   $feed_meta   Publishthis Feed data (display name, etc.)
   * @param int     $action_id   Publishing Action id
   * @param array   $action_meta Publishing Action data
   */
  function publish_feed_with_publishing_action( $feed, $action_meta ) {
    try{
      $posts_updated = $posts_inserted = $posts_deleted = $posts_skipped = 0;

      $feed_title = $feed['title'];
      $feed_id = $feed['feedId'];
      $docId = $feed['docId'];
      $imageUrl = $feed['imageUrl'];
      $summary = $feed['summary'];
      $publishTypeId = $feed['publishTypeId'];
      $publishDate = $feed['publishDate'];
      $source_url = $feed['source_url'];
      // Unique set name
      $set_name = '_publishthis_set_' . $action_meta['pta_feed_template'] . '_' . $action_meta['pta_content_type'] . '_' . $feed_id;

      //don't update existed posts if synchronization is turned off
      $nid = $this->_get_post_by_docid($docId);

      $status = $this->_update_content($nid, $feed_id, $set_name, $docId, $feed_title, $imageUrl, $summary, $publishTypeId, $publishDate, $source_url, $action_meta);

      if($status['status'] == 'updated') {
        $posts_updated++;
      }

      if($status['status'] == 'inserted') {
        $posts_inserted++;
      }

      if($status['status'] == 'skipped') {
        $posts_skipped++;
      }

      $message = [
        'message' => 'Import Results - ' . ( $posts_updated + $posts_inserted + $posts_skipped + $posts_deleted ) . ' post(s) processed: '.
        $posts_updated.' updated, '.$posts_inserted.' inserted, '.$posts_deleted.' deleted, '.$posts_skipped.' skipped',
        'status' => 'info',
      ];

      $this->obj_api->LogMessage($message, '2');
      return $status['nid'];
    }
    catch( Exception $ex ) {
      $message = [
        'message' => 'Import Results - Unable to publish the feed id:' . $feed['feedId'] . ', because of:' . $ex->getMessage(),
        'status' => 'error',
      ];

      $this->obj_api->LogMessage($message, '1');
    }
  }

  /**
   *   Save  publishthis content as a node
   */
  private function _update_content($nid, $feed_id, $set_name, $docId, $feed_title, $imageUrl, $summary, $publishTypeId, $publishDate, $source_url, $action_meta) {
    
    if($action_meta['pta_format_type'] == 'Individual' ) {

      // Update existing content
      if (!empty($nid)) {
        if($action_meta['pta_ind_modified_content'] == '1' ) {
          $data = file_get_contents($imageUrl);
          $file = file_save_data($data, 'public://publishthis-' . time() . '.jpg', FILE_EXISTS_RENAME);
          $fid = $file->id();

          $node = \Drupal\node\Entity\Node::load($nid);
          $node->title->value = $feed_title;
          $node->body->value = nl2br($summary);
          $node->body->format = 'full_html';
          $node->{$action_meta['pta_publish_image']} = $fid;
          $node->{$action_meta['pta_source_url']}->value = $source_url;
          $node->save();

          $this->_set_curatedate_by_nid($nid, time());

          $message = [
            'message' => 'Updated Individual - Successfully upadted. Node id:' . $nid . ' for feed id:' . $feed_id,
            'status' => 'info'
          ];
          $this->obj_api->LogMessage( $message, '1');

          $status = [
            'nid' => $nid,
            'status' => 'updated',
          ];

        }
        else {
          $message = [
            'message' => 'Skipped Individual - It was not updated because update is restricted in your setting. Node id:' . $nid . ' for feed id:' . $feed_id,
            'status' => 'info'
          ];
          $this->obj_api->LogMessage( $message, '1');
          $status =  [
            'nid' => $nid,
            'status' => 'skipped',
          ];
        }
      }
      else {
        // Create New Content
        $fid = NULL;
        $managed = TRUE;
        $replace = FILE_EXISTS_RENAME;
        $file_url = system_retrieve_file($imageUrl, 'public://', $managed, $replace);
        if(isset($file_url->uri->value)){
          $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $file_url->uri->value]);
          foreach ($files as $file) {
            $fid = $file->fid->value;
          } 
        }

        $node = \Drupal\node\Entity\Node::create([
          'type' => $action_meta['pta_content_type'],
          'title' => $feed_title,
          'body' => ['value' => nl2br($summary), 'format' => 'full_html'],
          $action_meta['pta_publish_image'] => $fid,
          $action_meta['pta_source_url'] => $source_url,
          'status' => $action_meta['pta_content_status'],
          'uid' => $action_meta['pta_publish_author'],
        ]);
        $node->save();

        $this->_set_docid($node->id(), $docId, $set_name );

        $message = [
          'message' => 'Created Individual - Successfully created. Node id:' . $node->id() . ' for feed id:' . $feed_id,
          'status' => 'info'
        ];
        $this->obj_api->LogMessage( $message, '1');

        $status = [
          'nid' => $node->id(),
          'status' => 'inserted',
        ];
      }
    }

    return  $status;
  }
}
