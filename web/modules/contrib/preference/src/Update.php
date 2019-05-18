<?php
 
/**
 * @file
 * Contains \Drupal\preference\Update.
 */
 
namespace Drupal\preference;
 
class Update {
 
  protected $response;
  protected $db;
  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct() {
    $this->response = array('msg' => 'success');
    $this->db = \Drupal::database();
  }
 
  /**
   * Return the value of the example variable.
   */
  public function getUpdated() {
    $result = $this->db->select('node', 'n')
    ->fields('n', array('nid'))
    ->execute();
    print_r($result);die;
    return $this->response;
  }
 
}