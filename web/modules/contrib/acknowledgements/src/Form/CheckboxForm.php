<?php

namespace Drupal\sign_for_acknowledgement\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form builder for the sign_for_acknowledgement basic checkbox form.
 */
class CheckboxForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sign_for_acknowledgement_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $submit = FALSE, $node = NULL) {
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
    $form['signature'] = array(
        '#type' => 'checkbox',
        '#required' => TRUE,
        '#attributes' => $submit ? NULL : array(
          'onclick' => 'this.form.submit()',
        ),
        '#title' => t('Click here to confirm you have read the document'),
      );
    $form['signature_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#attributes' => $submit ? NULL :  array(
        'style' => 'display:none',
      ),
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
    $checked = $form_state->getValue('signature');
    $annotation = $form_state->getValue('annotation');
    if (!$checked) {
      drupal_set_message(t('Data not saved, please select the checkbox first.'),'warning');
      return;
    }
    if ($dbman->signDocument($userid, $nodeid, $annotation)) {
      drupal_set_message(t('Document has been signed.'));
    }
  }
}
