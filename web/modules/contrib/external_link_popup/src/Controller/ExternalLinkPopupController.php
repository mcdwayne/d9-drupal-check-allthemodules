<?php

namespace Drupal\external_link_popup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\external_link_popup\Entity\ExternalLinkPopup;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller class for the external_link_popup module.
 */
class ExternalLinkPopupController extends ControllerBase {

  /**
   * Enables a ExternalLinkPopup object.
   *
   * @param \Drupal\external_link_popup\Entity\ExternalLinkPopup $external_link_popup
   *   The ExternalLinkPopup object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the ExternalLinkPopup listing page.
   */
  public function enable(ExternalLinkPopup $external_link_popup) {
    $external_link_popup->enable()->save();
    return new RedirectResponse($external_link_popup->url('collection', ['absolute' => TRUE]));
  }

  /**
   * Disables a ExternalLinkPopup object.
   *
   * @param \Drupal\external_link_popup\Entity\ExternalLinkPopup $external_link_popup
   *   The ExternalLinkPopup object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the ExternalLinkPopup listing page.
   */
  public function disable(ExternalLinkPopup $external_link_popup) {
    $external_link_popup->disable()->save();
    return new RedirectResponse($external_link_popup->url('collection', ['absolute' => TRUE]));
  }

}
