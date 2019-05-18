<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\RswebmailBlockContent.
 */

namespace Drupal\rswebmail\Controller;

use Drupal\Core\Controller\ControllerBase;

class RswebmailBlockContent extends ControllerBase {
  public function content() {
    return rswebmail_block_content();
  }
}
