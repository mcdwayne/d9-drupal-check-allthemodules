<?php

namespace Drupal\linkback\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Form controller for Linkback edit forms.
 *
 * @ingroup linkback
 */
class LinkbackForm extends ContentEntityForm {

  /**
   * Provides messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constuct a new Service.
   *
   * @param \Drupal\Core\Messenger\Messenger
   *   The messenger service.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\linkback\Entity\Linkback */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label Linkback.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label Linkback.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.linkback.canonical', [
      'linkback' => $entity->id(),
    ]);
  }

}
