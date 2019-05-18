<?php

namespace Drupal\jeditable\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JeditableAjax.
 *
 * @package Drupal\jeditable\Controller
 */
class JeditableAjax extends ControllerBase {

  /**
   * Saves latest changed value.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return Updated value.
   */
  public function jeditableAjaxSave() {
    $array = explode('-', $_POST['id']);
    // Fieldtype and $delta can used when expanding the scope of the module.
    list($type, $id, $field_name, $field_type, $delta) = $array;
    $value = Html::escape($_POST['value']);
    $storage = $this->entityTypeManager()->getStorage($type);
    if ($storage instanceof EntityStorageInterface) {
      $entity = $storage->load($id);
      if ($entity instanceof EntityInterface) {
        $entity->{$field_name}->value = $value;
        $entity->save();
        return new Response($value);
      }
    }
    // The entity could not be loaded, return the appropriate code.
    return new Response($value, 400);
  }

}
