<?php

namespace Drupal\simple_integrations\Entity;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_integrations\ConnectionClient;
use Drupal\simple_integrations\IntegrationInterface;
use Drupal\simple_integrations\Exception\IntegrationInactiveException;
use Drupal\simple_integrations\Exception\DebugModeDisabledException;
use Drupal\simple_integrations\Exception\EmptyDebugMessageException;
use Drupal\simple_integrations\Exception\InvalidArgumentException;

/**
 * Defines the Integration entity.
 *
 * @ConfigEntityType(
 *   id = "integration",
 *   label = @Translation("Integration"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_integrations\Controller\IntegrationListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\simple_integrations\Form\IntegrationEntityForm",
 *     }
 *   },
 *   config_prefix = "integration",
 *   admin_permission = "administer integrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/config/integrations",
 *     "edit-form" = "/admin/config/integrations/{integration}",
 *     "test-connection" = "/admin/config/integrations/{integration}/test-connection"
 *   }
 * )
 */
class Integration extends ConfigEntityBase implements IntegrationInterface {

  use StringTranslationTrait;

  /**
   * The Integration ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Integration label.
   *
   * @var string
   */
  public $label;

  /**
   * Whether this Integration is active.
   *
   * @var bool
   */
  public $active = TRUE;

  /**
   * Whether this Integration is operating in Debug mode.
   *
   * @var bool
   */
  public $debug_mode = FALSE;

  /**
   * The end point to connect to.
   *
   * @var string
   */
  public $external_end_point;

  /**
   * The location of a certificate file.
   *
   * @var string
   */
  protected $certificate;

  /**
   * The username (or equivalent) to use for authentication.
   *
   * @var string
   */
  protected $auth_user;

  /**
   * The access key (or equivalent) to use for authentication.
   *
   * @var string
   */
  protected $auth_key;

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->active;
  }

  /**
   * {@inheritdoc}
   */
  public function isDebugMode() {
    return $this->debug_mode;
  }

  /**
   * Perform a basic connection test.
   *
   * Simply, request the endpoint defined in this integration. No interaction
   * will take place: this simply checks to see if the endpoint is available.
   *
   * It's possible that this will return an error message, even when the request
   * is successful - for example, if the endpoint itself cannot be accessed, but
   * if you post a data to a specific method on the end point, it would work.
   *
   * @param \Drupal\simple_integrations\ConnectionClient $connection
   *   A Connection client.
   *
   * @throws \Drupal\simple_integrations\Exception\IntegrationInactiveException
   * @throws \Drupal\simple_integrations\Exception\InvalidArgumentException
   *
   * @return array
   *   A response array of the status code and a message.
   */
  public function performConnectionTest(ConnectionClient $connection) {
    // Exit quickly if this isn't an active integration.
    if (!$this->isActive()) {
      throw new IntegrationInactiveException($this->id());
    }

    // Check that the end point is valid.
    $end_point = $this->external_end_point;
    if (!UrlHelper::isValid($end_point)) {
      throw new InvalidArgumentException($this->t('The end point for this integration, %end_point, is invalid. Please try again.', [
        '%end_point' => $end_point,
      ]));
    }

    // Attempt a very simple get request - poll the end point with the given
    // configuration.
    $response = $connection->get(
      $connection->getRequestEndPoint(),
      $connection->getRequestConfig()
    );

    return [
      'code' => $response->getStatusCode(),
      'message' => $response->getReasonPhrase(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function logDebugMessage($message, $type = 'notice') {
    if (empty($message)) {
      throw new EmptyDebugMessageException();
    }

    if (!$this->isDebugMode()) {
      throw new DebugModeDisabledException($this->id());
    }

    \Drupal::logger('integrations')->{$type}($message);
  }

}
