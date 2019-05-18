<?php

namespace Drupal\matrix_api\Plugin\RulesAction;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\matrix_api\MatrixClientInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\core\Utility\Token;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SendMatrixMessage' rules action.
 *
 * @RulesAction(
 *   id = "send_matrix_message",
 *   label = @Translation("Send Matrix Message"),
 *   category = @Translation("Matrix"),
 *   context = {
 *     "room" = @ContextDefinition("string",
 *       label = @Translation("Room alias to post message"),
 *       description = @Translation("Matrix room alias to send. Will attempt to join room if not currently in room."),
 *       multiple = FALSE,
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("The email's message body."),
 *     ),
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data"),
 *       description = @Translation("Specifies data to be loaded into the token context."),
 *       allow_null = TRUE,
 *       assignment_restriction = "selector",
 *       required = FALSE
 *     )
 *   }
 * )
 */
class SendMatrixMessage extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The logger channel the action will write log messages to.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The MatrixClient object.
   *
   * @var \Drupal\matrix_api\MatrixClientInterface
   */
  protected $matrixClient;

  /**
   * The Token service.
   *
   * @var \Drupal\core\Utility\Token
   */
  protected $token;

  /**
   * SendMatrixMessage constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\matrix_api\MatrixClientInterface $matrixClient
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, MatrixClientInterface $matrixClient, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->matrixClient = $matrixClient;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('rules'),
      $container->get('matrix_api.matrixclient'),
      $container->get('token')
    );
  }

  /**
   * Send a Matrix message.
   *
   * @param string $room
   *   Room alias to send message.
   * @param string $message
   *   Message text.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity to pass for token data.
   */
  protected function doExecute($room, $message, ContentEntityInterface $entity = NULL) {
    $data = [];
    if ($entity) {
      $type = $entity->getEntityTypeId();
      $data[$type] = $entity;
    }
    $roomId = $this->matrixClient->join($room);
    $body = $this->token->replace($message, $data);

    if ($result = $this->matrixClient->sendMessage($roomId, $body)) {
      $this->logger->debug('Sent message to Matrix - @msg_id',
        [
          '@msg_id' => $result,
        ]);
    }
  }

}
