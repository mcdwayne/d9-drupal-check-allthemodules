<?php

namespace Drupal\ptalk\Form;

use Drupal\ptalk\ThreadStorageInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the thread multiple delete confirmation form.
 */
class ThreadConfirmDeleteMultiple extends ConfirmFormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The thread storage.
   *
   * @var \Drupal\ptalk\ThreadStorageInterface
   */
  protected $threadStorage;

  /**
   * An array of threads to be deleted.
   *
   * @var string[][]
   */
  protected $threadInfo;

  /**
   * Creates an new ConfirmDeleteMultiple form.
   *
   * @param \Drupal\ptalk\ThreadStorageInterface $thread_storage
   *   The thread storage.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(ThreadStorageInterface $thread_storage, PrivateTempStoreFactory $temp_store_factory) {
    $this->threadStorage = $thread_storage;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('ptalk_thread'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ptalk_thread_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->threadInfo), 'Are you sure you want to delete this conversation and all its messages?', 'Are you sure you want to delete these conversations and all their messages?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.ptalk_thread.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->threadInfo = $this->tempStoreFactory->get('ptalk_thread_multiple_delete_confirm')->get($this->currentUser()->id());
    if (empty($this->threadInfo)) {
      return $this->redirect('entity.ptalk_thread.canonical');
    }
    /** @var \Drupal\ptalk\ThreadInterface[] $threads */
    $threads = $this->threadStorage->loadMultiple($this->threadInfo);

    $items = [];
    foreach ($threads as $thread) {
      $items[$thread->id()] = $thread->label();
    }

    $form['threads'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->threadInfo)) {
      /** @var \Drupal\ptalk\ThreadInterface[] $threads */
      $threads = $this->threadStorage->loadMultiple($this->threadInfo);

      foreach ($threads as $thread) {
        $thread->deleteThread(PTALK_DELETED);
        $thread->save();
      }

      $this->logger('ptalk_thread')->notice('Deleted @count private conversations.', ['@count' => count($threads)]);

      drupal_set_message($this->formatPlural(count($threads), 'Deleted 1 private conversation.', 'Deleted @count private conversations.'));

      $this->tempStoreFactory->get('ptalk_thread_multiple_delete_confirm')->delete($this->currentUser()->id());
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
