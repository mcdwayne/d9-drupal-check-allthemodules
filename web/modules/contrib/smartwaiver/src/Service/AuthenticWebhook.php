<?php

namespace Drupal\smartwaiver\Service;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Logger\LoggerChannelFactory;
use Symfony\Component\HttpFoundation\Request;

use Drupal\key\KeyRepositoryInterface;

/**
 * Checks access for displaying configuration translation page.
 */
class AuthenticWebhook implements AccessInterface {

  /**
   * The key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The smartwaiver config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * A logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(KeyRepositoryInterface $key_repository, ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger_factory) {
    $this->keyRepository = $key_repository;
    $this->config = $config_factory->get('smartwaiver.config');
    $this->logger = $logger_factory->get('smartwaiver');
  }

  /**
   * Authenticates and validates a webhook request.
   *
   * This request will only be permitted if it has all required parameters, if
   * the event is for a new waiver, and the event is authenticated to be from
   * Smartwaiver.
   */
  public function access(Request $request) {
    return AccessResult::allowedIf($this->authentic($request));
  }

  /**
   * Whether a given smartwaiver webhook request is authentic.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return bool
   *   TRUE is authentic, FALSE if not.
   */
  protected function authentic(Request $request) {
    $parameters = $request->request;

    // If no unique_id or credential are provided, this can't be authenticated.
    if (!$parameters->has('unique_id') || !$parameters->has('credential')) {
      $this->log('Invalid webhook request. Cannot be authenticated.');
      return FALSE;
    }

    // Generate the expected credential.
    $unique_id = $parameters->get('unique_id');
    $expected_credential = md5($this->webhookKey() . $unique_id);

    // Check that the credential is correct.
    $has_correct_credential = $parameters->get('credential') == $expected_credential;

    // If the credential was not authentic, log the failed attempt.
    if (!$has_correct_credential) {
      $this->log('Invalid credential provided for @unique_id', [
        '@unique_id' => $unique_id,
      ]);
    }

    return $has_correct_credential;
  }

  /**
   * Retrieves a smartwaiver webhook key from configuration.
   *
   * @return string
   *   The smartwaiver webhook private key.
   */
  protected function webhookKey() {
    if ($key_id = $this->config->get('webhook_key')) {
      $key = $this->keyRepository->getKey($key_id);
      $value = trim($key->getKeyValue());
      return $value;
    }
  }

  /**
   * Log helper method.
   */
  protected function log($message, $context = []) {
    $this->logger->info($message, $context);
  }

}
