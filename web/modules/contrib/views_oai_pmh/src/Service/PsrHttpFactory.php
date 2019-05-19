<?php

namespace Drupal\views_oai_pmh\Service;

use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
class PsrHttpFactory {

  /**
   *
   */
  public function createDiactorosFactory(RequestStack $stack) {
    $request = new DiactorosFactory();

    return $request->createRequest($stack->getCurrentRequest());
  }

}
