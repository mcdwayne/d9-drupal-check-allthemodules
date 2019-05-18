<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Plugin\Mail;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\EmailParserTrait;
use Drupal\sendwithus\Event\TemplateCollectorAlter;
use Drupal\sendwithus\Event\Events;
use Drupal\sendwithus\Resolver\Template\TemplateResolver;
use Drupal\sendwithus\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\sendwithus\ApiManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides a 'sendwithus' mail plugin.
 *
 * @Mail(
 *  id = "sendwithus_mail",
 *  label = @Translation("Sendwithus mail")
 * )
 */
class SendwithusMail implements MailInterface, ContainerFactoryPluginInterface {

  use EmailParserTrait;

  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Drupal\sendwithus\ApiManager definition.
   *
   * @var \Drupal\sendwithus\ApiManager
   */
  protected $apiManager;

  /**
   * The template resolver.
   *
   * @var \Drupal\sendwithus\Resolver\Template\TemplateResolver
   */
  protected $resolver;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueFactory $queue
   *   The queue.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger factory.
   * @param \Drupal\sendwithus\ApiManager $apiManager
   *   The api key.
   * @param \Drupal\sendwithus\Resolver\Template\TemplateResolver $resolver
   *   The template resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueueFactory $queue, LoggerChannelFactory $logger_factory, ApiManager $apiManager, TemplateResolver $resolver, EventDispatcherInterface $eventDispatcher) {

    $this->queue = $queue;
    $this->logger = $logger_factory->get('sendwithus');
    $this->apiManager = $apiManager;
    $this->resolver = $resolver;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue'),
      $container->get('logger.factory'),
      $container->get('sendwithus.api_manager'),
      $container->get('sendwithus.template.resolver'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Nothing to do.
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $context = new Context($message['module'], $message['id'], new ParameterBag($message));
    $template = $this->resolver->resolve($context);

    if (!$template instanceof Template) {
      $this->logger->error(
        new FormattableMarkup('No template found for given email (@type).', [
          '@type' => $message['id'],
        ])
      );

      return FALSE;
    }
    $options = $message['params']['sendwithus']['options'] ?? [];
    $api = $this->apiManager->getAdapter($options);

    // Recipients must be formatted in ['address' => 'mail@example.tdl'] format.
    $recipients = $this->parseAddresses($message['to']);

    if (!empty($message['headers']['Cc'])) {
      $template->setVariable('cc', $this->parseAddresses($message['headers']['Cc']));
    }

    // Make the first recipient our 'primary' recipient.
    $to = array_shift($recipients);

    // Merge manually set BCCs with rest of the recipients.
    if (!empty($message['headers']['Bcc'])) {
      $recipients = array_merge($recipients, $this->parseAddresses($message['headers']['Bcc']));
    }
    // Additional recipients should be set as bcc.
    if (!empty($recipients)) {
      $template->setVariable('bcc', $recipients);
    }
    /** @var \Drupal\sendwithus\Event\TemplateCollectorAlter $event */
    $event = $this->eventDispatcher->dispatch(Events::TEMPLATE_ALTER,
      new TemplateCollectorAlter($context, $template)
    );
    // Allow template to be altered before sending the email.
    $template = $event->getTemplate();

    $status = $api->send($template->getTemplateId(), $to, $template->toArray());

    if (!empty($status->success)) {
      return ['response' => $status, 'template' => $template];
    }

    if (isset($status->exception)) {
      /** @var \sendwithus\API_Error $exception */
      $exception = $status->exception;
      $this->logger->error($exception->getBody());
    }
    return FALSE;
  }

}
