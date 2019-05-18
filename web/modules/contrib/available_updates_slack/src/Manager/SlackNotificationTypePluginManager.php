<?php

namespace Drupal\available_updates_slack\Manager;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class SlackNotificationTypePluginManager extends DefaultPluginManager {

    /**
     * {@inheritdoc}
     */
    public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
        parent::__construct(
            'Plugin/slack_notification/type',
            $namespaces,
            $module_handler,
            'Drupal\available_updates_slack\SlackNotificationTypeInterface',
            'Drupal\available_updates_slack\Annotation\SlackNotificationType'
        );

        $this->alterInfo('slack_notification_type');

        $this->setCacheBackend($cache_backend, 'slack_notification_type_plugins');
    }

    public function getIdLabelMapping(){
        $notification_types = [];
        $definitions = $this->getDefinitions();
        foreach($definitions as $key => $value) {
            if ($value['enabled']) {
                $notification_types[$key] = $value['label']->render();
            }
        }
        return $notification_types;
    }
}