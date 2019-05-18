<?php

namespace Drupal\entity_expiration\Plugin\EntityExpiration;

use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_expiration\EntityExpirationMethodInterface;

/**
 *
 * @EntityExpirationMethod(
 *   id = "delete_entities",
 *   select_options = {
 *     "entity_expiration_all_entities_of_type" = @Translation("All Entities of type"),
 *   },
 *   expire_options = {
 *     "entity_expiration_delete" = @Translation("Delete expired entities"),
 *   },
 *   label = @Translation("DefaultEntityExpirationMethod"),
 * )
 */
class DefaultEntityExpirationMethod extends PluginBase implements EntityExpirationMethodInterface {

  /**
   * @return string
   *   A string description of the plugin.
   */
  public function description()
  {
    return $this->t('Delete Entity Statements');
  }


  /**
   * @inheritdoc
   *
   * Finds Expiring Entity Statements
   *
   * @param (array) entity_expiration_policy_list:
   *   Array of entity_expiration_policy IDs
   *
   * @see \Drupal::entityQuery()
   */
  public static function select_expiring_entities($method, $policy)
  {
    $expire_time = time() - (isset($policy->get('expire_age')->getValue()[0]['value']) ? $policy->get('expire_age')->getValue()[0]['value'] : 0);
    $entity_type = isset($policy->get('entity_type')->getValue()[0]['value']) ? $policy->get('entity_type')->getValue()[0]['value'] : FALSE;
    $expire_max = isset($policy->get('expire_max')->getValue()[0]['value']) ? $policy->get('expire_max')->getValue()[0]['value'] : FALSE;

    $entities = array();
    if ($entity_type) {
      $entity_class = \Drupal::entityTypeManager()->getDefinition($entity_type)->getClass();
      $id_key = \Drupal::entityTypeManager()->getDefinition($entity_type)->getKeys()['id'];
      switch ($method) {
        case 'entity_expiration_all_entities_of_type':
          $query = \Drupal::entityQuery($entity_type);
          $query->condition('created', $expire_time, '<');
          $query->pager($expire_max);
          $query->sort($id_key, 'ASC');
          $result = array_keys($query->execute());

          $entities = $entity_class::loadMultiple($result);
          break;
      }
    }
    return $entities;
  }

  /**
   * @inheritdoc
   *
   * Expires Entity Statements
   *
   * @param (array) entity_expiration_policy_list:
   *   Array of entity_expiration_policy IDs
   *
   * @see \Drupal::entityQuery()
   */
  public static function expire_entities($method, $entity_list) {
    switch ($method) {
      case 'entity_expiration_delete':
        foreach($entity_list as $entity) {
          $entity->delete();
        }
        break;
    }
  }



}