<?php

namespace Drupal\sms_rule_based\Form;

use \Drupal\Core\Entity\EntityConfirmFormBase;
use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Url;

class SmsRoutingRulesetDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the SMS routing ruleset @ruleset?', ['@ruleset' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.sms_routing_ruleset.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $form_state->setRedirectUrl($this->getCancelUrl());
    drupal_set_message($this->t('Ruleset @title has been deleted.', ['@title' => $this->entity->label()]));
  }

}
