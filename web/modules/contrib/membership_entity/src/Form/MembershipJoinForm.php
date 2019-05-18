<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class JoinForm.
 */
class MembershipJoinForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'membership_entity_join_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['member_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Member ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      $this->messenger()->addMessage($key . ': ' . $value);
    }
  }

}
