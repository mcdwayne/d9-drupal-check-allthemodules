<?php
/**
 * -----------------------------------------------------------------------
 * vBSSO is a solution which helps you connect to different software platforms
 * via secure Single Sign-On.
 *
 * Copyright (c) 2011-2017 vBSSO. All Rights Reserved.
 * This software is the proprietary information of vBSSO.
 *
 * Author URI: http://www.vbsso.com
 * License: GPL version 2 or later -
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------
 */

namespace Drupal\vbsso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\field\Url;

/**
 * Class VbssoMainController
 *
 * @package Drupal\vbsso\Controller
 */
class VbssoMainController extends ControllerBase {

    const VERSION = '1.1.0';

    /**
     * Single entry point to slave
     *
     * @return void
     */
    public function init() {
        $options = $this->config('config.' . VBSSO_PRODUCT_ID);
        sharedapi_data_handler(
            SHAREDAPI_PLATFORM_DRUPAL, \Drupal::VERSION, VbssoMainController::VERSION,
            $options->get(VBSSO_NAMED_EVENT_FIELD_API_KEY),
            array(
                SHAREDAPI_EVENT_VERIFY => 'vbsso_listener_verify',
                SHAREDAPI_EVENT_LOGIN => 'vbsso_listener_register',
                SHAREDAPI_EVENT_AUTHENTICATION => 'vbsso_listener_authentication',
                SHAREDAPI_EVENT_LOGOUT => 'vbsso_listener_logout',
                SHAREDAPI_EVENT_REGISTER => 'vbsso_listener_register',
                SHAREDAPI_EVENT_CREDENTIALS => 'vbsso_listener_credentials',
            )
        );
    }

    /**
     * Override system route for registration new user
     *
     * @return TrustedRedirectResponse
     */
    public function registerRoute() {
        $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_REGISTER_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));

        if (empty($url)) {
            $url = $this->getFrontPage();
        }
        
        return new TrustedRedirectResponse($url);
    }

    /**
     * Override system route for lost password
     *
     * @return TrustedRedirectResponse
     */
    public function lostPasswordRoute() {
        $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOSTPASSWORD_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));

        if (empty($url)) {
            $url = $this->getFrontPage();
        }
        
        return new TrustedRedirectResponse($url);
    }

    /**
     * Override system route for logout
     *
     * @return TrustedRedirectResponse
     */
    public function logoutRoute() {

        $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL, null), true, '', variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));

        if (preg_match('/(\/user\/\d)/i', urldecode($url))) {
            $newUrl = new \Drupal\Core\Url('<front>', array(), array('absolute' => true));
            $url = sharedapi_url_add_destination(variable_get(VBSSO_NAMED_EVENT_FIELD_LOGOUT_URL, null), false, $newUrl->toString(), variable_get(VBSSO_NAMED_EVENT_FIELD_LID, null));
        }

        if (empty($url)) {
            $url = $this->getFrontPage();
        }
        
        $redirect =  new TrustedRedirectResponse($url);
        $redirect->send();
    }

    /**
     * Override system route for view profile
     *
     * @return TrustedRedirectResponse
     */
    public function viewProfileRoute() {
        $user = \Drupal::currentUser();
        $url = variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, '') . md5($user->getEmail());

        if (empty($url)) {
            $url = $this->getFrontPage();
        }
        
        return new TrustedRedirectResponse($url);
    }

    /**
     * Override system route for view/edit profile
     *
     * @return TrustedRedirectResponse
     */
    public function editProfileRoute() {
        $url = variable_get(VBSSO_NAMED_EVENT_FIELD_PROFILE_URL, '');
        $currentUser = \Drupal::currentUser();
        $path = \Drupal::request()->getpathInfo();
        $args = explode('/', $path);
        $user = User::load($args[2]);

        if ($currentUser->id() !== $user->id()) {
            $url .= md5(strtolower($user->getEmail()));
        }

        if (empty($url)) {
            $url = $this->getFrontPage();
        }
        
        $redirect = new TrustedRedirectResponse($url);
        $redirect->send();
    }

    /**
     * Get front page url
     * 
     * @return string
     */
    protected function getFrontPage() {
        $front = new \Drupal\Core\Url('<front>', array(), array('absolute' => true));
        return $front->toString();
    }
}
