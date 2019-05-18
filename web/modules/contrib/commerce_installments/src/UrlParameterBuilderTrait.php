<?php

namespace Drupal\commerce_installments;

/**
 * Trait UrlParameterBuilderTrait
 *
 * @package Drupal\commerce_installments
 */
trait UrlParameterBuilderTrait {

  protected function getUrlParameters() {
    return \Drupal::routeMatch()->getRawParameters()->all();
  }

}
