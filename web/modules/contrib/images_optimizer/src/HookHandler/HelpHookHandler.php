<?php

namespace Drupal\images_optimizer\HookHandler;

/**
 * Hook handler for the help() hook.
 *
 * @package Drupal\images_optimizer\HookHandler
 */
class HelpHookHandler {

  /**
   * Get the content for the help page of our module.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   The markup or NULL.
   */
  public function process($route_name) {
    // @TODO: Provides other useful information instead?
    // Or use Markdown Filter module if it is installed?
    return $route_name === 'help.page.images_optimizer' ?
      check_markup(file_get_contents(drupal_get_path('module', 'images_optimizer') . '/README.txt')) : NULL;
  }

}
