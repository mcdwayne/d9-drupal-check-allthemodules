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
namespace Drupal\vbsso\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class VbssoRouteController override system routes
 *
 * @package Drupal\vbsso\Routing
 */
class VbssoRouteController extends RouteSubscriberBase {

    /**
     * Override system routes method
     * 
     * @param RouteCollection $collection collection of routes
     *
     * @return void
     */
    protected function alterRoutes(RouteCollection $collection) {

        if (variable_get(VBSSO_NAMED_EVENT_FIELD_LOGIN_URL, '')) {
            if ($route = $collection->get('user.register')) {
                $route->setDefaults(array(
                    '_controller' => '\Drupal\vbsso\Controller\VbssoMainController::registerRoute',
                ));
            }

            if ($route = $collection->get('user.pass')) {
                $route->setDefaults(array(
                    '_controller' => '\Drupal\vbsso\Controller\VbssoMainController::lostPasswordRoute',
                ));
            }

            if ($route = $collection->get('user.logout')) {
                $route->setDefaults(array(
                    '_controller' => '\Drupal\vbsso\Controller\VbssoMainController::logoutRoute',
                ));
            }


            /* View profile */
            if (variable_get(VBSSO_NAMED_EVENT_FIELD_SHOW_VBULLETIN_PROFILE, null)) {
                if ($route = $collection->get('user.page')) {
                    $route->setDefaults(array(
                        '_controller' => '\Drupal\vbsso\Controller\VbssoMainController::viewProfileRoute',
                    ));
                }

                if ($route = $collection->get('entity.user.canonical')) {
                    $route->setPath('/');
                }

            }

            /* Edit profile */
            if (variable_get(VBSSO_NAMED_EVENT_FIELD_EDIT_PROFILE_IN_VBULLETIN, null)) {
                if ($route = $collection->get('entity.user.edit_form')) {
                    $route->setDefaults(array(
                        '_entity_form' => 'user.default',
                        '_controller' => '\Drupal\vbsso\Controller\VbssoMainController::editProfileRoute',
                    ));
                }
            }
        }
    }
}
