<?php

namespace Drupal\acquia_contenthub\Access;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Implements permission to prevent unauthorized access to webhooks.
 */
class ContentHubAccess implements AccessInterface {

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
   * Checks access to Entity CDF.
   *
   * Only grants access to logged in users with 'Administer Acquia Content Hub'
   * permission or if the request verifies its HMAC signature.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   TRUE if granted access, FALSE otherwise.
   */
  public function access(Request $request, AccountInterface $account) {
    // Check permissions and combine that with any custom access checking
    // needed. Pass forward parameters from the route and/or request as needed.
    if ($account->hasPermission(('administer acquia content hub'))) {
      // If this is a logged in user with 'Administer Acquia Content Hub'
      // permission then grant access.
      return AccessResult::allowed();
    }
    else {
      if (!$this->clientFactory->getClient()) {
        $this->loggerFactory->get('acquia_contenthub')->debug('Access denied: Acquia Content Hub Client not connected.');
        return AccessResult::forbidden('Acquia Content Hub Client not connected.');
      }
      // Only allow access if the Signature validates.
      return AccessResult::allowedIf((bool) $this->clientFactory->authenticate($request));
    }
  }

}
