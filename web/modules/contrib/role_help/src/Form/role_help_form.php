<?php

namespace Drupal\role_help\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Connection;

class role_help_form extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_help_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    // TODO: Implement getEditableConfigNames() method.
//    return ['role_help.settings'];
  }


  public function buildForm(array $form, FormStateInterface $form_state) {

    $result = \Drupal::database()->select('role_help', 'r');
    $result->fields('r', ['rid', 'help','summary']);
    $result->range(0, 2);
    $data = $result->execute()->fetchAll();
    $help = array();
    $summary = array();
    foreach($data as $values){
      $help[] = $values->help;
      $summary[]=$values->summary;

    }

    $form['anonymous_help'] = array(
    '#type' => 'textarea',
    '#title' => t('Anonymous user'),
    '#default_value' => $help[1] ,
    '#description' => t('A description of what an anonymous user can accomplish. You should only set this if anonymous users have rights beyond accessing content. If non-empty, this will be shown to anonymous users on the site help page.'),
  );
   $form['authenticated_help'] = array(
    '#type' => 'textarea',
    '#title' => t('Authenticated user'),
    '#default_value' =>$help[0],
    '#description' => t('A description of what an authenticated user can accomplish. If non-empty, this will be shown to authenticated users on the site help page.'),
  );

  $form['anonymous_summary'] = array(
    '#type' => 'textfield',
    '#title' => t('Anonymous user summary'),
    '#default_value' => $summary[1],
    '#description' => t('Summary of what permissions are assigned to this role, and how the role is used. This will be shown on the role list and user profile edit pages.'),
  );
  $form['authenticated_summary'] = array(
    '#type' => 'textfield',
    '#title' => t('Authenticated user summary'),
    '#default_value' => $summary[0],
    '#description' => t('Summary of what permissions are assigned to this role, and how the role is used. This will be shown on the role list and user profile edit pages.'),
  );

  $form['role_help_format'] = array(
    '#type' => 'text_format',
    '#base_type' => 'value',
    '#default_value' => '',
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save configuration'),
  );

  return $form;
  }


  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $result = \Drupal::database()->select('role_help', 'r');
    $result->fields('r', ['rid','role_name', 'help','summary']);
    $result->range(0, 2);
    $data = $result->execute()->fetchAll();
//    $help = array();
//    $summary = array();
    $role_name=array();
    $rid=array();
    foreach($data as $values){
//      $help[] = $values->help;
//      $summary[]=$values->summary;
      $role_name[]=$values->role_name;
      $rid[]= $values->rid;
    }

    $help_auth = $form_state->getValue('authenticated_help');
    $help_anon = $form_state->getValue('anonymous_help');
    $summary_auth = $form_state->getValue('authenticated_summary');
    $summary_anon = $form_state->getValue('anonymous_summary');
    $fields=array(
      array(
      'role_name'=> 'administrator',
      'help' => $help_auth,
      'summary' =>  $summary_auth ,
      ),
      array(
      'role_name'=> 'anonymous',
      'help' => $help_anon,
      'summary' =>  $summary_anon ,
      ),
      );

    if($role_name[0]=='administrator' && $role_name[1]=='anonymous') {
      $query = \Drupal::database();
      $query->update('role_help')->fields($fields[0])->condition('rid',$rid[0])->execute();
      $query->update('role_help')->fields($fields[1])->condition('rid',$rid[1])->execute();
      drupal_set_message(t('The role help settings have been saved.'));
    }
    else{
      $query = \Drupal::database();
      $query->insert('role_help')->fields($fields[0])->execute();
      $query->insert('role_help')->fields($fields[1])->execute();
      drupal_set_message(t('The role help settings have been saved.'));
    }

  }

}
