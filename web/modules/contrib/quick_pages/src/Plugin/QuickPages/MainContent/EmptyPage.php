<?php

/**
 * @file
 * Contains \Drupal\quick_pages\Plugin\QuickPages\MainContent\EmptyPage.
 */

namespace Drupal\quick_pages\Plugin\QuickPages\MainContent;

use Drupal\quick_pages\MainContentBase;

/**
 * Provides an empty main content.
 *
 * @MainContent(
 *   id = "empty_page",
 *   title = @Translation("Empty"),
 * )
 */
class EmptyPage extends MainContentBase {

  /**
   * {@inheritdoc}
   */
  public function getMainContent() {
    return ['#markup' => ''];
  }

}
