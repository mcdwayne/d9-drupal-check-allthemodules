<?php

namespace Drupal\drupaneo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class AkeneoService {

    protected $config;

    protected $access_token;

    /**
     * Creates Akeneo API client service.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The config factory.
     */
    public function __construct(ConfigFactoryInterface $config_factory) {
        $this->config = $config_factory->get('drupaneo.settings');
    }

    private function auth() {

        // We are already authenticated

        if (isset($this->access_token)) {
            return;
        }

        $username = $this->config->get('username');
        $password = $this->config->get('password');
        $url = $this->config->get('url');
        $client_id = $this->config->get('client_id');
        $client_secret = $this->config->get('client_secret');

        // Drupaneo is not configured

        if (empty($username) || empty($password) || empty($client_id) || empty($client_secret) || empty($url)) {
            throw new \Exception(t('Please configure Drupaneo first...'));
        }

        $data = array("grant_type" => "password", "username" => $username, "password" => $password);
        $auth = base64_encode("$client_id:$client_secret");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url/api/oauth/v1/token");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Basic $auth",
            "Content-Type: application/json",
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        // Something bad happened

        if (is_null($result)) {
            throw new \Exception(t('Unable to authenticate against Akeneo...'));
        }
        else if (isset($result->code) && $result->code !== 200) {
            throw new \Exception($result->message);
        }
        $this->access_token = $result->access_token;
    }

    protected function query($method, $uri, $page, $limit, $with_count, $params = array()) {
        $this->auth();

        $url = $this->config->get('url');
        $url = "$url$uri?with_count=$with_count&limit=$limit&page=$page";

        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $url .= "&$key=$value";
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $this->access_token",
            "Content-Type: application/json",
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        return $result;
    }

    public function getProducts($page, $limit, $with_count) {
        return $this->query('GET', '/api/rest/v1/products', $page, $limit, $with_count, array('scope' => $this->config->get('scope')));
    }

    /**
     * Get a list of channels
     *
     * @param $page
     * @param $limit
     * @param $with_count
     * @return mixed
     */
    public function getChannels($page, $limit, $with_count) {
        return $this->query('GET', '/api/rest/v1/channels', $page, $limit, $with_count);
    }
}