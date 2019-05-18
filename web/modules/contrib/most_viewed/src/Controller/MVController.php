<?php

namespace Drupal\most_viewed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\Response;

/**
 * MV Controller.
 */
class MVController extends ControllerBase {

  /**
   * Save hit to database.
   * @todo Обращаться к объекту без указания парент класса
   */
  public function stat() {
    $bundle = \Drupal::request()->query->get('bundle');

    $entity_type = \Drupal::request()->query->get('entity_type');
    $entity_id = \Drupal::request()->query->get('entity_id');

    // Increase the view count.
    if (!empty($entity_type) && !empty($bundle) && !empty($entity_id)) {
      Database::getConnection()->insert('most_viewed_hits')->fields(
        array(
          'entity_id' => $entity_id,
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'created' => \Drupal::time()->getRequestTime(),
        )
      )->execute();
    }
    return new Response();
  }
}
