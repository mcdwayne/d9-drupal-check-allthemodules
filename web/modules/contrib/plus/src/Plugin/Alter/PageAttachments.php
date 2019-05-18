<?php

namespace Drupal\plus\Plugin\Alter;

use Drupal\plus\Plugin\ThemePluginBase;

/**
 * Implements hook_page_attachments_alter().
 *
 * @ingroup plugins_alter
 *
 * @Alter("page_attachments")
 */
class PageAttachments extends ThemePluginBase implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$attachments, &$context1 = NULL, &$context2 = NULL) {
    $attachments['#attached']['drupalSettings']['bootstrap'] = $this->theme->drupalSettings();
    if ($this->theme->livereloadUrl()) {
      $attachments['#attached']['library'][] = 'plus/livereload';
    }
  }

}
