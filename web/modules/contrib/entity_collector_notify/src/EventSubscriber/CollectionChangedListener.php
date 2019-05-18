<?php

namespace Drupal\entity_collector_notify\EventSubscriber;

use Drupal\entity_collector\Entity\EntityCollectionInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\message\MessageTemplateInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Listens for insert and update events on Entity Collections to see if
 * participants have been added to the collection. When this is the case, this
 * class notifies the participant(s) to which collection they have been added
 * and by whom.
 */
class CollectionChangedListener implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The message notifier.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructs a new CollectionChangedListener object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   * @param \Drupal\message_notify\MessageNotifier $messageNotifier
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, MessageNotifier $messageNotifier) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => ['notifyParticipantAddedToCollection'],
      HookEventDispatcherInterface::ENTITY_UPDATE => ['notifyParticipantAddedToCollection'],
    ];
  }

  /**
   * This method is called whenever the hook_event_dispatcher.entity.insert
   * event is dispatched. It checks if participants have been added to a
   * collection on creation. When this is the case, it notifies the
   * participants
   * to which collection they have been added and by whom.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent $event
   *   The event that is dispatched when a new collection has been created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException Thrown
   *   if the message_template, message or user entity types are unknown.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException In case of failures an
   *   exception is thrown.
   * @throws \Drupal\message_notify\Exception\MessageNotifyException If no
   *   matching notifier plugin exists
   */
  public function notifyParticipantAddedToCollection(BaseEntityEvent $event) {
    /** @var \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection */
    $entityCollection = $event->getEntity();

    if ($entityCollection->getEntityTypeId() !== 'entity_collection') {
      return;
    }

    $entityCollectionTypeStorage = $this->entityTypeManager->getStorage('entity_collection_type');
    $entityCollectionType = $entityCollectionTypeStorage->load($entityCollection->bundle());
    if (NULL === $entityCollectionType || !$entityCollectionType->getThirdPartySetting('entity_collector_notify', 'participant_notification', FALSE)) {
      return;
    }

    $participantIds = $this->getNewlyAddedParticipantIds($entityCollection);

    if (empty($participantIds)) {
      return;
    }

    $this->sendMessageBulk($participantIds, $entityCollection);
  }

  /**
   * Checks if new participants have been added to an existing collection.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   *   The Entity Collection entity.
   *
   * @return array
   *   The ids of the participants newly added to the collection.
   */
  private function getNewlyAddedParticipantIds(EntityCollectionInterface $entityCollection) {
    if (!isset($entityCollection->original) || !$entityCollection->original instanceof EntityCollectionInterface) {
      return $entityCollection->getParticipantsIds();
    }
    $participantIds = $entityCollection->getParticipantsIds();
    $originalParticipantIds = $entityCollection->original->getParticipantsIds();
    return array_diff($participantIds, $originalParticipantIds);
  }

  /**
   * Send one ore more notifications to participants
   *
   * @param array $participantIds
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   *   The Entity Collection Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException Thrown
   *   if the message_template, message or user entity types are unknown.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException In case of failures an
   *   exception is thrown.
   * @throws \Drupal\message_notify\Exception\MessageNotifyException If no
   *   matching notifier plugin exists.
   */
  private function sendMessageBulk(array $participantIds, EntityCollectionInterface $entityCollection) {
    $userStorage = $this->entityTypeManager->getStorage('user');
    /** @var UserInterface[] $participantUsers */
    $participantUsers = $userStorage->loadMultiple($participantIds);
    $messageTemplate = $this->getMessageTemplate();
    foreach ($participantUsers as $participantUser) {
      $message = $this->initializeMessage(
        $entityCollection,
        $participantUser,
        $messageTemplate
      );

      $this->messageNotifier->send($message, [], 'email');
    }
  }

  /**
   * Gets the Participant Added To Collection Notification message template.
   *
   * @return \Drupal\message\MessageTemplateInterface
   *   The message template.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the message_template entity type is unknown.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getMessageTemplate() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $message_template_storage */
    $messageTemplateStorage = $this->entityTypeManager->getStorage('message_template');
    /** @var \Drupal\message\MessageTemplateInterface $messageTemplate */
    $messageTemplate = $messageTemplateStorage->load('participant_added_to_collection');

    return $messageTemplate;
  }

  /**
   * Sets some arguments and saves the messsage.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionInterface $entityCollection
   * @param \Drupal\user\UserInterface $participantUser
   * @param \Drupal\message\MessageTemplateInterface $messageTemplate
   *
   * @return \Drupal\message\MessageInterface
   *   The saved Message object.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException In case of failures an
   *   exception is thrown.
   */
  private function initializeMessage(EntityCollectionInterface $entityCollection, UserInterface $participantUser, MessageTemplateInterface $messageTemplate) {
    $message = $this->prepareMessage($participantUser, $messageTemplate);
    $message->setArguments([
      '@collection_creator_editor' => $this->currentUser->getAccountName(),
      '@collection' => $entityCollection->getName(),
      '@collection_url' => $entityCollection->toUrl('canonical', ['absolute' => TRUE])->toString(),
      '@collection_link' => $entityCollection->toLink(NULL, 'canonical', ['absolute' => TRUE])->toString(),
      '@participant' => $participantUser->getAccountName(),
    ]);
    $message->save();

    return $message;
  }

  /**
   * Creates a message and sets some basic configuration.
   *
   * @param \Drupal\user\UserInterface $participantUser
   * @param \Drupal\message\MessageTemplateInterface $messageTemplate
   *
   * @return \Drupal\message\MessageInterface
   *   A Message entity ready to be saved.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException If
   *   the message entity type is unknown.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function prepareMessage(UserInterface $participantUser, MessageTemplateInterface $messageTemplate) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $messageStorage */
    $messageStorage = $this->entityTypeManager->getStorage('message');

    /** @var \Drupal\message\MessageInterface $message */
    $message = $messageStorage
      ->create(['template' => $messageTemplate->id()]);
    $message->setOwner($participantUser);

    return $message;
  }
}
