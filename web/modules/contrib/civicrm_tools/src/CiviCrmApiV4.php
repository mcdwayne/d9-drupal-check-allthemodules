<?php

namespace Drupal\civicrm_tools;

use Drupal\civicrm\Civicrm;

/**
 * Class CiviCRM API.
 */
class CiviCrmApiV4 implements CiviCrmApiInterface {

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Constructs a CiviCrmApiV4 object.
   *
   * @param \Drupal\civicrm\Civicrm $civicrm
   *   The CiviCRM service.
   */
  public function __construct(Civicrm $civicrm) {
    $this->civicrm = $civicrm;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_id, array $params = []) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function count($entity_id, array $params = []) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($entity_id, array $params) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_id, array $params) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function create($entity_id, array $params) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function save($entity_id, array $params) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_id, $action = '') {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($entity_id, $field_name) {
    // @todo implement
  }

  /**
   * Ensures that CiviCRM is loaded and API function available.
   */
  protected function initialize() {
    // @todo implement
  }

}
