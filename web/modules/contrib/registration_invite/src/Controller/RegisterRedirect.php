<?php

namespace Drupal\registration_invite\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class RegisterRedirect.
 */
class RegisterRedirect extends ControllerBase {

  /**
   * Display the markup.
   */
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('Sorry!! Registrations are closed!<br/>
             New registrations are now only allowed through invitations.'),
    );
  }

}
