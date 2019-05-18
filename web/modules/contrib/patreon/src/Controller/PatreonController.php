<?php

namespace Drupal\patreon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\patreon\PatreonGeneralException;
use Drupal\patreon\PatreonUnauthorizedException;

/**
 * Class PatreonController.
 *
 * @package Drupal\patreon\Controller
 */
class PatreonController extends ControllerBase {

  /**
   * Patreon oauth callback.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the Patreon settings form.
   */
  public function oauthCallback() {
    if (isset($_GET['code']) && $code = $_GET['code']) {
      $service = \Drupal::service('patreon.api');

      try {
        $tokens = $service->tokensFromCode($code);
        $service->storeTokens($tokens);
        $service->bridge->setToken($tokens['access_token']);

        if ($return = $service->fetchUser()) {
          if ($user = $service->bridge->getValueByKey($return, 'data')) {
            if ($id = $service->bridge->getValueByKey($user, 'id')) {
              $config = \Drupal::service('config.factory')
                ->getEditable('patreon.settings');
              $config->set('patreon_creator_id', $id)->save();
            }
          }
        }
      }
      catch (PatreonUnauthorizedException $e) {
        $message = $this->t('The Patreon API returned the following error: :error', array(
          ':error' => $e->getMessage(),
        ));
        \Drupal::logger('patreon')->error($message);
        drupal_set_message($message, 'error');
      }
      catch (PatreonGeneralException $e) {
        $message = $this->t('The Patreon API returned the following error: :error', array(
          ':error' => $e->getMessage(),
        ));
        \Drupal::logger('patreon')->error($message);
        drupal_set_message($message, 'error');
      }
    }

    return $this->redirect('patreon.settings_form');
  }

}
