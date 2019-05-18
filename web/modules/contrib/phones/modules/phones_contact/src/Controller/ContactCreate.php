<?php

namespace Drupal\phones_contact\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Contact Create.
 */
class ContactCreate extends ControllerBase {

  /**
   * Create.
   */
  public static function cr(array $data) {
    $storage = \Drupal::entityManager()->getStorage('phones_contact');
    $contact = $storage->create([
      'name' => $data['name'],
      'type' => $data['type'],
      'field_hphone' => $data['phone'],
      'field_ref_organization' => $data['org'],
    ]);
    $contact->save();
    if ($contact->id()) {
      return $contact->id();
    }
    else {
      return FALSE;
    }
  }

}
