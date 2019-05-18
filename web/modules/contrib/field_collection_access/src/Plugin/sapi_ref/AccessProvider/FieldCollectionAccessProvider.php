<?php

namespace Drupal\field_collection_access\Plugin\sapi_ref\AccessProvider;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Session\AccountInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\sapi_ref\AccessProvider\AccessProvider;
use Drupal\sapi_ref\AccessProvider\AccessProviderInterface;

/**
 * SAPI Reference Access Provider for node_access.
 *
 * Wraps node_access_records and node_access_grants and formats them for
 * Search API References in Solr.
 *
 * @AccessProvider(
 *   id = "FieldCollectionAccessProvider",
 *   label = @Translation("Field Collection Access Provider"),
 *   description = @Translation("An access provider for field collection items."),
 * )
 */
class FieldCollectionAccessProvider extends AccessProvider implements AccessProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityRequirements(Entity $entity) {
    if ($entity instanceof FieldCollectionItem) {
      $grantStorage = \Drupal::service('field_collection_access.grant_storage');
      $grants = $grantStorage->getRecordsFor($entity);

      $rules = [];
      foreach ($grants as $g) {
        if ($g["grant_view"]) {
          $rules[] = "field_collection_access__" . $g["realm"] . "__" . $g["gid"];
        }
      }
      return $rules;
    }
    return parent::getEntityRequirements($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserGrants(AccountInterface $account) {
    $rules = [];
    $grantStorage = \Drupal::service('field_collection_access.grant_storage');
    $grants = $grantStorage->getUserGrants("view", $account);
    foreach ($grants as $realm => $gids) {
      foreach ($gids as $gid) {
        $rules[] = "field_collection_access__" . $realm . "__" . $gid;
      }
    }
    return $rules;
  }

}
