<?php

namespace Drupal\messagebird;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class MessageBirdServiceException.
 *
 * @package Drupal\messagebird
 */
class MessageBirdException implements MessageBirdExceptionInterface {
  use StringTranslationTrait, LoggerChannelTrait;

  /**
   * MessageBird configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * MessageBirdException constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration Factory object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Account object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->config = $config_factory->get('messagebird.settings');
    $this->currentUser = $current_user;
  }

  /**
   * Log the right status for the given Exception type.
   *
   * @param \Throwable $exception
   *   Exception Object.
   * @param array $args
   *   (optional) Arguments for t().
   */
  public function logError(\Throwable $exception, array $args = array()) {
    $class = new \ReflectionClass($exception);

    if ($class->getNamespaceName() != 'MessageBird\Exceptions') {
      return;
    }

    $logger = $this->getLogger('messagebird');

    switch ($class->getShortName()) {
      case 'AuthenticateException':
        $logger->alert('AuthenticateException failed, please check your credentials.');
        break;

      case 'BalanceException':
        $logger->warning('BalanceException: @error', array(
          '@error' => $exception->getMessage(),
        ));
        break;

      case 'HttpException':
        $logger->warning('HttpException: @error', array(
          '@error' => $exception->getMessage(),
        ));
        break;

      case 'RequestException':
        if ($this->config->get('debug.mode')) {
          $logger->debug('RequestException: @error', array(
            '@error' => $exception->getMessage(),
          ));
        }
        break;

      case 'ServerException':
        $logger->alert('ServerException: @error', array(
          '@error' => $exception->getMessage(),
        ));
        break;

      default:
        $logger->notice($exception->getMessage(), $args);
    }

    $log_access = $this->currentUser->hasPermission('access site reports');

    if ($log_access) {
      drupal_set_message($this->t('MessageBird @class_name, see <a href=":url_to_dblog">Recents Logs</a> for more info.', array(
        '@class_name' => $class->getShortName(),
        ':url_to_dblog' => Url::fromRoute('dblog.overview'),
      )), 'error');
    }
    elseif ($class->getShortName() != 'RequestException') {
      drupal_set_message($this->t('Something went wrong, please contact the site administrator.'), 'error');
    }
  }

}
