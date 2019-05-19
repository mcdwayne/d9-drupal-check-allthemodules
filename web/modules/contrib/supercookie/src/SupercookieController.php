<?php
namespace Drupal\supercookie;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller routines for Supercookie resources.
 */
class SupercookieController implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Menu callback for /supercookie XHR.
   */
  public function render(Request $request) {
    return $this->container
      ->get('supercookie.response')
      ->getResponse();
  }

  /**
   * Menu callback for /admin/reports/supercookie JSON.
   */
  public function report() {
    return $this->container
      ->get('supercookie.response')
      ->getReport();
  }

}
