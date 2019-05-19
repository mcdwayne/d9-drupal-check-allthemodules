<?php

namespace Drupal\sign_for_acknowledgement\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\sign_for_acknowledgement\Service\AcknowledgementsNodeFields;
use Drupal\sign_for_acknowledgement\Service\AcknowledgementsDatabase;

/**
 * Form builder for the sign_for_acknowledgement basic settings form.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sign_for_acknowledgement_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sign_for_acknowledgement.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sign_for_acknowledgement.settings');

	$my_nodes = array();
  //node_types_rebuild();
  $node_types = NodeType::loadMultiple();
  foreach ($node_types as $node_type) {
    $type = $node_type->get('type');
    $my_nodes[$type] = $type;
  }

  $form['content1'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node types'),
  );
  $form['content1']['node_types'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Node types to be handled by the module'),
    '#default_value' => $config->get('node_types'),
    '#options' => array_map('\Drupal\Component\Utility\Html::escape', $my_nodes),
    '#multiple' => TRUE,
  );
  $form['content2'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Messages'),
  );

  $form['content2']['signed_ok'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Replacement of: ') . $this->t('signed ok...'),
    '#default_value' => $config->get('signed_ok'),
  );

  $form['content2']['to_be_signed'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Replacement of: ') . $this->t('still to be signed...'),
    '#default_value' => $config->get('to_be_signed'),
  );

  $form['content2']['terms_expired'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Replacement of: ') . $this->t('terms have expired...'),
    '#default_value' => $config->get('terms_expired'),
  );

  $form['content2']['out_of_terms'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Replacement of: ') . $this->t('signed out of terms...'),
    '#default_value' => $config->get('out_of_terms'),
  );

  $form['content2']['show_nobody'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show message when a node has no acknowledgement'),
    '#default_value' => $config->get('show_nobody'),
  );
  $form['content3'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('User & Roles'),
  );
  $form['content3']['roles_fieldset'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('User roles to support by default'),
    '#description' => $this->t('These values will be used as default values while creating a new node...'),
  );
  $form['content3']['roles_fieldset']['roles'] = array(
    '#type' => 'checkboxes',
    '#default_value' => $config->get('roles'),
    '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE)),
    '#multiple' => TRUE,
  );
  $form['content3']['use_default_roles'] =  array(
    '#type' => 'checkbox',
    '#title' => $this->t('Use default roles only?'),
    '#default_value' => $config->get('use_default_roles'),
  );
  $form['content3']['use_single_user'] =  array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show single user insertion?'),
    '#default_value' => $config->get('use_single_user'),
  );
  $form['content4'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Layout'),
  );
  $form['content4']['show_roles'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show roles in the table?'),
    '#default_value' => $config->get('show_roles'),
  );
  $form['content4']['show_email'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show email in the table?'),
    '#default_value' => $config->get('show_email'),
  );
  $form['content4']['limit'] = array(
    '#type' => 'select',
    '#title' => $this->t('Number of rows in the table'),
    '#default_value' => $config->get('limit'),
    '#options' => array(
      5 => 5,
      10 => 10,
      50 => 50,
      100 => 100,
      500 => 500,
      1000 => 1000,
      -1 => $this->t('All'),
    ),
  );
  $form['content4']['embedded'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Fields to be shown in the table'),
  );
  $my_fields = array(); //'' => $this->t('No field'));
  $field_map = \Drupal::entityManager()->getFieldMap();
  $fields = $field_map['user'];
  foreach ($fields as $name => $field) {
    if (isset($field['bundles']['user']) && !empty($field['bundles']['user'])) {
      if (strncmp('field_', $name, 6) == 0)
        $my_fields[$name] = $name;
    }
  }
  $form['content4']['embedded']['fields'] = array(
    '#type' => 'checkboxes',
    '#default_value' => $config->get('fields'),
    '#options' => $my_fields,
    '#multiple' => TRUE,
  );
  $form['content4']['weight'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Checkbox and text weight'),
    '#size' => 4,
    //'#element_validate' => array('element_validate_number'),
    '#default_value' => $config->get('weight'),
  );
  $form['content4']['separator'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Separator for csv cells'),
    '#size' => 4,
    '#maxlength' => 1,
    '#default_value' => $config->get('separator'),
  );
  $form['content4']['show_submit'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show the submit button?'),
    '#default_value' =>  $config->get('show_submit'),
  );
  $form['content4']['block_expired'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Prevent the signature if terms have expired.'),
    '#default_value' =>  $config->get('block_expired'),
  );
/*
  $form['content4']['checkbox_in_views'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Show signature checkbox in views list of nodes.'),
    '#default_value' => $config->get('checkbox_in_views'),
  );
*/
  $form['content4']['beautify_node_edit'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Beautify node edit form using fieldsets.'),
    '#default_value' => $config->get('beautify_node_edit'),
  );
/*
  $form['content5'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('alternate form'),
  );
  $form['content5']['alternate_request'] = array(
    '#type' => 'textarea',
    '#title' => $this->t('Please insert your radio buttons labels, one per line, no more than 256 chars per line.'),
    '#default_value' => $config->get('alternate_request', "I don't agree\nI agree"),
  );
*/
  $form['content6'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('notification email'),
  );
  $form['content6']['email_to_roles'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Permit notification email to selected roles.'),
    '#default_value' => $config->get('email_to_roles'),
  );
  $form['content6']['email_to_users'] = array(
    '#type' => 'checkbox',
    '#title' => $this->t('Permit notification email to selected users.'),
    '#default_value' => $config->get('email_to_users'),
  );
  $form['content6']['email_to'] = array(
    '#type' => 'select',
    '#title' => $this->t('Visible email receiver'),
    '#default_value' => $config->get('email_to', 0),
    '#options' => array(
      0 => 'Undisclosed recipients',
      1 =>  \Drupal::config('system.site')->get('mail'),
    ),
  );
  $form['content6']['email_subject'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Please insert your email subject using tokens for node and site data.'),
    '#default_value' => $config->get('email_subject'),
  );
  $form['content6']['email_body'] = array(
    '#type' => 'textarea',
    '#title' => $this->t('Please insert your email body using tokens for node and site data.'),
    '#default_value' => $config->get('email_body'),
  );
  $form['content7'] = array(
    '#type' => 'fieldset',
    '#title' => $this->t('Notification email no signature'),
  );
  $form['content7']['email_subject_nosign'] = array(
    '#type' => 'textfield',
    '#title' => $this->t('Please insert your email subject using tokens for node and site data.'),
    '#default_value' => $config->get('email_subject_nosign'),
  );
  $form['content7']['email_body_nosign'] = array(
    '#type' => 'textarea',
    '#title' => $this->t('Please insert your email body using tokens for node and site data.'),
    '#default_value' => $config->get('email_body_nosign'),
  );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $fieldman = \Drupal::service('sign_for_acknowledgement.field_manager');
    $config = $this->config('sign_for_acknowledgement.settings');
    $config->set('node_types', $form_state->getValue('node_types'));
//    $config->set('warning', $form_state->getValue('warning'));
    $config->set('signed_ok', $form_state->getValue('signed_ok'));
    $config->set('to_be_signed', $form_state->getValue('to_be_signed'));
    $config->set('out_of_terms', $form_state->getValue('out_of_terms'));
    $config->set('terms_expired', $form_state->getValue('terms_expired'));
    $config->set('show_nobody', $form_state->getValue('show_nobody'));
    $config->set('roles', $form_state->getValue('roles'));
    $config->set('use_default_roles', $form_state->getValue('use_default_roles'));
    $config->set('use_single_user', $form_state->getValue('use_single_user'));
    $config->set('show_roles', $form_state->getValue('show_roles'));
    $config->set('show_email', $form_state->getValue('show_email'));
    $config->set('limit', $form_state->getValue('limit'));
    $config->set('fields', $form_state->getValue('fields'));
    $config->set('weight', $form_state->getValue('weight'));
    $config->set('separator', $form_state->getValue('separator'));
    $config->set('show_submit', $form_state->getValue('show_submit'));
    $config->set('block_expired', $form_state->getValue('block_expired'));
//    $config->set('checkbox_in_views', $form_state->getValue('checkbox_in_views'));
    $config->set('beautify_node_edit', $form_state->getValue('beautify_node_edit'));
//  $config->set('alternate_request', $form_state->getValue('alternate_request'));
    $config->set('email_to_roles', $form_state->getValue('email_to_roles'));
    $config->set('email_to_users', $form_state->getValue('email_to_users'));
    $config->set('email_to', $form_state->getValue('email_to'));
    $config->set('email_subject', $form_state->getValue('email_subject'));
    $config->set('email_body', $form_state->getValue('email_body'));
    $config->set('email_subject_nosign', $form_state->getValue('email_subject_nosign'));
    $config->set('email_body_nosign', $form_state->getValue('email_body_nosign'));
    $config->save();
	//$fieldman->resetExpirations();
    $fieldman->resetFields();
    $dbman = \Drupal::service('sign_for_acknowledgement.db_manager');
    $dbman->clearRenderCache();
  }

}
