<?php

namespace Drupal\clever_reach\Component\Utility;

use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Proxy;
use CleverReach\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeUpInterface;
use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\TaskRunner;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatusStorage;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerWakeup;
use CleverReach\Infrastructure\Utility\GuidProvider;
use CleverReach\Infrastructure\Utility\TimeProvider;
use Drupal\clever_reach\Component\BusinessLogic\AttributesService;
use Drupal\clever_reach\Component\BusinessLogic\RecipientService;
use Drupal\clever_reach\Component\Infrastructure\AsyncProcessStarterService;
use Drupal\clever_reach\Component\Infrastructure\ConfigRepository;
use Drupal\clever_reach\Component\Infrastructure\ConfigService;
use Drupal\clever_reach\Component\Infrastructure\HttpClientService;
use Drupal\clever_reach\Component\Infrastructure\LoggerService;
use Drupal\clever_reach\Component\Infrastructure\TaskQueueStorageService;
use InvalidArgumentException;

/**
 * Service Registry for CleverReach core.
 */
class Initializer {

  /**
   * Registers all services.
   */
  public static function registerServices() {
    try {
      ServiceRegister::registerService(
        TimeProvider::CLASS_NAME,
        function () {
            return new TimeProvider();
        }
      );
      ServiceRegister::registerService(
        Queue::CLASS_NAME,
        function () {
            return new Queue();
        }
      );
      ServiceRegister::registerService(
        ProxyInterface::CLASS_NAME,
        function () {
            return new Proxy();
        }
      );
      ServiceRegister::registerService(
        TaskRunnerWakeUpInterface::CLASS_NAME,
        function () {
            return new TaskRunnerWakeup();
        }
      );
      ServiceRegister::registerService(
        TaskRunner::CLASS_NAME,
        function () {
            return new TaskRunner();
        }
      );
      ServiceRegister::registerService(
        GuidProvider::CLASS_NAME,
        function () {
            return new GuidProvider();
        }
      );
      ServiceRegister::registerService(
        DefaultLoggerAdapter::CLASS_NAME,
        function () {
            return new DefaultLogger();
        }
      );
      ServiceRegister::registerService(
        TaskRunnerStatusStorageInterface::CLASS_NAME,
        function () {
            return new TaskRunnerStatusStorage();
        }
      );
      ServiceRegister::registerService(
        ShopLoggerAdapter::CLASS_NAME,
        function () {
            return new LoggerService();
        }
      );
      ServiceRegister::registerService(
        Configuration::CLASS_NAME,
        function () {
            return new ConfigService();
        }
      );
      ServiceRegister::registerService(
          ConfigRepositoryInterface::CLASS_NAME,
          function () {
              return new ConfigRepository();
          }
      );
      ServiceRegister::registerService(
        HttpClient::CLASS_NAME,
        function () {
            return new HttpClientService();
        }
      );
      ServiceRegister::registerService(
        AsyncProcessStarter::CLASS_NAME,
        function () {
            return new AsyncProcessStarterService();
        }
      );
      ServiceRegister::registerService(
        TaskQueueStorage::CLASS_NAME,
        function () {
            return new TaskQueueStorageService();
        }
      );
      ServiceRegister::registerService(
        Attributes::CLASS_NAME,
        function () {
            return new AttributesService();
        }
      );
      ServiceRegister::registerService(
        Recipients::CLASS_NAME,
        function () {
            return new RecipientService();
        }
      );
    }
    catch (InvalidArgumentException $exception) {
      // Don't do nothing if service is already registered.
    }
  }

}
