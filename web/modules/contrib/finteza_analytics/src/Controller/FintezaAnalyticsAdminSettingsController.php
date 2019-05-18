<?php

namespace Drupal\finteza_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for admin settings page.
 */
class FintezaAnalyticsAdminSettingsController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function adminPage() {

    $output = '';

    // Attach finteza_analytics.admin.css.
    $page = [
      '#markup' => $output,
      '#attached' => [
        'library' => [
          'finteza_analytics/admin_page',
        ],
      ],
    ];

    $page['tracking_settings'] = $this->formBuilder()->getForm('Drupal\finteza_analytics\Form\FintezaAnalyticsTrackingSettingsForm');
    $page['registration'] = $this->formBuilder()->getForm('Drupal\finteza_analytics\Form\FintezaAnalyticsRegistrationForm');

    return $page;
  }

}
