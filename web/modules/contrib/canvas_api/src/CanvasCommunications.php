<?php
/**
 * @file
 * Helper functions that utilize Canvas' Communication Channel APIs
 *
 * See @link https://canvas.instructure.com/doc/api/communication_channels.html @endlink
 *
 */
namespace Drupal\canvas_api;


/**
 * {@inheritdoc}
 */
class CanvasCommunications extends Canvas {

  /**
   * List Canvas communication channels
   *
   * See @link https://canvas.instructure.com/doc/api/communication_channels.html#method.communication_channels.index @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.communications');
   *  $user_id = 'sis_user_id:' . 'ABC123';
   *  $channels = $canvas_api->listChannels($user_id);
   *
   * @return array
   */
  
  public function listChannels($userID){
    $this->path = "users/$userID/communication_channels";
    return $this->get();
  }

  /**
   * Create canvas communication channel
   *
   * See @link https://canvas.instructure.com/doc/api/communication_channels.html#method.communication_channels.create @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.communications');
   *  $userID = 3;
   *  $canvas_api->params = array(
   *    'communication_channel' => array(
   *       'address' => 'jdoe@example.com',
   *       'type' => 'email',
   *    ),
   *    'skip_verification' => 1,
   *  );
   * 
   *  $channel = $canvas_api->create($userID);
   *
   * @return array
   */
  public function create($userID){
    $this->path = "users/$userID/communication_channels";
    return $this->post();   
  }
  
  /**
   * Delete a communication channel
   *
   * See @link https://canvas.instructure.com/doc/api/communication_channels.html#method.communication_channels.destroy @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.communications');
   *  $userID = 45;
   *  $ccID = 3;
   *  $channels = $canvas_api->deleteChannel($userID,$ccID);
   *
   * @return array
   */   
  public function deleteChannel($userID,$ccID){
    $this->path = "users/$userID/communication_channels/$ccID";

    return $this->delete();
  }
}
