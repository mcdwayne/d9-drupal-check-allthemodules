<?php
/**
 * @file
 * Contains \Drupal\signed_nodes\Form\SignedNodesForm.
 **/
   
namespace Drupal\signed_nodes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SignedNodesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'signed_node_agreement_form';
  }
   
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {

    $this->id = $id;
    (object)$obj = "";
    if ($id != NULL) {
      $obj = signed_nodes_load_all($id);
    }


    // Return array of Form API elements.

    $form['nid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Node ID'),
      '#maxlength' => '254',
      '#required' => TRUE,
      '#default_value' => (!empty($obj->nid)) ? $obj->nid : '',
    );
    $form['year'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Sign for Year'),
      '#maxlength' => '4',
      '#required' => TRUE,
      '#default_value' => (!empty($obj->year)) ? $obj->year : date('Y'),
    );

    $form['purge_year'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Years to Purge User signed nodes'),
      '#maxlength' => '2',
      '#required' => TRUE,
      '#default_value' => (!empty($obj->purge_year)) ? $obj->purge_year : 0,
    );

    $form['agreement_message'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Node agreement to sign'),
      '#required' => TRUE,
      '#default_value' => (!empty($obj->agreement_message)) ? $obj->agreement_message : '',
    );

    $form['reporting_profile_fields'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Profile fields in reporting'),
      '#description' => $this->t('Add comma separated list of Title|profilefields e.g. Title|profile_title,Extension|profile_extension'),
      '#default_value' => '',
    );

    $submit_label = $this->t('Add Node Aggreement');;
    if ($id != NULL) {
      $submit_label = $this->t('Edit Node Aggreement');
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $submit_label,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation covered in later recipe, required to satisfy interface

    $nid = $year = 0;
    // Make sure nid and year are unique.
    if (!$form_state->isValueEmpty('nid')) {
      $nid = $form_state->getValue('nid');
    }
    if (!$form_state->isValueEmpty('year')) {
      $year = $form_state->getValue('year');
    }

    $snid_years = signed_nodes_get_year($nid);
    if (in_array($year, $snid_years)) {
      $form_state->setErrorByName('year', t('Agreement for node id = %name for the year = %year already exists. Please enter another year.',
        array('%name' => $nid, '%year' => $year)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($this->id) {
      $connection = \Drupal::database();
      $update = $connection->update('signed_nodes')
        ->fields([
          'nid' => $form_state->getValue('nid'),
          'year' => $form_state->getValue('year'),
          'purge_year' => $form_state->getValue('purge_year'),
          'agreement_message' => $form_state->getValue('agreement_message'),
        ])
        ->condition('snid', $this->id, '=')
        ->execute();

      $message = t('Updated signed node agreement for Node ID =  %name', array('%name' => $form_state->getValue('nid')));
      \Drupal::logger('signed_nodes')->notice($message);

      drupal_set_message(t('The node agreement for Node ID = %name was updated.',
        array('%name' => $form_state->getValue('nid'))), 'status');
    }
    else {
      $connection = \Drupal::database();
      $insert = $connection->insert('signed_nodes')
        ->fields([
          'nid' => $form_state->getValue('nid'),
          'year' => $form_state->getValue('year'),
          'purge_year' => $form_state->getValue('purge_year'),
          'agreement_message' => $form_state->getValue('agreement_message'),
        ])
        ->execute();

      // Logs a notice
      $message = t('Created signed node agreement for Node ID =  %name', array('%name' => $form_state->getValue('nid')));
      \Drupal::logger('signed_nodes')->notice($message);

      drupal_set_message(t('The node agreement for Node ID = %name was created.',
        array('%name' => $form_state->getValue('nid'))), 'status');
    }

    $form_state->setRedirect('signed_nodes.adminlistpage');
  }
}