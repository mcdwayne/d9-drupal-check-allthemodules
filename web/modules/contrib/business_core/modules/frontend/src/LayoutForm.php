<?php

namespace Drupal\frontend;

class LayoutForm extends ContainerForm {

  /**
   * {@inheritdoc}
   */
  public function nameExists($value) {
    if ($this->entityTypeManager->getStorage('layout')->getQuery()->condition('id', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }

    return FALSE;
  }

}
