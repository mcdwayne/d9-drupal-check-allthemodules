<?php

namespace Drupal\mail_entity_queue\Form;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting multiple mail queue items.
 */
class MailEntityQueueItemMultipleDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new MailEntityQueueItemMultipleDeleteConfirmForm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $storage
   *   The entity type storage.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, ContentEntityStorageInterface $storage) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')->getStorage('mail_entity_queue_item')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_entity_queue_item_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete these items from the queue?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.mail_entity_queue_item.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface[] $items */
    $items = $this->tempStoreFactory
      ->get('mail_entity_queue_item_operations_delete')
      ->get($this->currentUser()->id());

    if (!$items) {
      return $this->redirect('entity.mail_entity_queue_item.collection');
    }

    $form['items'] = [
      '#prefix' => '<ul>',
      '#suffix' => '</ul>',
      '#tree' => TRUE
    ];

    foreach ($items as $item) {
      $id = $item->id();

      $form['items'][$id] = [
        '#type' => 'hidden',
        '#value' => $id,
        '#prefix' => '<li>',
        '#suffix' => $item->label() . "</li>\n",
      ];
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm')) {
      /** @var \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface[] $items */
      $items = $this->tempStoreFactory
        ->get('mail_entity_queue_item_operations_delete')
        ->get($this->currentUser()->id());
      $this->storage->delete($items);
    }

    // Clear out the items from the temp store.
    $this->tempStoreFactory->get('mail_entity_queue_item_operations_delete')
      ->delete($this->currentUser()->id());

    $form_state->setRedirect('entity.mail_entity_queue_item.collection');
  }

}
