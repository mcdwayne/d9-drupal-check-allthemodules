<?php

namespace Drupal\xero_contact_sync;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\user\UserInterface;
use Drupal\xero\XeroQueryFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class XeroContactSyncRemoteUserMatcher implements ContainerInjectionInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A Xero query factory.
   *
   * @var \Drupal\xero\XeroQueryFactory
   */
  protected $xeroQueryFactory;

  /**
   * A lookup service.
   *
   * @var \Drupal\xero_contact_sync\XeroContactSyncLookupService
   */
  protected $lookupService;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new XeroContactSyncRemoteUserMatcher object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\xero\XeroQueryFactory $xero_query_factory
   *   The Xero query factory.
   * @param \Drupal\xero_contact_sync\XeroContactSyncLookupService $lookup_service
   *   The Xero lookup service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(LoggerInterface $logger, TypedDataManagerInterface $typed_data_manager, EntityTypeManagerInterface $entity_type_manager, XeroQueryFactory $xero_query_factory, XeroContactSyncLookupService $lookup_service, EventDispatcherInterface $event_dispatcher) {
    $this->logger = $logger;
    $this->typedDataManager = $typed_data_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->xeroQueryFactory = $xero_query_factory;
    $this->lookupService = $lookup_service;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.xero_contact_sync'),
      $container->get('typed_data_manager'),
      $container->get('entity_type.manager'),
      $container->get('xero.query.factory'),
      $container->get('xero_contact_sync.lookup_service'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * @param $user
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function matchRemoteUser(UserInterface $user) {
    if ($contact = $this->lookupService->lookupByContactNumber($user->id())) {
      $user->set('xero_contact_id', $contact->get('ContactID')->getValue());
      $user->save();
      $this->logger->log(LogLevel::INFO, (string) new FormattableMarkup('User already found by contact number @contact_number, assigned with remote id @remote_id.', [
        '@contact_number' => $user->id(),
        '@remote_id' => $contact->get('ContactID')->getValue(),
      ]));
      return TRUE;
    }
    elseif ($contact = $this->lookupService->lookupByContactEmailAddress($user->getEmail())) {
      $user->set('xero_contact_id', $contact->get('ContactID')->getValue());
      $user->save();
      $this->logger->log(LogLevel::INFO, (string) new FormattableMarkup('User already found by email @mail, assigned with remote id @remote_id.', [
        '@mail' => $user->getEmail(),
        '@remote_id' => $contact->get('ContactID')->getValue(),
      ]));
      return TRUE;
    }
    else {
      $contact = [
        'ContactNumber' => $user->id(),
        'Name' => $user->getDisplayName(),
        'EmailAddress' => $user->getEmail(),
      ];

      $event = new XeroContactSyncEvent($user, $contact);
      $this->eventDispatcher->dispatch(XeroContactSyncEvents::SAVE, $event);

      // This will ensure our event subscribed can override the data.
      $contact = $event->getData();

      /** @var \Drupal\Core\TypedData\ListDataDefinition $list_definition */
      $list_definition = $this->typedDataManager->createListDataDefinition('xero_contact');
      $contacts = $this->typedDataManager->create($list_definition, []);
      $contacts->offsetSet(0, $contact);

      // Do the remote creation.
      $xeroQuery = $this->xeroQueryFactory->get();
      $xeroQuery->setType('xero_contact')
        ->setData($contacts)
        ->setMethod('post');

      /** @var \Drupal\xero\Plugin\DataType\XeroItemList|boolean $result */
      $result = $xeroQuery->execute();

      if ($result === FALSE) {
        $this->logger->log(LogLevel::ERROR, (string) new FormattableMarkup('Cannot create user @username, operation failed.', [
          '@username' => $user->getDisplayName(),
        ]));
        return FALSE;
      }
      if ($result->count() > 0) {
        /** @var \Drupal\xero\Plugin\DataType\Contact $createdXeroContact */
        $createdXeroContact = $result->get(0);
        $remote_id = $createdXeroContact->get('ContactID')->getValue();
        $user->set('xero_contact_id', $remote_id);
        $user->save();

        $this->logger->log(LogLevel::INFO, (string) new FormattableMarkup('Created user @username with remote id @remote_id.', [
          '@username' => $user->getDisplayName(),
          '@remote_id' => $remote_id,
        ]));
        return TRUE;
      }
      return FALSE;
    }
  }

}
