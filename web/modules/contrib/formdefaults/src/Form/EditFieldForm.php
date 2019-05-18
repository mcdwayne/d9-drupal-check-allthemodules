<?php

namespace Drupal\Formdefaults\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formdefaults\Helper\FormDefaultsHelper;

/**
 * @class EditFieldForm
 *
 * Form to edit the field title and description.
 */
class EditFieldForm extends FormBase {
  public function getFormId() {
    return 'formdefaults_edit_field';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_array = $_SESSION['formdefaults_forms'];
    $path_args = explode('/', current_path());
    $formid = $path_args[1];
    $fieldname = $path_args[2];

    $originalfields = @$form_array[$formid][$fieldname]? $form_array[$formid][$fieldname]:array();
    $savedform = formdefaults_getform($formid);
    $weight_range = range(-50, 50);
    $weights=array('unset' => 'unset');

    foreach ($weight_range as $weight) $weights[(string)$weight]=(string)$weight;

    if (is_array(@$savedform[$fieldname])) $formfields = array_merge($originalfields, @$savedform[$fieldname]);
    else $formfields = $originalfields;
    $type = $formfields['type'];
    if (!$type) {
      if (isset($formfields['input_format'])) $type = 'markup';
    }
    if (@$originalfields['type']) $type = $originalfields['type'];

    $form['formid'] = array(
       '#type' => 'value',
       '#value' => $formid,
       );

    $form['fieldname'] = array(
       '#type' => 'value',
       '#value' => $fieldname,
       );

    $form['type'] = array(
       '#type' => 'value',
       '#title' => 'Field Type',
       '#value' => $type,
       );

    $form['warning'] = array(
       '#type' => 'markup',
       '#value' => 'Some text to edit',
       );

    $form['hide_it'] = array(
       '#type' => 'checkbox',
       '#title' => 'Hide this field',
       '#description' => 'Checking this box will convert the field to a hidden field.' .
           ' You will need to use the edit form link to unhide them.',
       '#default_value' => @$formfields['hide_it']
    );


    if ($type == 'markup') {
      $form['value'] = array(
         '#type' => 'text_format',
         '#title' => 'Text or markup',
         '#rows' => 30,
         '#cols' => 80,
         '#format' => @$formfields['input_format'],
         '#default_value' => @$formfields['value'],
         );

      $form['value_original'] = array(
        '#type' => 'item',
        '#title' => 'Original value',
        '#value' => @$originalfields['value'],
        );
    }
    else {
      $form['title'] = array(
         '#type' => 'textfield',
         '#title' => 'Field Title',
         '#default_value' => @$formfields['title'],
         );

      $form['title_old'] = array(
         '#type' => 'item',
         '#title' => 'Original Title',
         '#value' => @$originalfields['title'],
         );

      $form['description'] = array(
         '#type' => 'textarea',
         '#title' => 'Field Description',
         '#default_value' => $formfields['description'],
         '#rows' => 30,
         '#cols' => 80,
         );

      $form['description_old'] = array(
         '#type' => 'item',
         '#title' => 'Original Description',
         '#value' => $originalfields['description'],
         );

    }
    if ($type == 'fieldset' ) {
      $truefalse = array('' => 'Leave alone', TRUE => 'Yes', FALSE => 'No');
      $form['collapsible'] = array(
        '#type' => 'radios',
        '#title' => 'Collapsible',
        '#options' => $truefalse,
        '#default_value' => @$formfields['collapsible'],
      );

      $form['collapsed'] = array(
        '#type' => 'radios',
        '#title' => 'Collapsed',
        '#options' => $truefalse,
        '#default_value' => @$formfields['collapsed'],
      );
    }
    $form['weight'] = array(
         '#type' => 'select',
         '#title' => 'Weight',
         '#options' => $weights,
         '#default_value' => @$formfields['weight'],
         '#description' => 'Higher values appear near at the top of the form, lower values at the bottom.',
         );
    $form['weight_old'] = array(
         '#type' => 'item',
         '#title' => 'Original Weight',
         '#value' => @$originalfields['weight'],
         );

    $form['submit'] = array(
       '#type' => 'submit',
       '#value' => 'Save',
       );

    $form['reset'] = array(
       '#type' => 'submit',
       '#value'  => 'Reset',
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $formid = $form_values['formid'];
    $fieldname = $form_values['fieldname'];
    $formarray=formdefaults_getform($formid);
    $baseform = $formarray;
    // set the form values
    if ($_POST['op']=='Reset') {
      unset($formarray[$fieldname]);
    }
    else {
      if ($form_values['type'] == 'markup') {
        $formarray[$fieldname]['value'] = $form_values['value']['value'];
        $formarray[$fieldname]['input_format'] = $form_values['value']['format'];
      }
      else {
        $formarray[$fieldname]['title'] = $form_values['title'];
        $formarray[$fieldname]['description'] = $form_values['description'];
      }
      if (@$form_values['collapsible'] === '') {
        unset($formarray[$fieldname]['collapsible']);
      }
      else {
        $formarray[$fieldname]['collapsible'] = @$form_values['collapsible'];
      }
      if (@$form_values['collapsed'] === '') {
        unset($formarray[$fieldname]['collapsed']);
      }
      else {
        $formarray[$fieldname]['collapsed'] = @$form_values['collapsed'];
      }
      $formarray[$fieldname]['hide_it'] =$form_values['hide_it'];
      $formarray[$fieldname]['weight'] = $form_values['weight'];
      $formarray[$fieldname]['type'] = $form_values['type'];
    }
    $helper = new FormDefaultsHelper();
    $helper->saveForm($formid, $formarray);
    $form_state->setRedirect('formdefaults.edit_w_formid', array('formid' => $formid));
  }
}
