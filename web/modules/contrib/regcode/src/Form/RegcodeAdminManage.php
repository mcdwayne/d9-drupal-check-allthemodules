<?php

/**
 * @file
 * Contains \Drupal\regcode\Form\RegcodeAdminManage.
 */

namespace Drupal\regcode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class RegcodeAdminManage extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'regcode_admin_manage';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];

    $operations = [
      REGCODE_CLEAN_TRUNCATE => t('Delete all registration codes'),
      REGCODE_CLEAN_EXPIRED => t('Delete all expired codes'),
      REGCODE_CLEAN_INACTIVE => t('Delete all inactive codes'),
    ];
    $form['regcode_operations'] = [
      '#type' => 'checkboxes',
      '#title' => t('Operations'),
      '#description' => t('This operation cannot be undone.'),
      '#options' => $operations,
    ];

    $form['regcode_submit'] = [
      '#type' => 'submit',
      '#value' => t('Perform operations'),
    ];

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $operations = $form_state->getValue(['regcode_operations']);
    foreach ($operations as $operation) {
      switch ($operation) {
        case REGCODE_CLEAN_TRUNCATE:
          regcode_clean(REGCODE_CLEAN_TRUNCATE);
          drupal_set_message(t('All registration codes were deleted.'));
          break;

        case REGCODE_CLEAN_EXPIRED:
          regcode_clean(REGCODE_CLEAN_EXPIRED);
          drupal_set_message(t('All expired registration codes were deleted.'));
          break;

        case REGCODE_CLEAN_INACTIVE:
          regcode_clean(REGCODE_CLEAN_INACTIVE);
          drupal_set_message(t('All inactive registration codes were deleted.'));
          break;
      }
    }
  }

}
