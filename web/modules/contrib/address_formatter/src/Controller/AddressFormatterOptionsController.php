<?php

namespace Drupal\address_formatter\Controller;

use Drupal\address_formatter\Entity\AddressFormatter;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller class for the address_formatter module.
 */
class AddressFormatterOptionsController extends ControllerBase {

  /**
   * Enables an AddressFormatter object.
   *
   * @param \Drupal\address_formatter\Entity\AddressFormatter $formatter
   *   The AddressFormatter object to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the AddressFormatter options listing page.
   *
   * @throws \Exception
   *   Exception.
   */
  public function enable(AddressFormatter $formatter) {
    $formatter->enable()->save();
    return new RedirectResponse($formatter->url('collection', ['absolute' => TRUE]));
  }

  /**
   * Disables an AddressFormatter object.
   *
   * @param \Drupal\address_formatter\Entity\AddressFormatter $formatter
   *   The AddressFormatter object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the AddressFormatter options listing page.
   *
   * @throws \Exception
   *   Exception.
   */
  public function disable(AddressFormatter $formatter) {
    $formatter->disable()->save();
    return new RedirectResponse($formatter->url('collection', ['absolute' => TRUE]));
  }

}
