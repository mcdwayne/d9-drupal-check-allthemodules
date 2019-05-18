<?php

namespace Drupal\phones_contact\Hook;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\phones\Controller\PhoneClear;

/**
 * Controller routines for page example routes.
 */
class Presave extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook($entity) {
    if (self::checkType($entity) && self::checkBundle($entity)) {
      $hphones = $entity->field_hphone->getValue();
      $phones = [];
      if (!empty($hphones)) {
        foreach ($hphones as $k => $hphone) {
          if ($phone = PhoneClear::clear($hphone['value'])) {
            $phones[] = $phone;
          }
        }
      }
      $entity->field_phone->setValue($phones);
      $entity->field_hphone->setValue($hphones);
    }
  }

  /**
   * Ascii filter.
   */
  public static function checkAscii($text) {
    $ascii = "a-zA-Z0-9\s`~!@#$%^&*()_+-={}|:;<>?,.\/\"\'\\\[\]";
    return preg_replace("/[^$ascii]/", '', $text);
  }

  /**
   * Check Entity Type Id.
   */
  public static function checkType($entity) {
    $result = FALSE;
    if (method_exists($entity, 'getEntityTypeId')) {
      if ($entity->getEntityTypeId() == 'phones_contact') {
        $result = TRUE;
      }
    }
    return $result;
  }

  /**
   * Check bundle.
   */
  public static function checkBundle($entity) {
    $result = FALSE;
    if (method_exists($entity, 'bundle')) {
      $type = $entity->bundle();
      if (in_array($type, ['person', 'organization'])) {
        $result = TRUE;
      }
      // "Ok" for eny type!
      if ($type) {
        $result = TRUE;
      }
    }
    return $result;
  }

}
