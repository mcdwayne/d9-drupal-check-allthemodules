<?php

namespace Drupal\sign_for_acknowledgement\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form builder for the sign_for_acknowledgement alternate form.
 */
class AlternateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sign_for_acknowledgement_alt_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $node = NULL) {
    $user =  \Drupal::currentUser();
	
    $form['user'] = array(
      '#type' => 'value',
      '#name' => 'user',
      '#value' => $user->id(),
    );
    $form['node'] = array(
      '#type' => 'hidden',
      '#name' => 'node',
      '#value' => $node->id(),
    );
    $do_ann = $node->get('annotation_field')->getValue();
    $required = $node->get('annotation_field_required')->getValue();
    if (isset($do_ann[0]['value']) && $do_ann[0]['value']) {
      $form['annotation'] = array(
        '#type' => 'textarea',
        '#title' => t('Annotation'),
        '#rows' => 4,
        '#cols' => 54,
        '#default_value' => '',
        '#resizable' => FALSE,
        '#required' => (isset($required[0]['value']) && $required[0]['value']),
        '#attributes' => array('style' => 'width: 90%'),
      );
    }
    $multi = FALSE;
    if ($node->alternate_form_multiselect) {
      $multi = $node->alternate_form_multiselect->value;
    }
    $labels = str_replace(["\r\n", "\r"], "\n", $node->alternate_form_text->value);
    $labels = explode( "\n", $labels);//$node->get('alternate_form_text')->getValue();
    $options = array();
    foreach ($labels as $label) {
      $val = \Drupal\Component\Utility\Xss::filter($label);
      if (empty(trim($val))) {
          continue;
      }
      $options[$val] = $val;
    }
    $form['selection'] = array(
      '#type' => $multi? 'checkboxes' : 'radios',
      '#title' =>  $multi? t('Multiselect agreement') : t('Agreement'),
      '#options' => $options,
      //'#default_value' => 0,
      '#required' => TRUE,
    );
    $form['signature_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
    $userid = $form_state->getValue('user');
    $nodeid = $form_state->getValue('node');
    $select_val = $form_state->getValue('selection');
    if (is_array($select_val)) {
      $selection = '';
      foreach($select_val as $k => $val) {
        if ($k !== $val) {
          continue;
        }
        if (strlen($selection)) {
          $selection .= '|';
        }
        $selection .= $val;
      }
    }
    else { // single select
      $selection = $select_val;
    }
    $annotation = $form_state->getValue('annotation');
    if ($dbman->alternateSignDocument($userid, $nodeid, $selection, $annotation)) {
      drupal_set_message(t('Document has been signed.'));
    }
  }
}
