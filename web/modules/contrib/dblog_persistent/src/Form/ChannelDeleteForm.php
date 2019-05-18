<?php

namespace Drupal\dblog_persistent\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityDeleteFormTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dblog_persistent\DbLogPersistentStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Persistent Log Message Type entities.
 */
class ChannelDeleteForm extends EntityConfirmFormBase {

  use EntityDeleteFormTrait {
    submitForm as submitFormDelete;
  }

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $storage;

  /**
   * ChannelDeleteForm constructor.
   *
   * @param \Drupal\dblog_persistent\DbLogPersistentStorageInterface $storage
   */
  public function __construct(DbLogPersistentStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('dblog_persistent.storage'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->submitFormDelete($form, $form_state);

    if ($count = $this->storage->clearChannel($this->entity->id())) {
      $this->messenger()->addStatus($this->t('Deleted %count log messages from channel %channel.', [
        '%count' => $count,
        '%channel' => $this->entity->label(),
      ]));
    }
  }

}
