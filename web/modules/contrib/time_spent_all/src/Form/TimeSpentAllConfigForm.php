<?php

/**
 * @file
 * Contains \Drupal\time_spent_all\Form\TimeSpentConfigForm.
 */

namespace Drupal\time_spent_all\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class TimeSpentAllConfigForm extends ConfigFormBase {
  public function getFormId() {
    return 'time_spent_all_config_form';
  }
  public function getEditableConfigNames() {
    return [
      'time_spent_all.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form['who_counts'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Specify what and who this module will track'),
      '#description' => $this->t('Set the node types and roles you want to have statistics. All them are tracked by default.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    );
    $form['psq_track'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Specify track information to mail'),
      '#description' => $this->t('Specify track information to mail'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );


    // Form an array of node types to be used in the config form
    $types = array();
    $node_types = NodeType::loadMultiple();
    foreach ($node_types as $key => $type) {
      $types[$key] = t($key);
    }

    // Form an array of user roles to be used in the config form
    $user_roles['all'] = 'all';
    foreach(user_roles() as $key => $role) {
      $user_roles[$key] = $key;
    }

    $form['who_counts']['time_spent_all_node_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Node types'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_node_types'),
      '#options' => $types
    );
    $form['who_counts']['time_spent_all_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_roles'),
      '#description' => t('If you want to track anonymous users, use Google Analytics.'),
      '#options' => $user_roles
    );
    $form['who_counts']['time_spent_all_timer'] = array(
      '#type' => 'textfield',
      '#title' => t('Seconds interval'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_timer'),
      '#description' => t('We need to check by ajax if the user is on page yet. Define here the amount of time between one call and another.'),
    );
    $form['who_counts']['time_spent_all_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Define in minutes how long these ajax call should be tracked'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_limit'),
      '#description' => t('As we are using ajax call, session will never expire. So we need to avoid continuos tracking if the user left the chair with the page open.'),
    );

    $form['time_spent_all_pager_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Define the number of records after which the pager should be displayed'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_pager_limit'),
      '#description' => t('Report would be listing all the nodes on which a user has spent time. This allows us to show a paged listing.'),
    );
    $form['psq_track']['time_spent_all_mail_attach'] = array(
      '#type' => 'textfield',
      '#title' => t('Define the weform mail id'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_mail_attach'),
      '#description' => t('Define the weform mail id to attach tracking data'),
    );
    $form['psq_track']['time_spent_all_home_nide_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Define the home node id'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_home_nide_id'),
      '#description' => t('Define the home node id for home page'),
    );
    $form['psq_track']['time_spent_all_ip_exclude'] = array(
      '#type' => 'textfield',
      '#title' => t('Define IP Address to exclue in mail'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_ip_exclude'),
      '#description' => t('Define IP Address to exclue if multiple give space'),
    );
    $form['psq_track']['time_spent_all_ip_exclude_tracking'] = array(
      '#type' => 'textfield',
      '#title' => t('Define IP Address to exclue tracking'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_ip_exclude_tracking'),
      '#description' => t('Define IP Address to exclue if multiple give space'),
    );
    $form['who_counts']['time_spent_all_client_timing'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use client-side timing'),
      '#description' => t('Enables client-side timing that is sent to the backend when a user leaves the page. This allows setting a longer <em>seconds interval</em> above. <strong>Note</strong>: Since this time is tracked client-side, a sufficiently savy-user may be able to fake this data.'),
      '#default_value' => $this->config('time_spent_all.settings')->get('time_spent_all_client_timing'),
    );
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $userInputValues = $form_state->getUserInput();
    $config = $this->configFactory->getEditable('time_spent_all.settings');
    $config->set('time_spent_all_node_types', $userInputValues['time_spent_all_node_types']);
    $config->set('time_spent_all_pager_limit', $userInputValues['time_spent_all_pager_limit']);
    $config->set('time_spent_all_roles', $userInputValues['time_spent_all_roles']);
    $config->set('time_spent_all_timer', $userInputValues['time_spent_all_timer']);
    $config->set('time_spent_all_limit', $userInputValues['time_spent_all_limit']);
    $config->set('time_spent_all_mail_attach', $userInputValues['time_spent_all_mail_attach']);
    $config->set('time_spent_all_home_nide_id', $userInputValues['time_spent_all_home_nide_id']);
    $config->set('time_spent_all_ip_exclude', $userInputValues['time_spent_all_ip_exclude']);
     $config->set('time_spent_all_ip_exclude_tracking', $userInputValues['time_spent_all_ip_exclude_tracking']);
    
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
