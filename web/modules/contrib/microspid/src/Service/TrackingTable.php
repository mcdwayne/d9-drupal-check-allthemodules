<?php

namespace Drupal\microspid\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;

/**
 * Service to interact with the database.
 */
class TrackingTable {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A database object.
   *
   */
  protected $database;

  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory = NULL) {
    $this->config = $config_factory->get('microspid.settings');
    $this->database =  Database::getConnection();
  }

  /**
   * Create a tracking record.
   *
   * @param int $userid
   *   id of the current user
   * @param int $nodeid
   *   id of the current node
   */
  public function createTrack($id, $instant, $data) {
    $this->database->insert('microspid_tracking')
    ->fields(array(
      'AuthnReq_ID' => $id,
      'AuthnReq_IssueInstant' => $instant,
      'Timestamp' => time(),
      'AuthnRequest' => $data,
      ))
    ->execute();
  }
  /**
   * @TODO [microspid_update_track description]
   * @method microspid_update_track
   * @param  [type] $id
   *   [description].
   * @param  [type] $idr
   *   [description].
   * @param  [type] $instant
   *   [description].
   * @param  [type] $data
   *   [description].
   * @param  [type] $issuer
   *   [description].
   * @return [type]                          [description]
   */
  public function updateTrack($id, $idr, $instant, $data, $issuer) {
    return ($this->database->update('microspid_tracking')
      ->fields(array(
        'Resp_ID' => $idr,
        'Resp_IssueInstant' => $instant,
        'Resp_Issuer' => $issuer,
        'Response' => $data,
      ))
      ->condition('AuthnReq_ID', $id)
      ->condition('Resp_ID', NULL, 'IS')
      ->execute());
  }
}
