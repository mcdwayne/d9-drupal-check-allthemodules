<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 11/01/2018
 * Time: 12:09
 */

namespace Drupal\twizo\Api;

use Twizo\Api\Exception;
use Twizo\Api\Twizo;

require_once(DRUPAL_ROOT . '/vendor/twizo/lib-api-php/autoload.php');

class TwizoApi {
    private $twizo;
    private $config;
    private $errorMessage;

    /**
     * TwizoApi constructor.
     * @param null $apiKey
     * @param null $apiServer
     */
    public function __construct($apiKey = null, $apiServer = null) {
        if(!isset($apiKey) || !isset($apiServer)) {
            $this->config = \Drupal::config('twizo.adminsettings');
            $this->twizo = Twizo::getInstance($this->config->get('twizo_api_key'), $this->config->get('twizo_api_server'));
        } else {
            $this->twizo = Twizo::getInstance($apiKey, $apiServer);
        }
    }

    /**
     * Check if Twizo api credentials are valid, return TRUE if valid.
     * @return bool
     */
    public function validateApiCredentials(){
        try{
            $this->twizo->verifyCredentials();
            return true;
        } catch (Exception $e){
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getErrorMessage(){
        return $this->errorMessage;
    }

    /**
     * @param $allowedTypes
     * @param $recipient
     * @param null $backupCodeIdentifier
     * @param null $totpIdentifier
     * @param null $issuer
     * @return null|string
     */
    public function createWidgetSession($allowedTypes, $recipient, $preferredMethod = NULL, $backupCodeIdentifier = NULL, $totpIdentifier = NULL, $issuer = NULL){
        $widgetSession = $this->twizo->createWidgetSession($allowedTypes, $recipient, $backupCodeIdentifier, $totpIdentifier);
        try {
            $widgetSession->setTag('Drupal 8');
            isset($preferredMethod) ? $widgetSession->setPreferredType($preferredMethod) : null;
            $widgetSession->create();
        } catch (Exception $e){
            return $e->getMessage();
        }
        return $widgetSession->getSessionToken();
    }

    /**
     * @param $sessionToken
     * @return bool|string
     */
    public function validateWidget($sessionToken){
        try{
            if($this->twizo->getWidgetSession($sessionToken)->getStatus() == 'success'){
                return TRUE;
            } else{
                return FALSE;
            }
        } catch (Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * @return array|null
     * @throws Exception
     */
    public function getVerificationTypes(){
        return $this->twizo->getVerificationTypes()->getVerificationTypes();
    }

    /**
     * @param $identifier
     * @return array|null
     * @throws \Twizo\Api\Entity\Exception
     */
    public function generateBackupCodes($identifier){
        $backupcodes = $this->twizo->createBackupCode($identifier);
        $backupcodes->create();

        return $backupcodes->getCodes();
    }

    /**
     * @param $identifier
     * @return array|null
     * @throws Exception
     * @throws \Twizo\Api\Entity\Exception
     */
    public function updateBackupCodes($identifier){
        $backupcode = $this->twizo->getBackupCode($identifier);
        $backupcode->delete();

        return $this->generateBackupCodes($identifier);
    }

    /**
     * @param $identifier
     * @return int|null
     * @throws Exception
     */
    public function getRemainingBackupCodes($identifier){
        $backupCodes = $this->twizo->getBackupCode($identifier);

        return $backupCodes->getAmountOfCodesLeft();
    }

    /**
     * @param $identifier
     * @return string
     * @throws \Twizo\Api\Entity\Exception
     */
    public function createTotp($identifier){
        $totp = $this->twizo->createTotp($identifier, 'drupal');
        $totp->create();

        return $totp->getUri();
    }

    /**
     * @param $url
     * @return string
     */
    public function getTotpQrUrl($url){
        $encodedUrl = urlencode($url);
        return "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . $encodedUrl . "&choe=UTF-8";
    }

    /**
     * @param $number
     * @return null|string
     * @throws \Twizo\Api\Entity\Exception
     */
    public function createBiovoiceRegistration($number){
        $biovoice = $this->twizo->createBioVoiceRegistration($number);
        try {
            $biovoice->create();
        } catch (\Twizo\Api\Entity\Exception $e) {
            return $e->getMessage();
        }
        return $biovoice->getVoiceSentence();
    }

    /**
     * @param $number
     * @return bool
     */
    public function getBiovoiceRegistrationStatus($number){
        try {
            $biovoice = $this->twizo->getBioVoiceSubscription($number);
            $biovoice->getCreatedDateTime();
            $result = TRUE;
        } catch (Exception $e) {
            $result = FALSE;
        }
        return $result;
    }

    /**
     * @param $number
     * @return string
     */
    public function deleteBiovoice($number){
        try {
            $biovoice = $this->twizo->getBioVoiceSubscription($number);
            $biovoice->delete();
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param $number
     * @return null|string
     * @throws Exception
     */
    public function getBiovoiceSentence($number){
        $biovoice = $this->twizo->getBioVoiceSubscription($number);
        return $biovoice->getVoiceSentence();
    }
}