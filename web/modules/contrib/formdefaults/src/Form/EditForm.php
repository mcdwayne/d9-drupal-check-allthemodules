<?php

namespace Drupal\Formdefaults\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formdefaults\Helper\FormDefaultsHelper;
use Drupal\Core\Link;

class EditForm extends FormBase {
  public function getFormId() {
    return 'formdefaults_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $path_args = explode('/', current_path());
    $formid = $path_args[1];
    $fieldname = $path_args[2];
    // Load the form
    $data = formdefaults_getform($formid);
    $fields = array();
    $form['formid'] = array(
       '#type' => 'value',
       '#value' => $formid,
       );

    foreach ($data as $f => $field) if (strpos($f, '#')!==0) {
      $t = @$field['title'] ? ' - ' . @$field['title']:'';
      $fields[$f] = Link::createFromRoute(t($f . $t), 'formdefaults.edit', [
        'formid' => $formid,'field' => urlencode($f)
      ]);
    }

    $form['fields'] = array(
       '#type' => 'checkboxes',
       '#title' => 'Overriden Fields',
       '#options' => $fields,
    );

    $form['add'] = array('#type' => 'fieldset',
                         '#title' => 'Add Fields',
                         '#collapsible' => TRUE,
                         '#collapsed' => TRUE,
    );

    $types = array('markup' => 'Markup', 'fieldset' => 'Collapsed fieldset with markup ');
    $form['add']['field_type'] = array(
       '#type' => 'select',
       '#title' => 'Type',
       '#options' => $types,
       '#description' => t('Choose Markup to add a place for instructions that are always seen.  Choose collapsed fieldset to add instructions inside an expandable box')
    );

    // Weight of
    $weight_range = range(-50, 50);
    $weights=array('unset' => 'unset');
    foreach ($weight_range as $weight) $weights[(string)$weight]=(string)$weight;

    $form['add']['weight'] = array(
      '#type' => 'select',
      '#title' => 'Weight',
      '#options' => $weights,
      '#default_value' => -49,
      '#description' => 'Controls placement within the form, -49 is a good header value or 50 is usually a good footer value',
    );

    $form['add']['add_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Add',
    );


    $form['reset'] = array('#type' => 'submit', '#value' => 'Reset Selected');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $formid = $form_values['formid'];
    $formdef = formdefaults_getform($formid);

    // Reset fields
    if ($_POST['op'] == 'Reset Selected') {
      foreach ($form_values['fields'] as $field => $checked) {
        if ($checked) {
          unset($formdef[$field]);
        }
      }

      // Condense addon array.
      if (isset($formdef['#formdefaults_addon_fields'])) {
         $addons = (array)$formdef['#formdefaults_addon_fields'];
         $new_addons = array();

         foreach ($addons as $key => $field) {
           if (@$formdef[$key]) {
             $i = 'formdefaults_' . count($new_addons);

             if ($i != $key) {
               $formdef[$i] = $formdef[$key];
               unset($formdef[$key]);
               if ($formdef[$i . '_markup']) {
                 $formdef[$i . '_markup'] = $formdef[$key . '_markup'];
                 unset($formdef[$key . '_markup']);
               }
             }
             $new_addons[$i] = $field;
           }
         }
         $formdef['#formdefaults_addon_fields'] = $new_addons;
      }

    }


    if ($_POST['op'] == 'Add') {
      $i = count((array)@$formdef['#formdefaults_addon_fields']);
      $key = 'formdefaults_' . $i;
      $subkey = $key . '_markup';
      $field = array();
      $weight = $form_values['weight'];
      switch ($form_values['field_type']) {
        case "markup":
          $field = array('#type' => 'markup', '#markup' => '' );

          $formdef[$key] = array('type' => 'markup', 'value' => '<p>Replace with your own markup</p>', 'format' => 0, 'weight' => $weight);
          break;
        case "fieldset":
          $field = array('#type' => 'fieldset',
                         '#title' => 'Untitled',
                         '#collapsible' => TRUE,
                         '#collapsed' => TRUE,
                         $subkey => array('#type' => 'markup', '#value' => ''),
                         );
          $formdef[$key] = array('type' => 'fieldset', 'title' => 'Untitled', 'weight' => $weight);
          $formdef[$subkey] = array('type' => 'markup', 'value' => '<p>Replace with your own markup</p>');
          break;

      }
      $formdef['#formdefaults_addon_fields'][$key] = $field;
    }
    $helper = new FormDefaultsHelper();
    $helper->saveForm($formid, $formdef);
  }
}
