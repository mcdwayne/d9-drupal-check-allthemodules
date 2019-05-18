<?php

namespace Drupal\form_alter_service\Annotation;

/**
 * Defines a validation handler.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class FormValidate extends FormHandler {

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return '#validate';
  }

}
