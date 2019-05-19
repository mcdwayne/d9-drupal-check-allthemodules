<?php

namespace Drupal\simple_integrations\Controller;

use Drupal\simple_integrations\IntegrationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Connection test controller.
 *
 * Performs a straightforward connection test and prints a status message to the
 * user with the results. Once complete, redirects the user to the main listing.
 */
class ConnectionTestController extends ConnectionController {

  /**
   * Log type: "error".
   *
   * @var string
   */
  const LOG_TYPE_ERROR = 'error';

  /**
   * Log type: "warning".
   *
   * @var string
   */
  const LOG_TYPE_WARNING = 'warning';

  /**
   * Log type: "status".
   *
   * @var string
   */
  const LOG_TYPE_SUCCESS = 'status';

  /**
   * Configure the connection test.
   *
   * @param \Drupal\simple_integrations\Entity\IntegrationInterface $integration
   *   Integration entity interface.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the Integration entity collection.
   */
  public function configureConnectionTest(IntegrationInterface $integration) {
    // Add the integration.
    $this->integration = $integration;

    // Create the connection.
    $this->connection->setIntegration($this->integration);
    $this->connection->configure();

    // Run the connection test.
    $this->runConnectionTest();

    $collection_url = $this->integration->urlInfo('collection')->toString();
    return new RedirectResponse($collection_url);
  }

  /**
   * Test the connection.
   */
  public function runConnectionTest() {
    // Log that the connection test has started.
    if ($this->integration->isDebugMode()) {
      $started_message = $this->t('Connection test for %id started.', ['%id' => $this->integration->id]);
      $this->integration->logDebugMessage($started_message);
    }

    try {
      // Perform a connection test and construct a response.
      $result = $this->integration->performConnectionTest($this->connection);
    }
    catch (\Exception $e) {
      $result = [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
      ];
    }

    $notice_type = $this->determineResultType($result['code']);
    $message = $this->t('Connection test complete. Returned code %code with message %message.', [
      '%code' => $result['code'],
      '%message' => $result['message'],
    ]);

    if ($this->integration->isDebugMode()) {
      // Log that the connection test has ended, with the results.
      $log_notice = ($notice_type == self::LOG_TYPE_ERROR) ? 'error' : 'status';
      $this->integration->logDebugMessage($message, $log_notice);
    }

    $this->messenger()->addMessage($message, $notice_type);
  }

  /**
   * Determine what kind of result this was: positive, negative, or who knows?
   *
   * @param int $code
   *   The status code returned by \Drupal::httpClient(), or exception code
   *   returned by Guzzle.
   *
   * @return string
   *   'notice', 'error' or 'warning' depending on given code.
   */
  public function determineResultType($code = 200) {
    if ($code == 200) {
      // 200 is 'okay'; treat as a success.
      return self::LOG_TYPE_SUCCESS;
    }
    elseif (substr($code, 0, 1) === '3') {
      // 300 is 'redirected' or 'moved'; we found it, but it's not perfect.
      // Treat as a notice.
      return self::LOG_TYPE_WARNING;
    }
    else {
      // Anything else can be treated as an error.
      return self::LOG_TYPE_ERROR;
    }
  }

}
