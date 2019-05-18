<?php

namespace Drupal\demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * This class returns the demo_config_manage_form in which there will be a button to create the snapshot of the configuration.
 */
class DemoConfigManageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_config_manage_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['actions'] = ['#type' => 'actions'];
    $form['description'] = [
      '#type' => 'item',
      '#description' => t('This will create snapshot of the configuration of your drupal site.'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('+ Create snapshot'),
      '#attributes' => ['class' => ['btn-primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->setRedirect('demo.export_download')) {
      drupal_set_message(t('Snapshot has been created.'));
    }
    else {
      drupal_set_message(t('Snapshot not created.'));
    }
  }

}
