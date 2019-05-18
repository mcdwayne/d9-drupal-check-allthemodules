<?php

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config;
use Symfony\Component\HttpFoundation\JsonResponse;

function accessibility_test_add($node_type) {
  $language = language_default();
  $new_test = entity_create('accessibility_test', array(
      'language' => $language->id,
      'status' => 1
    ));
  return Drupal::entityManager()->getForm($new_test);
}
