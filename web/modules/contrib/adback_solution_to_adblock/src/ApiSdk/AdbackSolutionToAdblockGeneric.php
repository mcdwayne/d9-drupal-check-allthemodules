<?php

namespace Drupal\adback_solution_to_adblock\ApiSdk;

/**
 * Class AdbackSolutionToAdblockGeneric.
 */
class AdbackSolutionToAdblockGeneric
{
    private static $instance = NULL;

    protected $api;
    protected $connected;

    protected $types = [
        'analytics',
        'message',
        'product',
        'banner',
        'catcher',
        'iab_banner',
    ];

    /**
     * AdBackGeneric constructor.
     */
    private function __construct()
    {
        $token = \Drupal::config('adback_solution_to_adblock.settings')->get('access_token');

        $this->api = new AdbackSolutionToAdblockApi($token);
    }

    /**
     * Use singleton pattern.
     *
     * @return AdbackSolutionToAdblockGeneric
     *     The instance of AdbackSolutionToAdblockGeneric
     */
    static public function getInstance()
    {

        if (self::$instance == NULL) {
            self::$instance = new AdbackSolutionToAdblockGeneric();
        }

        return self::$instance;
    }

    /**
     * Get info from adback site.
     *
     * @return array|bool
     *     Array with all domains.
     */
    public function getScriptInfo($force = false)
    {
        $scripts = [];

        $updateTime = \Drupal::config('adback_solution_to_adblock.settings')->get('update_time');
        if ((time() - 10800) < $updateTime) {
            foreach ($this->types as $type) {
                if ('' != ($script = \Drupal::config('adback_solution_to_adblock.' . $type)->get('script'))) {
                    $scripts[$type] = $script;
                }
            }
        }

        if (empty($scripts) || $force) {
            $fullScripts = $this->api->getFullScripts();
            $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.settings');
            $config->set('update_time', @time());
            $config->save();
            foreach ($this->types as $type) {
                if (
                    is_array($fullScripts['script_codes'])
                    && array_key_exists($type, $fullScripts['script_codes'])
                    && '' !== $fullScripts['script_codes'][$type]['code']
                ) {
                    $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.' . $type);
                    $config->set('script', $fullScripts['script_codes'][$type]['code']);
                    $config->save();
                    $scripts[$type] = $fullScripts['script_codes'][$type]['code'];
                }
            }
            $this->updateEndpoints();
        }

        return $scripts;
    }

    /**
     * Update the endpoints data
     */
    protected function updateEndpoints()
    {
        $endpoints = $this->api->getEndpoints();

        $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.endpoints');
        foreach ($endpoints as $type => $endpoint) {
            $config->set($type, $endpoint);
        }
        $config->save();

        \Drupal::service("router.builder")->rebuild();
    }


    /**
     * Update via adback api with message.
     *
     * @param bool $display
     *     The status of the message.
     *
     * @return mixed|string
     *     The response of the request
     */
    public function updateMessageDisplay($display)
    {
        $fields = [
            "display" => $display
        ];

        return $this->api->setMessageDisplay($fields);
    }

    /**
     * Check if the token is valid.
     *
     * @param object $token
     *     The token.
     *
     * @return bool
     *     If the user is connected
     */
    public function isConnected($token = NULL)
    {
        if (true === \Drupal::config('adback_solution_to_adblock.settings')->get('connected')) {
            $this->connected = true;
        }

        if ($this->connected !== null) {
            return $this->connected;
        }

        if ($token == NULL) {
            $token = $this->getToken();
        }

        if (is_array($token)) {
            $token = (object) $token;
        }

        $this->api->setToken($token->access_token);

        $this->connected = $this->api->isConnected();
        $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.settings');
        $config->set('connected', $this->connected);
        $config->save();

        return $this->connected;
    }

    /**
     * Return token object stored.
     *
     * @return object
     *     The token object
     */
    public function getToken()
    {
        return (object) [
            'access_token' => \Drupal::config('adback_solution_to_adblock.settings')->get('access_token'),
            'refresh_token' => \Drupal::config('adback_solution_to_adblock.settings')->get('refresh_token'),
        ];
    }

    /**
     * Save tokens into db.
     *
     * @param array|null $token
     *     All tokens.
     */
    public function saveToken($token)
    {

        if ($token == NULL || array_key_exists("error", $token)) {
            return;
        }

        $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.settings');
        $config->set('access_token', $token["access_token"]);
        $config->set('refresh_token', $token["refresh_token"]);
        $config->save();

        $this->api->setToken($token["access_token"]);

        $this->api->pluginActivate();
        $this->api->ensureEndpointProxyIsActivated();
    }

    /**
     * Reset all token and domain.
     */
    public function logout()
    {
        $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.settings');
        $config->clear('access_token');
        $config->clear('refresh_token');
        $config->set('connected', false);
        $config->save();

        foreach ($this->types as $type) {
            $config = \Drupal::configFactory()->getEditable('adback_solution_to_adblock.' . $type);
            $config->clear('script');
            $config->save();
        }
    }
}
