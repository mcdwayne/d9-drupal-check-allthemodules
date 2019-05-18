<?php

namespace Drupal\instant_solr_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Server;

class ServerEditForm extends FormBase {

  public function getFormId() {
    // Unique ID of the form.
    return 'instant_solr_index_server_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $server_details = NULL, $response_object = NULL, $index_expired = NULL) {
    // Create a $form API array.
    if ($index_expired == TRUE) {
      $form['#prefix'] = t('Your test index has expired and is no longer valid. Please remove it, and eventually create a new one.');
    }
    $response_value = $response_object->results[0][0];
    if ($response_value->isUnknown == FALSE && $response_value->isTemporary == FALSE) {
      $field_disable = FALSE;
    }
    else {
      $field_disable = TRUE;
    }
    $form['name'] = array(
      '#title' => t('Name:'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['name'],
    );

    $form['protocol'] = array(
      '#title' => t('Protocol:'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['protocol'],
    );
    $form['host'] = array(
      '#title' => t('Host'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['host'],
    );
    $form['port'] = array(
      '#title' => t('Port'),
      '#type' => 'textfield',
      '#default_value' => $server_details['port'],
    );
    $form['path'] = array(
      '#title' => t('Path'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['path'],
    );
    $form['key'] = array(
      '#title' => t('Key'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['key'],
    );
    $form['secret'] = array(
      '#title' => t('Secret'),
      '#type' => 'textfield',
      '#disabled' => $field_disable,
      '#default_value' => $server_details['secret'],
    );

    $form['purchase_server'] = array(
      '#markup' => $server_details['purchase_server'],
    );

    $form['submit_function'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#submit' => array('::' . $server_details['submit_function']),
    );

    $form['delete_function'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('::' . $server_details['delete_function']),
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
  }
  
  /**
   * Submit edited searchapi server on drupal.
   */
  public function instant_solr_index_searchapiserver_edit_submit(&$form, &$form_state) {
    $server_id = \Drupal::config('instant_solr_index.settings')->get('instant_solr_index_searchApiServer_id');
    $server = Server::load($server_id);
    $backend_config=$server->getBackendConfig();
    $values = $form_state->getValues();
    $paths = explode('/', $values['path']);
    $server->set('name', $values['name']);
    $backend_config['port']=$values['port'];
    $backend_config['scheme']=$values['protocol'];
    $backend_config['host']=$values['host'];
    $backend_config['path']=$values['path'];
    $backend_config['key']=$values['key'];
    $backend_config['secret']=$values['secret'];
    $backend_config['urlCore']=$paths[2];
    $server->setBackendConfig($backend_config);
    $server->save();
    $form_state->setRedirect('instant_solr_index.searchapi');
  }

  /**
   * Delete searchapi server from drupal.
   */
  public function instant_solr_index_searchapiserver_edit_delete(&$form, &$form_state) {
    $server_id = \Drupal::config('instant_solr_index.settings')->get('instant_solr_index_searchApiServer_id');
    $server = Server::load($server_id);
    $form_state->setRedirect(
      'entity.search_api_server.delete_form', array('search_api_server' => $server->id()), array('query' => array('destination' => 'admin/config/search/gotosolr/searchapi'))
    );
  }

}
