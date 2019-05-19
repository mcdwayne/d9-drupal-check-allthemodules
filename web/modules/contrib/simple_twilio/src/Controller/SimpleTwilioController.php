<?php

namespace Drupal\simple_twilio\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for simple_twilio pages.
 *
 * @ingroup simple_twilio
 */
class SimpleTwilioController extends ControllerBase {

  /**
   * Renders the form for the Mobile number config.
   */
  public function simpleTwilioPage() {
    $build = [];
    $build['simple_twilio_form'] = $this->formBuilder()->getForm('Drupal\simple_twilio\Form\SimpleTwilioPageForm');
    return $build;
  }

}
