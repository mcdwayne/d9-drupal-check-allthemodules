<?php

/**
 * @file
 * Contains \Drupal\contact_message_rest\Plugin\rest\resource\ContactMessageResource.
 */

namespace Drupal\contact_message_rest\Plugin\rest\resource;

use Drupal\contact\MailHandlerInterface;
use Drupal\contact\MessageInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Creates a resource for adding contact Message entities and sending them.
 *
 * @RestResource(
 *   id = "contact_message",
 *   label = @Translation("Contact message"),
 *   serialization_class = "Drupal\contact\Entity\Message",
 *   uri_paths = {
 *     "canonical" = "/contact_message/{entity}"
 *   }
 * )
 */
class ContactMessageResource extends ResourceBase {

  /**
   * A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The flood control mechanism.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The contact mail handler service.
   *
   * @var \Drupal\contact\MailHandlerInterface
   */
  protected $mailHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('flood'),
      $container->get('contact.mail_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood control mechanism.
   * @param \Drupal\contact\MailHandlerInterface $mail_handler
   *   The contact mail handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, FloodInterface $flood, MailHandlerInterface $mail_handler, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->config = $config_factory->get('contact.settings');
    $this->currentUser = $current_user;
    $this->flood = $flood;
    $this->mailHandler = $mail_handler;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   The Message entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws HttpException in case of error.
   */
  public function post(MessageInterface $message = NULL) {
    if ($message == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    if (!$message->access('create')) {
      throw new AccessDeniedHttpException();
    }

    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$message->isNew()) {
      throw new BadRequestHttpException('Only new entities can be created');
    }

    // Only check 'edit' permissions for fields that were actually
    // submitted by the user. Field access makes no difference between 'create'
    // and 'update', so the 'edit' operation is used here.
    foreach ($message->_restSubmittedFields as $key => $field_name) {
      if (!$message->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on creating field '$field_name'");
      }
    }

    // Validate the received data before saving.
    $this->validate($message);

    // Send out the contact message via mail.
    $this->mailHandler->sendMailMessages($message, $this->currentUser);

    // Register submission with the flood service.
    $this->flood->register('contact', $this->config->get('flood.interval'));

    // Try saving the message entity. This will only be useful when
    // contact_storage module is installed.
    try {
      $message->save();
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }

    return new Response('', 200);
  }

  /**
   * Verifies that the whole entity does not violate any validation constraints.
   *
   * Copied from Drupal\rest\Plugin\rest\resource\EntityResource::validate.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   The message entity object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   If validation errors are found.
   */
  protected function validate(MessageInterface $message = NULL) {
    $violations = $message->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if (count($violations) > 0) {
      $error_message = "Unprocessable Entity: validation failed.\n";
      foreach ($violations as $violation) {
        $error_message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . "\n";
      }
      // Instead of returning a generic 400 response we use the more specific
      // 422 Unprocessable Entity code from RFC 4918. That way clients can
      // distinguish between general syntax errors in bad serializations (code
      // 400) and semantic errors in well-formed requests (code 422).
      throw new HttpException(422, $error_message);
    }

    // Check if flood control has been activated for sending emails.
    if (!$this->currentUser->hasPermission('administer contact forms') && (!$message->isPersonal() || !$this->currentUser->hasPermission('administer users'))) {
      $limit = $this->config->get('flood.limit');
      $interval = $this->config->get('flood.interval');

      if (!$this->flood->isAllowed('contact', $limit, $interval)) {
        $flood_error = $this->t('You cannot send more than %limit messages in @interval. Try again later.', array(
          '%limit' => $limit,
          '@interval' => $this->dateFormatter->formatInterval($interval),
        ));
        throw new AccessDeniedHttpException($flood_error);
      }
    }
  }

}
