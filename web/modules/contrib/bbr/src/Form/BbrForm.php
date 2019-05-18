<?php

namespace Drupal\bbr\Form;

use Drupal\Core\Form\FormBase;

use Drupal\Core\Form\FormStateInterface;

/**
 * Back Butoon Refresh form class.
 */
class BbrForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bbr';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'bbr/bbr_form';
    $form['bbr_field'] = array(
      '#prefix' => '<div style="display:none;">',
      '#type' => 'textfield',
      '#title' => 'Back Button Refresh',
      '#default_value' => 'no',
      '#attributes' => array('id' => 'bbr'),
      '#suffix' => '</div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(
        $this->t(
            '@emp_name ,Your application is being submitted!',
            array(
              '@emp_name' => $form_state->getValue('employee_name'),
            )
        )
    );
  }

}
