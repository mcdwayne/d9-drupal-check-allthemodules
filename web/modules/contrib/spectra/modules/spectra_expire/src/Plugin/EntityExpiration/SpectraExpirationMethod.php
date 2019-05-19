<?php

namespace Drupal\spectra_expire\Plugin\EntityExpiration;

use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_expiration\EntityExpirationMethodInterface;
use Drupal\spectra\Entity\SpectraStatement;
use Drupal\spectra\Entity\SpectraData;

/**
 *
 * @EntityExpirationMethod(
 *   id = "spectra_expiration_methods",
 *   select_options = {
 *     "spectra_expire_data_type" = @Translation("Spectra: Get Statements referenced by expiring Data."),
 *   },
 *   expire_options = {
 *     "spectra_expire_delete_statements_and_data" = @Translation("Spectra: Delete Statements and associated data, but leave other entities alone"),
 *     "spectra_expire_delete_all" = @Translation("Spectra: Delete a Statement's associated Actors, Actions, etc. if no other statements reference them"),
 *   },
 *   label = @Translation("SpectraExpirationMethod"),
 * )
 */
class SpectraExpirationMethod extends PluginBase implements EntityExpirationMethodInterface {

  /**
   * @return string
   *   A string description of the plugin.
   */
  public function description()
  {
    return $this->t('Spectra Expiration Methods');
  }


  /**
   * @inheritdoc
   *
   * Finds Expiring Spectra Statements
   *
   * @param (array) statement_list:
   *   Array of statement IDs
   *
   * @see \Drupal::entityQuery()
   */
  public static function select_expiring_entities($method, $policy) {
    $expire_time = time() - (isset($policy->get('expire_age')->getValue()[0]['value']) ? $policy->get('expire_age')->getValue()[0]['value']: 0);
    $entity_type = isset($policy->get('entity_type')->getValue()[0]['value']) ? $policy->get('entity_type')->getValue()[0]['value']: FALSE;
    $expire_max = isset($policy->get('expire_max')->getValue()[0]['value']) ? $policy->get('expire_max')->getValue()[0]['value']: 0;

    $statements = array();
    if ($entity_type === 'spectra_data') {
      switch ($method) {
        case 'spectra_expire_data_type':
          $data_query = \Drupal::entityQuery('spectra_data');
          $data_query->condition('created', $expire_time, '<');
          $data_query->pager($expire_max);
          $data_query->sort('data_id', 'ASC');
          $result = array_keys($data_query->execute());
          $data = SpectraData::loadMultiple($result);
          foreach ($data as $d) {
            if (isset($d->get('statement_id')->getValue()[0]['target_id'])) {
              $id = $d->get('statement_id')->getValue()[0]['target_id'];
              if(!isset($statements[$id])) {
                $statements[$id] = SpectraStatement::load($id);
              }
            }
          }
          break;
      }
    }
    return $statements;
  }

  /**
   * @inheritdoc
   *
   * Expires Spectra Statements
   *
   * @param (array) statement_list:
   *   Array of statement IDs
   *
   * @see \Drupal::entityQuery()
   */
  public static function expire_entities($method, $statement_list) {
    switch ($method) {
      case 'spectra_expire_delete_statements_and_data':
        foreach($statement_list as $statement) {
          $statement->delete();
        }
        break;
      case 'spectra_expire_delete_all':
        foreach($statement_list as $statement) {
          $statement->deleteAssociatedEntities();
          $statement->delete();
        }
        break;
    }
  }



}