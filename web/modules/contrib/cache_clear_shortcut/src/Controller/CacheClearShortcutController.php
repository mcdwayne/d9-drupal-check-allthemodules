<?php

namespace Drupal\cache_clear_shortcut\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * {@inheritdoc}
 */
class CacheClearShortcutController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function clearcache() {
    drupal_flush_all_caches();
    \Drupal::logger('cache_clear_shortcut')->notice('Cache Cleared.');
    return new JsonResponse(array('data' => 'test'));
  }

}
