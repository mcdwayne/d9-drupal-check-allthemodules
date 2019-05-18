<?php

namespace Drupal\autodrop_test_page\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Generates a test page for the autodrop module.
 */
class AutodropTestPageController {

  use StringTranslationTrait;

  /**
   * Returns a test page and sets the title.
   */
  public function testPage() {
    return [
      '#title' => t('Autodrop test page'),
      'dropbutton' => [
        '#type' => 'dropbutton',
        '#attributes' => [
          'id' => 'autodrop-test-dropbutton',
        ],
        '#links' => [
          'action1' => [
            'title' => $this->t('Action no 1'),
            'url' => Url::fromRoute('<front>'),
          ],
          'action2' => [
            'title' => $this->t('Action no 2'),
            'url' => Url::fromRoute('<front>'),
          ],
        ],
      ],
    ];
  }
}
