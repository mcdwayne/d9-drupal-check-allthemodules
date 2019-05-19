<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 10/01/2018
 * Time: 17:09
 */

namespace Drupal\twizo\General;

use Drupal\Component\Serialization\Json;
use Exception;

class TwizoInfo {
    public function parseJson(){
        $uri = 'https://cdn.twizo.com/information.json';
        try{
            $response = \Drupal::httpClient()->get($uri, ['headers' => ['Accept' => 'application/json']]);
            $data = (string) $response->getBody();
            return Json::decode($data);
        } catch (Exception $e){
            return $e;
        }
    }

    public function getApiServers(){
        $twizoInfo = $this->parseJson();
        $hosts = [];

        foreach ($twizoInfo['hosts'] as $key){
            $hosts[$key['host']] = $key['location'];
        }

        return $hosts;
    }
}