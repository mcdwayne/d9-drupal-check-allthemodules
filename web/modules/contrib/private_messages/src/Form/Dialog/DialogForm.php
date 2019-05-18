<?php

namespace Drupal\private_messages\Form\Dialog;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\private_messages\Entity\Message;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Dialog edit forms.
 *
 * @ingroup private_messages
 */
class DialogForm extends ContentEntityForm {

  protected $user;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    UserInterface $user = NULL
  ) {
    /* @var $dialog \Drupal\private_messages\Entity\Dialog */
    $form = parent::buildForm($form, $form_state);

    $form['name']['widget'][0]['#title'] = FALSE;
    $form['name']['widget'][0]['#title_display'] = 'hidden';

    $form['message'] = [
      '#type'        => 'text_format',
    ];

    $form['actions']['submit']['#attributes'] = [
      'class' => ['btn-primary'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $status = parent::save($form, $form_state);
    $dialog = $this->entity;

    $message = \Drupal::entityTypeManager()->getStorage('message')->create([
      'dialog_id' => $dialog->get('id')->value,
      'message'   => $values['message'],
      'status'    => 0,
    ]);
    $message->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Dialog.', [
          '%label' => $dialog->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Dialog.', [
          '%label' => $dialog->label(),
        ]));
    }
    $form_state->setRedirect('entity.dialog.canonical',
      ['dialog' => $dialog->id(), 'user' => $dialog->getOwner()->id()]);
  }

  /**
   * This method is being run:
   *  - after router checks for integer.
   *  - after router checks existing User entity.
   *  - before buildForm() method.
   *
   * Will perform check against blocked users sends.
   */
  public function access() {
    return AccessResult::allowedIf(TRUE);
  }
}
