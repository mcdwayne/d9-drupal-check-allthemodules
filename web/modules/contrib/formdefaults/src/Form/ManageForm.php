<?php

namespace Drupal\Formdefaults\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\formdefaults\Helper\FormDefaultsHelper;

class ManageForm extends FormBase {
  public function getFormId() {
    return 'formdefaults_manage';
  }

  /**
   * Form management form used for inspecting and resetting forms.
   *
   * @return Form
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $search_str = @$_SESSION['formdefaults_search'];
    $form['search_str'] = array(
      '#type' => 'textfield',
      '#default_value' => $search_str,
      '#description' => t('Search all forms that have a formid (name) containing the word you specify.'),
    );

    $form['search'] = array(
        '#type' => 'submit',
        '#value' => 'Search',
        '#size' => 10,
    );

    $form['results'] = array(
        '#type' => 'fieldset',
        '#title' => 'Overridden Forms',
        '#tree' => TRUE,
    );

    $helper = new FormDefaultsHelper();
    $form_list = $helper->search($search_str);
    $list = array();
    foreach ($form_list as $form_key => $f) {
      $list[$form_key]= Link::createFromRoute(
        t($form_key),
        'formdefaults.edit_w_formid', [
          'formid' => $form_key,
      ]);
    }

    $form['results']['reset_forms'] = array('#type' => 'checkboxes',
                                            '#options' => $list);
    $form['results']['reset'] = array('#type' => 'submit',
                                      '#value' => 'Reset Selected');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $_SESSION['formdefaults_search'] = $form_values['search_str'];
    if ($form_values['results']['reset_forms']) {
      foreach ($form_values['results']['reset_forms'] as $form) {
        if ($form) {
          $helper = new FormDefaultsHelper();
          $helper->deleteForm($form);
        }
      }
    }
  }
}
