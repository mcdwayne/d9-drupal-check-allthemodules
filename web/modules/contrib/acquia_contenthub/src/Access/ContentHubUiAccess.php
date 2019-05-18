<?php

namespace Drupal\acquia_contenthub\Access;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Implements permission to prevent unauthorized access to webhooks.
 */
class ContentHubUiAccess implements AccessInterface {

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Content Hub Client Manager.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * Constructs a ContentHubAccess object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $client_factory
   *   The client factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, ClientFactory $client_factory) {
    $this->loggerFactory = $logger_factory;
    $this->clientFactory = $client_factory;
  }

  /**
   * Provides access to Content Hub UI.
   *
   * Only grants access to logged in users with 'Administer Acquia Content Hub'
   * permission and a valid client. While this looks similar to ContentHubAccess
   * it uses opposite logic.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   TRUE if granted access; FALSE otherwise.
   *
   * @throws \Drupal\acquia_contenthub\Exception\ContentHubException
   */
  public function access(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    $access_result = AccessResult::forbidden();
    if ($account->hasPermission(('administer acquia content hub'))) {
      // If this is a logged in user with 'Administer Acquia Content Hub'
      // permission then grant access.
      if (!$this->clientFactory->getClient()) {
        $this->loggerFactory->get('acquia_contenthub')->debug('Access denied: Acquia Content Hub Client not connected.');
        $access_result->setReason('Acquia Content Hub Client not connected.');
      }
      else {
        $access_result = AccessResult::allowed();
      }
    }
    $access_result->addCacheTags(['acquia_contenthub_settings']);
    return $access_result;
  }

}
