<?php

namespace Drupal\ptalk\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a ptalk_thread entity.
 */
class ThreadDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The thread storage.
   *
   * @var \Drupal\ptalk\ThreadStorageInterface
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
   * Constructs a deletion form for the ptalk_thread entity.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $current_user) {
    parent::__construct($entity_manager);
    $this->currentUser = $current_user;
    $this->storage = $entity_manager->getStorage('ptalk_thread');
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    if ($this->currentUser->hasPermission('read all private conversation')) {
      $form['delete_options'] = array(
        '#type' => 'checkbox',
        '#title' => t('Delete this conversation for all users?'),
        '#description' => t('Tick the box to delete the conversation for all users.'),
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
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('All messages of this conversation will be deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this conversation?');
  }

  /**
   * Ganerate redirect url.
   */
  public function redirectUrl() {
    $config = \Drupal::config('ptalk.settings');
    $thread = $this->getEntity();
    $count_messages = \Drupal::service('ptalk_thread.manager')->countMessages($thread, $this->currentUser->id());

    if ((int) $count_messages > 0) {
      $uri = $thread->urlInfo();
    }
    else {
      $uri = new Url('entity.ptalk_thread.collection');
    }

    return $uri;
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
    return $this->t('Delete conversation');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $thread = $this->getEntity();
    $account = ($form_state->getValue('delete_options')) ? NULL : $this->currentUser;
    ptalk_thread_change_delete($thread, PTALK_DELETED, $account);

    $form_state->setRedirectUrl($this->redirectUrl());

    $this->logger('ptalk_thread')->notice('@type: thread @tid has been deleted @state.',
      [
        '@type' => $this->entity->bundle(),
        '@tid' => $this->entity->getThreadId(),
        '@state' => is_null($account) ? t('for all participants') : t('for') . ' ' . $this->currentUser->getUsername(),
      ]);

    if (is_null($account)) {
      drupal_set_message(t('Thread has been deleted for all users.'));
    }
    else {
      drupal_set_message(t('Thread has been deleted.'));
    }

  }

}
