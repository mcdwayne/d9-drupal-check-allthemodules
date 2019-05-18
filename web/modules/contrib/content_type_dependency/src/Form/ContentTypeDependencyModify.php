<?php

namespace Drupal\content_type_dependency\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

class ContentTypeDependencyModify extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_type_dependency_modify';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    // Removed Anonymous & Authenticated user from check box list.
    $roles = Role::loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $avail_type = array_keys($roles);
    $avail_roles_new = array();
    foreach ($avail_type as $role_value) {
      $avail_roles_new[$role_value] = $role_value;
    }

    // Rule edit form & new rule form.
    $node_type_get_types = array();
    foreach (NodeType::loadMultiple() as $type) {
      $node_type_get_types[$type->id()] = $type->label();
    }
    $content_types = array();
    $content_types[] = '-Select-';
    foreach ($node_type_get_types as $mechine_name => $human_read) {
      $content_types[$mechine_name] = $human_read;
    }
    $record = array();
    if (isset($_GET['cd_id'])) {
      $db = \Drupal::database();
      $result = $db->select('content_type_dependency','c')
        ->fields('c')
        ->condition('cd_id', $_GET['cd_id'])
        ->execute();
      $record = $result->fetchObject();
    }
    $form['to_create_modify'] = array(
      '#title' => $this->t('To create'),
      '#type' => 'select',
      '#description' => t('The content type that the following content creation dependency rules apply to'),
      '#options' => $content_types,
      '#weight' => 1,
      '#required' => TRUE,
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? $record->to_create : '-Select-',
    );
    $form['must_have_fields_modify'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Condition'),
      '#weight' => 2,
    );
    $form['must_have_fields_modify']['must_have_modify'] = array(
      '#title' => $this->t('Must have'),
      '#type' => 'select',
      '#description' => $this->t('This content type has to be created before being able to create the "To Create" content type'),
      '#options' => $content_types,
      '#required' => TRUE,
      '#weight' => 3,
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? $record->must_have : '-Select-',
    );
    $form['must_have_fields_modify']['no_of_modify'] = array(
      '#title' => $this->t('No. of'),
      '#description' => $this->t('The total number of contents the user has to create of this content type'),
      '#type' => 'textfield',
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? $record->no_of : '',
      '#size' => 6,
      '#required' => TRUE,
      '#maxlength' => 3,
      '#weight' => 4,
    );
    $form['must_have_fields_modify']['role'] = array(
      '#title' => $this->t('User Roles'),
      '#type' => 'checkboxes',
      '#weight' => 5,
      '#description' => $this->t('Apply only for the selected role(s).'),
      '#options' => $avail_roles_new,
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? unserialize($record->role) : [],
    );
    $form['enable_modify'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable'),
      '#weight' => 6,
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? $record->status : 0,
    );
    $form['display_message_modify'] = array(
      '#title' => $this->t('Display message'),
      '#description' => $this->t("The drupal message to be displayed when the user tries to create the 'Must have' content type, and still has to create more of the prerequisite content type.<br />Leave empty for Default. Example: You must have 4 Articles created to continue."),
      '#type' => 'textarea',
      '#default_value' => (isset($_GET['cd_id']) && $_GET['cd_id']) ? ($record->default_message ? '' : $record->message) : '',
      '#rows' => 4,
      '#cols' => 20,
      '#weight' => 5,
    );
    $form['Submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 10,
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // User selects the same content types on each drop down list.
    if ($form_state->getValue(['to_create_modify']) == $form_state->getValue(['must_have_modify'])) {
      $form_state->setErrorByName('cd_both', t('You should select different content types'));
    }
    $db = \Drupal::database();
    // Checking rule exists in rule edit form.
    if (isset($_GET['cd_id'])) {
      $result = $db->select('content_type_dependency','c')
        ->fields('c')
        ->condition('to_create', $form_state->getValue(['to_create_modify']), '=')
        ->condition('must_have', $form_state->getValue(['must_have_modify']), '=')
        ->condition('cd_id', $_GET['cd_id'], '!=')
        ->execute();
      // Redirect URL.
      $url = base_path().'admin/config/content/content_type_dependency/list';
      // If rule already created with this content types.
      if (count($result->fetchAll()) >= 1) {
        $form_state->setErrorByName('cd_exists', t('The selected Content Dependency already exists. View the <a href="@url">List</a> of already existing dependencies'
          , ['@url' => $url]
        ));
      }
    }
//    // Checking rule exists in new rule form.
    else {
      $result = $db->select('content_type_dependency','c')
        ->fields('c')
        ->condition('to_create', $form_state->getValue(['to_create_modify']), '=')
        ->condition('must_have', $form_state->getValue(['must_have_modify']), '=')
        ->execute();
      // Redirect url.
      $url = base_path().'admin/config/content/content_type_dependency/list';
      // If rule already created with this content types.
      if (count($result->fetchAll()) >= 1) {
        $form_state->setErrorByName('cd_exists', t('The selected Content Dependency already exists. View the <a href="@url">List</a> of already existing dependencies', [
          '@url' => $url
        ]));
      }
    }
      $query = $db->select('content_type_dependency','c')
        ->fields('c')
        ->condition('to_create', $form_state->getValue(['must_have_modify']), '=')
        ->condition('must_have', $form_state->getValue(['to_create_modify']), '=')
        ->execute();
      // Redirect URL.
      $url = base_path().'admin/config/content/content_type_dependency/list';
      // If rule already created with this content types.
      if (count($query->fetchAll()) >= 1) {
        $form_state->setErrorByName('cd_wrong', t('You are not allow to create reverse rule. View the <a href="@url">List</a> of already existing dependencies'
          , ['@url' => $url]
        ));
      }
    // Restrict user input to minimum 1.
    if ($form_state->getValue(['no_of_modify']) < 1) {
      $form_state->setErrorByName('cd_no_of', t('The number of contents to create should be minimum 1'));
    }
    // Restrict user input type to number.
    if (!is_numeric($form_state->getValue([
      'no_of_modify'
    ]))
    ) {
      $form_state->setErrorByName('cd_no_of_numeric', t('The value of the "No of" field should be a number'));
    }

    if (is_integer(array_values($form_state->getValue(['role']))[0])) {
      drupal_set_message("No role selected", "warning");
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store form_state values in variables.
    $no_of = $form_state->getValue(['no_of_modify']);
    $must_have = $form_state->getValue(['must_have_modify']);
    $to_create = $form_state->getValue(['to_create_modify']);
    $status = $form_state->getValue(['enable_modify']);
    $role = $form_state->getValue(['role']);

    // Setting default message if text area is empty.
    if ($form_state->getValue(['display_message_modify']) == '') {
      $message = 'You must create ' . $no_of . ' ' . $must_have . ' to continue ';
      $default_message = 1;
    }
    else {
      $message = $form_state->getValue(['display_message_modify']);
      $default_message = 0;
    }
    // Display enabled or disabled on message status.
    if ($status == 1) {
      $msg = $this->t('Enabled');
    }
    else {
      $msg = $this->t('Disabled');
    }
    // echo '<pre>'; print_r(); exit;
    // Form submission for editing.
    if (isset($_GET['cd_id'])) {
      \Drupal::database()->update('content_type_dependency')->fields([
        'to_create' => $to_create,
        'must_have' => $must_have,
        'no_of' => $no_of,
        'role' => serialize($role),
        'message' => $message,
        'status' => $status,
        'default_message' => trim($default_message),
      ])->condition('cd_id', $_GET['cd_id'])
        ->execute();
      drupal_set_message(t('content dependency has been updated.'));
    }
    // New rule form submission.
    else {
      \Drupal::database()->insert('content_type_dependency')
        ->fields([
          'to_create' => $to_create,
          'must_have' => $must_have,
          'no_of' => $no_of,
          'role' => serialize($role),
          'message' => $message,
          'status' => $status,
          'default_message' => trim($default_message),
        ])->execute();
      drupal_set_message($this->t('New content dependency created & @msg', [
        '@msg' => $msg
      ]));
    }
    $form_state->setRedirect('content_type_dependency.list');
  }
}
