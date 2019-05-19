<?php
/**
 * Created by PhpStorm.
 * User: bmcintyre
 * Date: 7/27/18
 * Time: 8:49 AM
 */

namespace Drupal\skyword\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SkywordAuth implements AuthenticationProviderInterface {
    public function applies(Request $request) {
        if( strpos($request->getRequestUri(), 'skyword/v1') !== false ) {
            return TRUE;
        }
        return FALSE;
    }

    public function authenticate(Request $request) {
        $apiKey = \Drupal::configFactory()->get('skyword.skyword_config')->get('apiKey');
        $hash = $request->headers->get("Authentication");
        $timestamp = $request->headers->get("Timestamp");
        if($hash === null)
            throw new AccessDeniedHttpException("Bad request");

        $timeNow = time();
        if($timeNow - $timestamp >= - 20000 &&  $timeNow - $timestamp <= 20000) {
            if($apiKey !== '') {
                $compareHash = md5($apiKey . $timestamp);
                if($compareHash === $hash) {
                    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'skyword']);
                    $user = reset($users);
                    if($user) {
                        return $user;
                    } else {
                        throw new AccessDeniedHttpException("User 'skyword' not found");
                        return null;
                    }
                } else {
                    throw new AccessDeniedHttpException("Could not match hash - " . $apiKey . " - " . $hash . " - " . $timestamp);
                }
            } else {
                throw new AccessDeniedHttpException("Skyword API Key not set");
            }
        } else {
            throw new AccessDeniedHttpException("Bad timestamp used");
            return null;
        }
    }

    public function cleanup(Request $request) {}
}