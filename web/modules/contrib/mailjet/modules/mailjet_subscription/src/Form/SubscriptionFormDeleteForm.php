<?php

namespace Drupal\mailjet_subscription\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block\Entity\Block;

/**
 *
 * @ingroup mailjet_subscription
 */
class SubscriptionFormDeleteForm extends EntityConfirmFormBase {

  public function getQuestion() {
    return $this->t('Are you sure you want to delete the subscription form -> %label?', [
      '%label' => $this->entity->label(),
    ]);
  }


  public function getConfirmText() {
    return $this->t('Delete Subscription Form');
  }


  public function getCancelUrl() {
    return new Url('entity.mailjet_subscription_form.list');
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {

    $block_name = strtolower(mailjetsubscriptionform . $this->entity->name);
    $block_name = str_replace(' ', '', $block_name);
    $block = Block::load($block_name);

    if (!empty($block)) {
      $block->delete();
    }


    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    drupal_set_message($this->t('Subscription Form %label was deleted.', [
      '%label' => $this->entity->label(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
