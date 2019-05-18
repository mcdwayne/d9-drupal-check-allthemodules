<?php

namespace Drupal\import_through_csv;

use Drupal\Core\Config\Entity;
use Drupal\taxonomy\Entity\Vocabulary;
class EntityTypeFetch {

  /**
   * Fetch all the content type of your site.
   *
   * @return array
   *   An array of content type of the site
   */
  public function fetchEntity($type) {
      $entityTypesList = array();
      if($type == 'node_type') {
          $contentTypes = \Drupal::service('entity.manager')->getStorage($type)->loadMultiple();
          foreach ($contentTypes as $contentType) {
              $entityTypesList[$contentType->id()] = $contentType->label();
          }
      }
      else {
          $vocabularies = Vocabulary::loadMultiple();
          $entityTypesList = [];
          foreach ($vocabularies as $vid => $vocablary) {
              $entityTypesList[$vid] = $vocablary->get('name');
          }
      }
    return $entityTypesList;
  }

 }
