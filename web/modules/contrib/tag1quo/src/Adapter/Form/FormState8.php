<?php

namespace Drupal\tag1quo\Adapter\Form;

/**
 * Class FormState8.
 *
 * @internal This class is subject to change.
 *
 * @property \Drupal\Core\Form\FormStateInterface $formState
 */
class FormState8 extends FormState {

  /**
   * {@inheritdoc}
   */
  public function setErrorByName($name, $message = '') {
    if (is_string($name) && strpos($name, '.') !== FALSE) {
      $name = explode('.', $name);
    }
    if (is_array($name)) {
      $name = implode('][', $name);
    }
    $this->formState->setErrorByName($name, $message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValue($key, $default = NULL) {
    if (is_string($key) && strpos($key, '.') !== FALSE) {
      $key = explode('.', $key);
    }
    return $this->formState->getValue($key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->formState->getValues();
  }

}
