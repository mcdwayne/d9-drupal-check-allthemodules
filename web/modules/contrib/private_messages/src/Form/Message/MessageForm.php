<?php

namespace Drupal\private_messages\Form\Message;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\private_messages\Entity\Dialog;

/**
 * Form controller for Message edit forms.
 *
 * @ingroup private_messages
 */
class MessageForm extends ContentEntityForm
{
  protected $user;

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, Dialog $dialog = null)
  {
    $form = parent::buildForm($form, $form_state);
    if(empty($dialog)) {
      $dialog = \Drupal::request()->attributes->get('dialog');
    }

    // $dialog->referencedEntities();
    if($dialog) {
      $form['dialog'] = [
        '#type'  => 'value',
        '#value' => $dialog->id()
      ];
    }

    $form['actions']['submit']['#attributes'] = [
      'class' => ['btn-primary']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state)
  {
    $values = $form_state->getValues();
    $message = &$this->entity;

    $message->dialog_id = $values['dialog'];
    $message->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
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
    return AccessResult::allowedIf(true);
  }
}

