<?php

namespace Drupal\civicrm_tools;

use Drupal\civicrm\Civicrm;

/**
 * Class CiviCRM API.
 */
class CiviCrmApiV3 implements CiviCrmApiInterface {

  /**
   * The CiviCRM service.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Constructs a CiviCrmApiV3 object.
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
    $this->initialize();
    $result = civicrm_api3($entity_id, 'get', $params);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function count($entity_id, array $params = []) {
    $this->initialize();
    $result = civicrm_api3($entity_id, 'getcount', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll($entity_id, array $params) {
    $result = [];
    if (empty($params) && $this->mandatoryParams($entity_id)) {
      \Drupal::messenger()->addError(
        'CiviCrmApi getAll(), must contain parameters. Cowardly refusing to get all entities for @entity_id.',
        ['@entity_id' => $entity_id]
      );
    }
    else {
      $count = $this->count($entity_id, $params);
      if (!array_key_exists('options', $params)) {
        $params['options'] = [
          'limit' => $count,
        ];
      }
      else {
        $params['options']['limit'] = $count;
      }
      $result = $this->get($entity_id, $params);
    }
    return $result;
  }

  /**
   * Set a list of entities that could lead to timeout due to their amount.
   *
   * @param string $entity_id
   *   Entity id.
   *
   * @return bool
   *   Does this entity requires params.
   */
  private function mandatoryParams($entity_id) {
    return in_array($entity_id, ['Contact']);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity_id, array $params) {
    $this->initialize();
    $result = civicrm_api3($entity_id, 'delete', $params);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function create($entity_id, array $params) {
    $this->initialize();
    $result = civicrm_api3($entity_id, 'create', $params);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save($entity_id, array $params) {
    return $this->create($entity_id, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_id, $action = '') {
    $this->initialize();
    $result = civicrm_api3($entity_id, 'getfields', [
      'sequential' => 1,
      'action' => $action,
    ]);
    return $result['values'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($entity_id, $field_name) {
    $this->initialize();
    $result = civicrm_api3($entity_id, 'getoptions', ['field' => $field_name]);
    return $result['values'];
  }

  /**
   * Ensures that CiviCRM is loaded and API function available.
   */
  protected function initialize() {
    if (!function_exists('civicrm_api3')) {
      $this->civicrm->initialize();
    }
  }

}
