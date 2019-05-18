<?php

namespace Drupal\js_component;

use Drupal\Core\Form\FormStateInterface;

/**
 * Define JS component base form.
 */
abstract class JSComponentBaseForm implements JSComponentFormInterface {

  /**
   * {@inheritdoc}
   */
  public function validateComponentFormElements(array $form, FormStateInterface $form_state) {
  }
}
