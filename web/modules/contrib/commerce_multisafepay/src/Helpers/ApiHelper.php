<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */

namespace Drupal\commerce_multisafepay\Helpers;

use Drupal\commerce_multisafepay\API\Client;

class ApiHelper
{
    /**
     * Set the api settings
     *
     * @param Client $client
     * @param $mode
     */
    public function setApiSettings(Client $client, $mode){

        //Get the needed Data to set the setting
        $testApiKey = \Drupal::config('commerce_multisafepay.settings')->getRawData()['test_api_key'];
        $liveApiKey = \Drupal::config('commerce_multisafepay.settings')->getRawData()['live_api_key'];

        //check if the gateway is N/A
        if($mode === "n/a") {
            $mode = \Drupal::config('commerce_multisafepay.settings')->getRawData()['account_type'];
        }

        //Check if the account type is set to Test Or live
        if($mode === "live"){
            //set Live URL
            $client->setApiUrl('https://api.multisafepay.com/v1/json/');
            //set the API key
            $client->setApiKey($liveApiKey);
        }else if($mode === 'test'){
            //set Test URL
            $client->setApiUrl('https://testapi.multisafepay.com/v1/json/');
            //set the API key
            $client->setApiKey($testApiKey);
        }
    }
}