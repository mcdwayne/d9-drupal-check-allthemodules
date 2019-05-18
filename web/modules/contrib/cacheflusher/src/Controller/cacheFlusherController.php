<?php

namespace Drupal\cacheflusher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * CacheFlusherController.
 */
class CacheFlusherController extends ControllerBase {

  /**
   * Public function cacheFlusherCacheClear.
   */
  public function cacheFlusherCacheClear() {
    // Clear the caches.
    drupal_flush_all_caches();
    //Display a message.
    $this->messenger()->addMessage(t('All Caches cleared. '));

    // Get the page where the clear cache request came from.
    $previousUrl = \Drupal::request()->server->get('HTTP_REFERER');

    // Go back to that page.
    return new RedirectResponse($previousUrl);
    
  }

}
