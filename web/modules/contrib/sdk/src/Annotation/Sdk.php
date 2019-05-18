<?php

namespace Drupal\sdk\Annotation;

use Drupal\sdk\SdkPluginDefinition;
use Drupal\Component\Annotation\AnnotationBase;

/**
 * Annotation for defining SDK.
 *
 * @Annotation
 */
class Sdk extends AnnotationBase {

  /**
   * Human-readable name of SDK.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * {@inheritdoc}
   */
  public function get() {
    return (new SdkPluginDefinition())
      ->setId($this->id)
      ->setLabel($this->label->get())
      ->setProvider($this->provider)
      ->setClass($this->class)
      ->setFormClass($this->class . 'ConfigurationForm');
  }

}
