<?php
namespace Drupal\getresponse\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class GetresponseController
 * @package Drupal\getresponse\Controller
 */
class GetresponseController extends ControllerBase {

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function content() {
    return new RedirectResponse(Url::fromRoute('getresponse.settings_form')
      ->toString());
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function disconnect() {
    \Drupal::configFactory()->getEditable('getresponse.settings')->delete();

    drupal_set_message($this->t('You disconnected your Drupal from GetResponse.'));

    return new RedirectResponse(Url::fromRoute('getresponse.settings_form')
      ->toString());
  }
}