<?php

namespace Drupal\customfilter\Form;

// Base class for form that delete a configuration entity.
use Drupal\Core\Form\ConfirmFormBase;

// Use base class for Url.
use Drupal\Core\Url;

// Need for FormStateInterface.
use Drupal\Core\Form\FormStateInterface;

// Need for the entity.
use Drupal\customfilter\Entity\CustomFilter;

/**
 * Builds the form to delete a Custom Filter.
 */
class CustomFilterRulesDeleteForm extends ConfirmFormBase {
  /**
   * The customfilter with the rule to delete.
   *
   * @var \Drupal\customfilter\Entity\CustomFilter
   */
  protected $customfilter;

  /**
   * The rule to delete.
   */
  protected $rule;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, CustomFilter $customfilter = NULL, $rule_id = NULL) {
    $this->customfilter = $customfilter;
    $this->rule = $this->customfilter->getRule($rule_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->rule['name']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('customfilter.rules.list', array('customfilter' => $this->customfilter->id()));
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
  public function getDescription() {
    return $this->t('This will delete the rule %name and all sub-rules. This
      action cannot be undone.', array('%name' => $this->rule['name']));
  }
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'customfilter_rules_delete_form';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->customfilter->deleteRule($this->rule['rid']);
    $this->customfilter->save();
    drupal_set_message($this->t('The rule %label has been deleted.',
      array('%label' => $this->rule['name'])));
    $form_state->setRedirect('customfilter.rules.list', array('customfilter' => $this->customfilter->id()));
  }
}
