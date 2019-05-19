<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 24/01/2018
 * Time: 16:17
 */

namespace Drupal\twizo\Controller;


use Drupal\Component\Utility\Crypt;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twizo\Api\TwizoApi;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserLoginController{

    /**
     * @param $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function validateLogin(&$form, FormStateInterface $form_state){
        $response = new AjaxResponse();

        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');

        $twizo = new TwizoApi();

        $config = \Drupal::config('twizo.adminsettings');

        /** @var UserAuthInterface $uid */
        $uid = \Drupal::service('user.auth')->authenticate($form_state->getValue('name'), $form_state->getValue('pass'));

        if($uid != 0){
            $userEnabled = $userData->get('twizo', $uid, 'widgetEnabled');
            $adminEnabled = $config->get('twizo_enable_2fa');
            $identifier = $userData->get('twizo', $uid, 'identifier');
            $numberValidated = ($userData->get('twizo', $uid, 'number') != NULL) ? TRUE : FALSE;
            $totpIdentifier = $userData->get('twizo', $uid, 'totpIdentifier');

            $token = $_COOKIE['twizo_user_cookie'];
            $hashedVersion = hash('sha512', sprintf('%s_%s', $identifier, $token));

            if($hashedVersion == $userData->get('twizo', $uid, 'cookieHash' . $token)){
                $isTrusted = TRUE;
            } else{
                $isTrusted = FALSE;
            }

            // Logs user in if 2fa is disabled by admin or user and check for trustedDevice
            if(!$userEnabled || !$adminEnabled || !$numberValidated || $isTrusted){
                $user = User::load($uid);
                user_login_finalize($user);

                $response->addCommand(new InvokeCommand(NULL, 'reload'));

                return $response;
            } else {
                $number = $userData->get('twizo', $uid, 'number');
                $preferredMethod = $userData->get('twizo', $uid, 'preferredMethod');

                $sessionToken = $twizo->createWidgetSession(NULL, $number, $preferredMethod, $identifier, $totpIdentifier);
                //user_login_finalize($user);
                $response->addCommand(new InvokeCommand(NULL, 'validateLogin', [
                    $sessionToken,
                    $form_state->getValue('name'),
                    $form_state->getValue('pass'),
                    $config->get('widget_logo')]));

                return $response;
            }
        } else {
            $response->addCommand(new InvokeCommand(NULL, 'reload'));
            return $response;
        }
    }

    public function finalizeLogin(){
        $response = new JsonResponse();
        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');

        $sessionToken = $_POST['sessionToken'];
        $name = $_POST['name'];
        $pass = $_POST['pass'];
        $isTrusted = $_POST['isTrusted'] === 'true' ? TRUE : FALSE;

        /** @var UserAuthInterface $auth */
        $uid = \Drupal::service('user.auth')->authenticate($name, $pass);

        $twizo = new TwizoApi();

        // Check if user is correct
        if(isset($uid)){
            if($twizo->validateWidget($sessionToken)){
                if($isTrusted){
                    $identifier = $userData->get('twizo', $uid, 'identifier');
                    $token = bin2hex(Crypt::randomBytes(30));
                    $hashedVersion = hash('sha512', sprintf('%s_%s', $identifier, $token));
                    $userData->set('twizo', $uid, 'cookieHash' . $token, $hashedVersion);
                    setcookie('twizo_user_cookie', $token, time() + (86400 * 30), '/');
                }
                $user = User::load($uid);

                user_login_finalize($user);

                $response->setData(NULL);

                return $response;
            }
        }
    }
}