<?php

namespace Drupal\dblog_persistent\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dblog_persistent\DbLogPersistentStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChannelClearForm extends EntityConfirmFormBase {

  /**
   * @var \Drupal\dblog_persistent\DbLogPersistentStorageInterface
   */
  protected $storage;

  /**
   * ChannelClearForm constructor.
   *
   * @param \Drupal\dblog_persistent\DbLogPersistentStorageInterface $storage
   */
  public function __construct(DbLogPersistentStorageInterface $storage) {
    $this->storage = $storage;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('dblog_persistent.storage'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Clear the persistent log channel %channel?', [
      '%channel' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl(): Url {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    if ($count = $this->storage->clearChannel($this->entity->id())) {
      $this->messenger()->addStatus($this->t('Deleted %count log messages from channel %channel.', [
        '%count' => $count,
        '%channel' => $this->entity->label(),
      ]));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
