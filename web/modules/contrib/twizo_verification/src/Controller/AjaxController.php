<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 15/01/2018
 * Time: 17:32
 */

namespace Drupal\twizo\Controller;

use Drupal\twizo\Api\TwizoApi;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxController {

    // TODO Refactor
    public function validateWidget(){
        try {
            $response = new JsonResponse();

            $userData = \Drupal::service('user.data');
            // Get uid
            $uid = \Drupal::currentUser()->id();
            $number = $_POST['number'];
            $sessionToken = $_POST['sessionToken'];

            $twizo = new TwizoApi();
            $identifier = md5(uniqid($uid, true));
            $totpIdentifier = md5(uniqid($uid, true));
            $biovoiceIdentifier = md5(uniqid($uid, true));


            if ($twizo->validateWidget($sessionToken)) {
                // Save data if validation success.
                $userData->set('twizo', $uid, 'number', $number);
                $userData->set('twizo', $uid, 'preferredMethod', 'sms');
                $userData->set('twizo', $uid, 'identifier', $identifier);
                $userData->set('twizo', $uid, 'totpIdentifier', $totpIdentifier);
                $userData->set('twizo', $uid, 'biovoiceIdentifier', $biovoiceIdentifier);
                $userData->set('twizo', $uid, 'codesGenerated', 0);
                $userData->set('twizo', $uid, 'biovoiceRegistered', 0);
                $userData->set('twizo', $uid, 'widgetEnabled', 1);

                // Register TOTP
                $totpUrl = $twizo->createTotp($totpIdentifier);
                $userData->set('twizo', $uid, 'totpUrl', $totpUrl);


                $response->setData(NULL);
                //$response->addCommand( new InvokeCommand(null, 'testAlert', [$number]));
                return $response;
            } else {
                $response->setData('Validation failed');
                return $response;
            }
        } catch (\Exception $e){
            $response->setData($e->getMessage());
            return $response;
        }
    }

    public function validateLogin(){

    }
}