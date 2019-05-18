<?php

namespace Drupal\ajax_cached_get\Response;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;

class GetAjaxResponse extends AjaxResponse implements CacheableResponseInterface {
  use CacheableResponseTrait;

  /**
   * {@inheritdoc}
   */
}