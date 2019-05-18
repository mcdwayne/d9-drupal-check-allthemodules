<?php

namespace Drupal\iots_device\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook Theme.
 */
class Theme extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook() {
    $theme = [];
    $theme['iots_device'] = [
      'render element' => 'elements',
      'file' => 'iots_device.page.inc',
      'template' => 'iots_device',
    ];
    $theme['iots_device_content_add_list'] = [
      'render element' => 'content',
      'variables' => ['content' => NULL],
      'file' => 'iots_device.page.inc',
    ];
    return $theme;
  }

}
