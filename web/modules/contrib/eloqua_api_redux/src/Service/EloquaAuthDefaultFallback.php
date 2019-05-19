<?php

namespace Drupal\eloqua_api_redux\Service;

/**
 * Class EloquaAuthDefaultFallback.
 *
 * Provides default implementation for eloqua auth fallback which needs
 * to be overridden by module that implements the auth fallback e.g. using
 * resource owner password grants.
 *
 * @package Drupal\eloqua_api_redux\Service
 */
class EloquaAuthDefaultFallback implements EloquaAuthFallbackInterface {

  /**
   * Default generateTokensByResourceOwner implementation.
   *
   * @inheritDoc
   */
  public function generateTokensByResourceOwner() {
    return FALSE;
  }

}
