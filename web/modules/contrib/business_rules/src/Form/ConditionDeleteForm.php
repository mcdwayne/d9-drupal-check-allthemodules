<?php

namespace Drupal\business_rules\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Condition entities.
 */
class ConditionDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name? All references to this condition will be removed as well.', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\business_rules\Util\BusinessRulesUtil $util */
    $util                               = \Drupal::service('business_rules.util');
    $form['rules_using_this_item']      = $util->getUsedByBusinessRulesDetailsBox($this->entity);
    $form['conditions_using_this_item'] = $util->getUsedByConditionsDetailsBox($this->entity);
    $form['actions_using_this_item']    = $util->getUsedByActionsDetailsBox($this->entity);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.business_rules_condition.collection');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type'  => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
      )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
