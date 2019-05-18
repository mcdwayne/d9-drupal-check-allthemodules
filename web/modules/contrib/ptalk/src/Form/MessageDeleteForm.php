<?php

namespace Drupal\ptalk\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a ptalk_message entity.
 */
class MessageDeleteForm extends ContentEntityConfirmFormBase {

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
   * Constructs a deletion form for the ptalk_message entity.
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
  public function form(array $form, FormStateInterface $form_state) {
    $message = $this->getEntity();
    // Show this form if current user has the proper permission and message is undeleted.
    if ($this->currentUser->hasPermission('read all private conversation') && !$message->isDeleted()) {
      $form['delete_options'] = array(
        '#type' => 'checkbox',
        '#title' => t('Delete this message for all users?'),
        '#description' => t('Tick the box to delete the message for all users.'),
        '#default_value' => FALSE,
      );
    }

    return $form;
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
    return $this->t('This message will be deleted from this conversation.');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this message?');
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
    return $this->t('Delete message');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = $this->getEntity();
    $thread = $message->getThread();
    // Actually we do not delete ptalk_message entity, we only mark this message
    // in table ptalk_message_index as deleted, deletion the entity will be handled in hook_cron().
    $account = ($form_state->getValue('delete_options')) ? NULL : $this->currentUser;
    ptalk_message_change_delete($message, PTALK_DELETED, $account);

    $form_state->setRedirectUrl($this->redirectUrl());

    $this->logger('ptalk_message')->notice('@type: message @mid of the thread @tid has been deleted @state.',
      [
        '@type' => $this->entity->bundle(),
        '@mid' => $this->entity->id(),
        '@tid' => $this->entity->getThreadId(),
        '@state' => is_null($account) ? t('for all participants') : t('for') . ' ' . $this->currentUser->getUsername(),
      ]);

    if (is_null($account)) {
      drupal_set_message(t('Message has been deleted for all users.'));
    }
    else {
      drupal_set_message(t('Message has been deleted.'));
    }

  }

}
