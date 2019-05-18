<?php

namespace Drupal\ptalk_block_user\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for blocking author of the message.
 */
class BlockAuthorForm extends ContentEntityConfirmFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The message storage.
   *
   * @var \Drupal\ptalk\MessageStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs a block_author form for the ptalk_message entity.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    parent::__construct($entity_manager);
    $this->currentUser = $current_user;
    $this->storage = $entity_manager->getStorage('ptalk_message');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->redirectUrl();
  }

  /**
   * Ganerate redirect url.
   */
  public function redirectUrl() {
    $config = \Drupal::config('ptalk.settings');
    $message = $this->getEntity();
    $count_messages = $message->getThread()->index->message_count;

    if ((int) $count_messages > 0) {
      $uri = ptalk_message_url($message, $this->currentUser, ['fragment' => 'message-' . $message->id()]);
    }
    else {
      $uri = new Url('entity.ptalk_thread.collection');
    }

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Author of this message will be blocked from sending you messages.');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to block @author sending you messages?', ['@author' => $this->entity->getOwner()->getUserName()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->redirectUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Block author');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $author = $this->entity->getOwner();
    $recipient = $this->currentUser;
    $form_state->setRedirectUrl($this->redirectUrl());

    db_insert('ptalk_block_user')
      ->fields(array(
        'author' => $author->id(),
        'recipient' => $recipient->id(),
      ))
      ->execute();

    drupal_set_message(t('@author has been blocked from sending you any further messages.', ['@author' => ptalk_participant_format($author), ['plain' => TRUE]]));
  }

}
