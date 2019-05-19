<?php

namespace Drupal\vib\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\vib\Entity\VibLink;
use Drupal\vib\Entity\VibLinkInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for View in browser.
 */
class VibController extends ControllerBase {

  /**
   * Provides browser variant of the email.
   *
   * @param string $id
   *   The vib_link entity id.
   * @param string $token
   *   Security token.
   *
   * @return array
   *   The page content render array.
   */
  public function page($id, $token) {
    $vib_link = VibLink::load($id);
    if ($vib_link instanceof VibLinkInterface) {
      $vib_token = $vib_link->getToken();

      if ($vib_token === $token) {
        $body = $vib_link->getEmailContent();
        $library = $vib_link->getlibrary();

        $build = [];

        if ($library) {
          $build['#attached']['library'] = $library;
        }

        // Use this method of render array to prevent stripping of styles.
        $build['#children'] = $body;

        return $build;
      }
    }

    throw new AccessDeniedHttpException();
  }

}
