<?php

namespace Drupal\phones_contact\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * Hook Cron.
 */
class Theme extends ControllerBase {

  /**
   * Hook.
   */
  public static function hook() {
    return [
      'phones_contact' => [
        'render element' => 'elements',
        'file' => 'phones_contact.page.inc',
        'template' => 'phones_contact',
      ],
      'phones_contact_content_add_list' => [
        'render element' => 'content',
        'variables' => ['content' => NULL],
        'file' => 'phones_contact.page.inc',
      ],
    ];
  }

}
