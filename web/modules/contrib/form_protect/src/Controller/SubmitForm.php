<?php

/**
 * @file
 * Contains \Drupal\form_protect\Controller\SubmitForm.
 */

namespace Drupal\form_protect\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;

class SubmitForm extends ControllerBase {

  /**
   * Provides the content callback for form_protect.submit route.
   *
   * @return array
   *   A renderable array.
   */
  public function content() {
    if ($this->config('form_protect.settings')->get('log')) {
      // Log the blocked submission.
      $post = '<pre>' . print_r($_POST, TRUE) . '</pre>';
      \Drupal::logger('form_protect')->notice("Blocked submission. Post data:$post");
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->getStringTranslation()->translate('JavaScript is not enabled in your browser. This form requires JavaScript to be enabled.'),
      '#cache' => ['max-age' => Cache::PERMANENT],
    ];
  }

  /**
   * Provides the access callback for form_protect.submit route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access() {
    // This page is available only via POST.
    if (\Drupal::request()->getMethod() != 'POST') {
      return AccessResult::forbidden();
    }
    // Quick check if this is a Drupal form.
    if (empty($_POST['form_id']) || empty($_POST['form_build_id'])) {
      return AccessResult::forbidden();
    }
    // The form ID should be in the list of protected forms.
    if (!in_array($_POST['form_id'], $this->config('form_protect.settings')->get('form_ids'))) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
