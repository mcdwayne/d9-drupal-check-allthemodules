<?php

namespace Drupal\phones_contact\Controller;

/**
 * @file
 * Contains \Drupal\phones_contact\Controller\ContactPhones.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller ContactPhones.
 */
class ContactPhones extends ControllerBase {

  /**
   * Get Contact Phones.
   */
  public static function getPhones($entity) {
    $phones = self::phonesExtract($entity);
    if ($entity->bundle() == 'organization') {
      $persons = self::query($entity->id());
      if (!empty($persons)) {
        foreach ($persons as $person) {
          $phones = $phones + self::phonesExtract($person);
        }
      }
    }
    return $phones;
  }

  /**
   * Get Contact Phones.
   */
  public static function phonesExtract($entity) {
    $phones = [];
    if (!empty($fields = $entity->field_phone->getValue())) {
      foreach ($fields as $field) {
        $phone = $field['value'];
        $phones[$phone] = $phone;
      }
    }
    return $phones;
  }

  /**
   * Query.
   */
  public static function query($id) {
    $entities = [];
    $entity_type = 'phones_contact';
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->condition('field_ref_organization', $id);
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

}
