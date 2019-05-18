<?php

namespace Drupal\message_thread\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\message_thread\Entity\MessageThread;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a message thread deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {
  /**
   * The array of message threads to delete.
   *
   * @var array
   */
  protected $messageThreads = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The message storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('message_thread');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_thread_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return \Drupal::translation()->formatPlural(count($this->messageThreads), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->messageThreads = $this->tempStoreFactory->get('message_thread_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->messageThreads)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['message_threads'] = [
      '#theme' => 'item_list',
      '#items' => array_map(function (MessageThread $message_thread) {
        $params = [
          '@id' => $message_thread->id(),
          '@template' => $message_thread->getTemplate()->label(),
        ];
        return t('Delete message thread ID @id for template @template', $params);
      }, $this->messageThreads),
    ];
    $form = parent::buildForm($form, $form_state);

    $form['actions']['cancel']['#href'] = $this->getCancelRoute();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->messageThreads)) {
      $this->storage->delete($this->messageThreads);
      $this->tempStoreFactory->get('message_thread_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->messageThreads);
      $this->logger('message_thread')->notice('Deleted @count message threads.', ['@count' => $count]);
      drupal_set_message(\Drupal::translation()->formatPlural($count, 'Deleted 1 message thread.', 'Deleted @count message threads.'));
    }
    $form_state->setRedirect('message_thread.message_threads');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('message_thread.message_threads');
  }

}
