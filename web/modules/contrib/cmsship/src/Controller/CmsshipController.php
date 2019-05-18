<?php

namespace Drupal\cmsship\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CmsshipController extends ControllerBase {
    /**
     * Validate the token with cmsship server.
     *
     * @param  string  $token cmsship token
     * @return boolean
     */
    public function validate_token($token) {
        $client = \Drupal::httpClient();

        $config = \Drupal::service('config.factory')->getEditable('cmsship.settings');

        try {
            $request = $client->request('GET', 'https://cmsship.com/api/token/' . $token, array(
                'headers' => array(
                    'X-cmsship-Token' => $config->get('cmsship_key')
                )
            ));
        } catch(\Exception $e) {
            return false;
        }

        // If the status code is 200 the token is valid
        if ($request->getStatusCode() == '200') {
            return true;
        }

        return false;
    }

    /**
     * Handle cmsship login.
     */
    public function login() {
        $config = \Drupal::service('config.factory')->getEditable('cmsship.settings');

        if ( ! empty($_GET['token']) && $this->validate_token($_GET['token'])) {
            // Below is taken from the normal Drupal login functions
            $user = \Drupal\user\Entity\User::load($config->get('cmsship_account'));
            user_login_finalize($user);

            \Drupal::logger('cmsship')->notice(
                'User ' . $user->getUsername() . ' logged in via cmsship with IP of ' . \Drupal::request()->getClientIp() . '.'
            );

            drupal_set_message(t('Successfully logged in via cmsship.'));

            return new RedirectResponse('/admin');
        } else {
            drupal_set_message(t('Could not log you in via cmsship, check watchdog.'), 'error');

            return new RedirectResponse('/');
        }
    }
}
