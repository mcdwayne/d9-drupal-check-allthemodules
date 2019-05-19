<?php

namespace Drupal\views_ajax_get;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;

class CacheableAjaxResponse extends AjaxResponse implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
