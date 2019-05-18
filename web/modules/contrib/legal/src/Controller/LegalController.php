<?php

namespace Drupal\legal\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class LegalController.
 *
 * @package Drupal\legal\Controller
 */
class LegalController extends ControllerBase {

  /**
   * Page callback.
   *
   * @return array
   *   Render array of terms and conditions.
   */
  public function legalPageAction() {

    $language   = $this->languageManager()->getCurrentLanguage();
    $conditions = legal_get_conditions($language->getId());
    $output     = '';

    switch ($this->config('legal.settings')->get('registration_terms_style')) {
      // Scroll Box.
      case 0:
        $output = nl2br(strip_tags($conditions['conditions']));
        break;

      // CSS Scroll Box with HTML.
      case 1:

        // HTML.
      case 2:

        // Page Link.
      case 3:
        $output = Xss::filterAdmin($conditions['conditions']);
        break;
    }
    $build = [
      '#type'   => 'markup',
      '#markup' => $output,
    ];

    return $build;
  }

}
