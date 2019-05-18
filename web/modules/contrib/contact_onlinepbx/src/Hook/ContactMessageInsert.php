<?php

namespace Drupal\contact_onlinepbx\Hook;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\contact_onlinepbx\Controller\Api;

/**
 * ContactMessageInsert.
 */
class ContactMessageInsert extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook(EntityInterface $entity) {
    foreach (self::getFields() as $field) {
      if ($entity->hasField($field)&& $entity->$field->value) {
        if ($phone = self::tryPhone($entity->$field->value)) {
          $message = "Пробуем позвонить вам по телефону $phone";
          drupal_set_message($message);
          Api::callNow($phone);
        }
      }
    }
  }

  /**
   * Find phone in string.
   */
  public static function tryPhone($str) {
    $phone = FALSE;
    $numbers = preg_replace("/[^0-9]/", '', $str);
    $stip = substr($numbers, 1, 10);
    if (strlen($stip) == 10) {
      $phone = "8" . $stip;
    }
    return $phone;
  }

  /**
   * Get Fields From Settings.
   */
  public static function getFields() {
    $config = \Drupal::config('contact_onlinepbx.settings');
    $fields = $config->get('fields');
    $result = [];
    foreach (explode("\n", $fields) as $line) {
      if (trim($line)) {
        $result[] = trim($line);
      }
    }
    return $result;
  }

}
