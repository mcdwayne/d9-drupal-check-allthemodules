<?php

namespace Drupal\japan_postal_code\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Edit config form.
 */
class ConfigForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'japan_postal_code_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = '') {
    $count = _japan_postal_code_count_postal_code_records();

    $form['count'] = [
      '#markup' => $this->t('%count records exist in the postal code database table.', ['%count' => $count]),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'update' => [
        '#type' => 'submit',
        '#value' => $this->t('Fetch and update the postal code data'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $count = _japan_postal_code_update_all_postal_code();
    if ($count === FALSE) {
      drupal_set_message($this->t('Japan postal code database table update failed.'), 'error');
    }
    else {
      drupal_set_message($this->t('Japan postal code database table is successfully updated. %count address inserted.', ['%count' => $count]));
    }
  }

}
