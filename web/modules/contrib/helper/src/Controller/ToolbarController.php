<?php

namespace Drupal\helper\Controller;

use Drupal\admin_toolbar_tools\Controller\ToolbarController as AdminToolbarController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ToolbarController extends AdminToolbarController {

  public function flushPhp() {
    if (function_exists('opcache_reset')) {
      opcache_reset();
      drupal_set_message($this->t('PHP opcache cleared using opcache_reset().'));
    }

    if (function_exists('apc_cache_clear')) {
      apc_cache_clear();
      apc_clear_cache('user');
      apc_clear_cache('opcode');
      drupal_set_message($this->t('PHP APC cache cleared using apc_cache_clear().'));
    }

    clearstatcache();
    drupal_set_message($this->t('File status cache cleared using clearstatcache().'));

    return new RedirectResponse($this->reload_page());
  }

}