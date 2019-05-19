<?php

namespace Drupal\simple_glossary\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SimpleGlossaryHelpController.
 *
 * @package Drupal\simple_glossary\Controller
 */
class SimpleGlossaryHelpController extends ControllerBase {

  /**
   * Method Content to help tab.
   */
  public function content() {
    return SimpleGlossaryHelpController::helperFetchHelpContent();
  }

  /**
   * Helper Method to fetch template content.
   */
  public function helperFetchHelpContent() {
    return [
      '#theme' => 'help_tab_view',
      '#help_data' => [],
    ];
  }

}
