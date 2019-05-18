<?php

namespace Drupal\phones_contact\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook OnlinepbxClientsAlter.
 */
class OnlinepbxClientsAlter extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(&$clients) {
    $phones = array_keys($clients);
    $contacts = self::query($phones);
    $match_phones = [];
    foreach ($contacts as $id => $entity) {
      foreach ($entity->field_phone->getValue() as $field) {
        $phone = $field['value'];
        $match_phones[$phone] = [
          'name' => $entity->name->value,
          'id' => $id,
        ];
      }
    }

    foreach ($clients as $phone => $client) {
      if (isset($match_phones[$phone])) {
        $contact = $match_phones[$phone];
        $clients[$phone]['name'] = $contact['name'];
        $link = "phones/contact/{$contact['id']}";
        $clients[$phone]['name'] = "<a href='/$link'>{$contact['name']}</a>";
      }
      else {
        $link = "phones/contact/quick-add/$phone";
        $ajax = "class='use-ajax' data-dialog-type='modal'";
        $clients[$phone]['name'] = "<a $ajax href='/$link'>+</a>";
      }
    }
  }

  /**
   * Query.
   */
  public static function query($phones) {
    $entities = [];
    $entity_type = 'phones_contact';
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $query = \Drupal::entityQuery($entity_type)
      ->condition('status', 1)
      ->sort('created', 'ASC')
      ->condition('field_phone', $phones, 'IN');
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $id => $entity) {
        $entities[$id] = $entity;
      }
    }
    return $entities;
  }

}
