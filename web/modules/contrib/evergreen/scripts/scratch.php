<?php

if (php_sapi_name() !== 'cli') {
  exit;
}

$eq = Drupal::service('entity.query');

$query = $eq->get('evergreen_content')
  ->condition('evergreen_entity_type', 'node')
  ->condition('evergreen_bundle', 'page')
  ->condition('evergreen_status', 0);

$results = $query->execute();

var_dump($results);
