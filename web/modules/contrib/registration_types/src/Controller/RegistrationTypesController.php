<?php

namespace Drupal\registration_types\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\registration_types\Entity\RegistrationType;
use Drupal\registration_types\Entity\RegistrationTypeInterface;

/**
 * Class RegistrationTypesController.
 *
 * @package Drupal\registration_types\Controller
 */
class RegistrationTypesController extends ControllerBase {

  /**
   * Page.
   *
   * @return string
   *   Return Hello string.
   */
  public function page($registration_type_id) {
    // @todo: inject entityTypeManager service
    $account = \Drupal::entityTypeManager()->getStorage('user')->create([]);

    $registration_type = RegistrationType::load($registration_type_id);
    $form_display_mode = RegistrationTypeInterface::DISPLAY_MODE_PREFIX . $registration_type_id;

    // add registration type to detect the form in hook_form_alter() and other places
    $form_state_additions = ['registration_type' => $registration_type_id];
    $form = $this->entityFormBuilder()->getForm($account, $form_display_mode, $form_state_additions);
    return $form;
  }

}
