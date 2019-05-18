<?php

namespace Drupal\available_updates_slack;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\available_updates_slack\Manager\SlackNotificationTypePluginManager;
use Drupal\available_updates_slack\SlackNotificationTypeInterface;
use Drupal\available_updates_slack\Exception\UndefinedTypeException;


class SlackNotificationService implements SlackNotificationServiceInterface {

    /**
     * @var Client
     */
    private $http_client;

    /**
     * @var ConfigFactoryInterface
     */
    private $config_factory;

    /**
     * @var SlackNotificationTypePluginManager
     */
    private $plugin_manager;

    /**
     * SlackNotificationService Constructor
     *
     * @param SlackNotificationTypePluginManager $plugin_manager
     * @param ConfigFactoryInterface $config
     * @param Client $http_client
     */
    public function __construct(SlackNotificationTypePluginManager $plugin_manager, ConfigFactoryInterface $config, Client $http_client) {
        $this->http_client = $http_client;
        $this->config_factory = $config;
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdates(string $type = null) {
        $modules = [];
        if (empty($type)) {
            $type = $this->getNotificationType();
        }
        update_refresh();
        $available = update_get_available(true);
        $updates = update_calculate_project_data($available);
        /** @var SlackNotificationTypeInterface $obj */
        if ($obj = $this->getPluginById($type)) {
            $modules = $obj->filterUpdates($updates);
        }

        return $modules;
    }

    /**
     * {@inheritdoc}
     */
    public function notify(array $modules, string $type = null){
        /** @var SlackNotificationTypeInterface $plugin_type */
        $plugin_type = $this->getPluginById($this->getNotificationType());
        $this->http_client->post($this->getWebhookUrl(), [
            'json' => $plugin_type->buildMessage($modules),
            'headers' => [
                'Content-type' => 'Content-type: application/json'
            ]
        ]);
    }

    /**
     * Returns the webhook URL
     *
     * @return string
     */
    private function getWebhookUrl() {
        return $this->getAvailableUpdatesSlackSettingValue('webhook_url') ?? '';
    }

    /**
     * Get the plugin type object instance
     *
     * @param string $type the plugin type
     * @return SlackNotificationTypeInterface
     *
     * @throws UndefinedTypeException
     */
    private function getPluginById(string $type) {
        if ($this->plugin_manager->getDefinition($type, false)) {
            return $this->plugin_manager->createInstance($type);
        }

        throw new UndefinedTypeException("The type '$type' is not defined.");
    }

    /**
     * Get the default notification type
     *
     * @return string
     */
    private function getNotificationType(){
        return $this->getAvailableUpdatesSlackSettingValue('notification_type') ?? '';
    }

    /**
     * Get the value of a settings name on the available_updates_slack.update
     *
     * @param string $settings_name the setting name
     * @return string
     */
    private function getAvailableUpdatesSlackSettingValue(string $settings_name) {
        return $this->config_factory->get('available_updates_slack.settings')->get($settings_name);
    }
}
