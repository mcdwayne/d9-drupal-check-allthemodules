<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 24/01/2018
 * Time: 12:16
 */

namespace Drupal\twizo\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\twizo\Api\TwizoApi;
use Drupal\user\UserDataInterface;
use Twizo\Api\Exception;

class UserAccountController {

    public function __construct(){
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array|AjaxResponse|null
     */
    public function saveChanges(array &$form, FormStateInterface $form_state){
        // Get uid
        $uid = \Drupal::currentUser()->id();
        $twizo = new TwizoApi();
        $config = \Drupal::config('twizo.adminsettings');

        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');

        // Change number
        if($form_state->getValue('number') != $userData->get('twizo', $uid, 'number')){
            $values = $form_state->getValues();
            $sessionToken = $twizo->createWidgetSession(NULL, $values['number']);

            $ajaxResponse = new AjaxResponse();
            $ajaxResponse->addCommand(new InvokeCommand(NULL, 'openWidget', [
                $sessionToken,
                $values['number'],
                $config->get('widget_logo')
            ]));

            return $ajaxResponse;
        } else {

            try {
                $types = $twizo->getVerificationTypes();
            } catch (Exception $e) {
                return drupal_set_message($e->getMessage());
            }

            $prefMethod = $types[$form_state->getValue('preferredMethod')];

            $userData->set('twizo', $uid, 'preferredMethod', $prefMethod);
            $userData->set('twizo', $uid, 'widgetEnabled', $form_state->getValue('widgetEnabled'));
            $response = new AjaxResponse();
            return $response->addCommand(new InvokeCommand(NULL, 'reload'));
        }
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function enableTfa(array &$form, FormStateInterface $form_state){
        // Get uid
        $uid = \Drupal::currentUser()->id();
        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');

        $userData->set('twizo', $uid, 'widgetEnabled', $form_state->getValue('widgetEnabled'));

        $response = new AjaxResponse();

        return $response->addCommand(new InvokeCommand(NULL, 'reload'));
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function validateNumber(array &$form, FormStateInterface $form_state){
        $values = $form_state->getValues();
        $number = $values['number'];

        $twizo = new TwizoApi();
        $sessionToken = $twizo->createWidgetSession(['sms', 'call'], $values['number']);
        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->addCommand(new InvokeCommand(NULL, 'openWidget', [
            $sessionToken,
            $number
        ]));

        return $ajaxResponse;
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function generateBackupCodes(array &$form, FormStateInterface $form_state){
        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');
        $uid = \Drupal::currentUser()->id();
        $twizo = new TwizoApi();

        $identifier = $userData->get('twizo', $uid, 'identifier');

        $backupcodes = $twizo->generateBackupCodes($identifier);

        $userData->set('twizo', $uid, 'codesGenerated', 1);

        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->addCommand(new InvokeCommand(NULL, 'showCodes', [$backupcodes]));

        return $ajaxResponse;
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     */
    public function updateBackupCodes(array &$form, FormStateInterface $form_state){
        /** @var UserDataInterface $userData */
        $userData = \Drupal::service('user.data');
        $uid = \Drupal::currentUser()->id();
        $twizo = new TwizoApi();

        $identifier = $userData->get('twizo', $uid, 'identifier');

        try {
            $backupcodes = $twizo->updateBackupCodes($identifier);
        } catch (Exception $e) {
            return drupal_set_message($e->getMessage());
        }

        $ajaxResponse = new AjaxResponse();
        $ajaxResponse->addCommand(new InvokeCommand(NULL, 'showCodes', [$backupcodes]));

        return $ajaxResponse;
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return AjaxResponse
     * @throws \Twizo\Api\Entity\Exception
     */
    public function registerBiovoice(array &$form, FormStateInterface $form_state){
        $ajaxResponse = new AjaxResponse();
        $userData = \Drupal::service('user.data');
        $uid = \Drupal::currentUser()->id();
        $twizo = new TwizoApi();
        $number = $userData->get('twizo', $uid, 'number');
        //drupal_set_message($twizo->getBiovoiceRegistrationStatus($number));
        if(!$twizo->getBiovoiceRegistrationStatus($number)){
            $twizo->deleteBiovoice($number);
        }



        try {
            drupal_set_message('A call will be made to complete your biovoice registration. Your sentence is: "' . $twizo->createBiovoiceRegistration($number) . '".');
        } catch (\Twizo\Api\Entity\Exception $e) {
            return $ajaxResponse->addCommand(new InvokeCommand(NULL, 'testAlert', [$e->getMessage()]));
        }
        $userData->set('twizo', $uid, 'biovoiceRegistered', 1);


        $ajaxResponse->addCommand(new InvokeCommand(NULL, 'reload'));

        return $ajaxResponse;
    }
}