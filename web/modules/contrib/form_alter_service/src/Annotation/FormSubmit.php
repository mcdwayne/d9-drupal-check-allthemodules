<?php

namespace Drupal\form_alter_service\Annotation;

/**
 * Defines a submission handler.
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class FormSubmit extends FormHandler {

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return '#submit';
  }

}
