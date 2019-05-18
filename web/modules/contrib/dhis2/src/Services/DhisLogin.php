<?php

/**
 * @file
 */

namespace Drupal\dhis\Services;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TooManyRedirectsException;

class DhisLogin implements LoginService
{

    private $header = array();
    private $username;
    private $password;
    private $isSessionAlive = FALSE;
    private $baseUrl;

    public function __construct(ConfigFactory $config_factory)
    {
        $config = $config_factory->getEditable('dhis.settings');
        $this->username = $config->get('dhis.username');
        $this->password = $config->get('dhis.password');
        $this->baseUrl = $config->get('dhis.link');
    }


    public function login($url)
    {
        $client = new Client();
        $response = $client->request('GET', $this->baseUrl . $url, ['auth' => [$this->username, $this->password, 'basic']]);
        return json_decode($response->getBody()->getContents(), true);
    }

    private function setHeaders($username, $password)
    {
        $header = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json';
        return $header;
    }

    private function isSessionAlive()
    {

        return $this->isSessionAlive;
    }
    public function testLogin(array $credentials){
        $this->baseUrl = $credentials['baseUrl'];
        $this->username = $credentials['username'];
        $this->password = $credentials['password'];
        try{
            $this->login('me');
            return TRUE;
        }
        catch(TooManyRedirectsException $e){

            return FALSE;
        }

    }
}