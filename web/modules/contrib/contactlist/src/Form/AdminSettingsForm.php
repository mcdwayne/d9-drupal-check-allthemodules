<?php

namespace Drupal\contactlist\Form;

use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contactlist_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['contactlist_config'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration Options'),
      '#description' => $this->t('General configuration options for Contact List.'),
      '#tree' => FALSE,
    );

    // Settings for default fields.
    $field_list = $this->getDisplayableContactFieldLabels('form');

    // Use $field_list for contactlist_entry bundle as options.
    $options = ['' => '-- Select --'] + $field_list;

    $config = $this->config('contactlist.settings');
    if (!empty($field_list)) {
      $form['contactlist_config']['name_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Name field'),
        '#description' => $this->t('Choose the name field for contact list entries. This field will be used for identifying contact list entries.'),
        '#options' => $options,
        '#default_value' => $config->get('name_field'),
      ];

      $form['contactlist_config']['label_format'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Display format for the contact entry label'),
        '#description' => $this->t('How do you want your contact labels to be displayed. Combine field names from the tokens below. E.g. [name] <[email]> to display a name as "John Doe <john@example.com>"'),
        '#maxsize' => 255,
        '#size' => 50,
        '#default_value' => $config->get('label_format'),
      );

      $form['contactlist_config']['field_tokens'] = array(
        '#type' => 'details',
        '#title' => $this->t('Available field tokens'),
        '#description' => $this->t('Use the following tokens to construct the display label or parsing rules: ')
            . '[' . implode('], [', array_keys($field_list)) . ']',
      );

      $form['contactlist_config']['default_field'] = array(
        '#type' => 'select',
        '#title' => $this->t('Default field in basic contact list importation form'),
        '#description' => $this->t('The default field when importing in free text mode, e.g. telephone, email, name, etc.'),
        '#options' => $options,
        '#default_value' => $config->get('default_field'),
      );

      $form['contactlist_config']['expose_default_field'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Allow users to change default field during import'),
        '#default_value' => !empty($config->get('expose_default_field')),
      );

      $form['contactlist_config']['quick_import_parse_rule'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Parse rule for non-default fields'),
        '#description' => $this->t('Use this to populate non-default fields in quick import. Use above tokens'),
        '#default_value' => $config->get('quick_import_parse_rule'),
      );

      $form['contactlist_config']['group_field'] = array(
        '#type' => 'select',
        '#title' => $this->t('Field to use for contact groups'),
        '#description' => $this->t('The field to use as contact groups field.'),
        '#options' => $options,
        '#default_value' => $config->get('group_field'),
      );

      $form['contactlist_config']['unique_fields'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Unique fields'),
        '#description' => $this->t('Unique fields that are used to distinguish contact list entries when adding or importing.'),
        '#options' => $field_list,
        '#multiple' => TRUE,
        '#default_value' => $config->get('unique_fields'),
      );

//      $form['contactlist_config']['advanced'] = array(
//        '#type' => 'details',
//        '#title' => $this->t('Advanced Options'),
//        '#description' => $this->t('Advanced configuration options for contactlist import.'),
//        '#tree' => TRUE,
//      );

      $form['contactlist_config']['field_mapping'] = array(
        '#type' => 'details',
        '#title' => $this->t('Field mapping for imports'),
        '#description' => $this->t('Specify how imported list columns should be mapped to internal contact list entry fields.'
          . ' Use comma separated lists to specify multiple mappings. E.g. name, full name, names'),
        '#tree' => TRUE,
      );

      foreach ($field_list as $field_name => $field_label) {
        $form['contactlist_config']['field_mapping'][$field_name] = array(
          '#type' => 'textfield',
          '#title' => $field_label,
          '#description' => $this->t(''),
          '#default_value' => $config->get('field_mapping.' . $field_name),
        );
      }

//      $form['contactlist_config']['advanced']['field_parse_rule'] = array(
//        '#type' => 'fieldset',
//        '#title' => $this->t('Field parsing rules during imports'),
//        '#description' => $this->t('Specify how imported contact list fields should be parsed to the various columns for the fields.'
//          . ' Use specified list of field column names in tokenized format. E.g. [value], [name]'),
//        '#collapsible' => TRUE,
//        '#collapsed' => TRUE,
//        '#tree' => TRUE,
//      );

      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
      );
    }
    else {
      drupal_set_message($this->t('You must !add_field_link to the ContactListEntry entity type to use the contact lists.',
            array('!add_field_link' => $this->l('add fields', Url::fromRoute('admin/structure/contactlist_entry/manage/fields')))), 'error');

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('contactlist.settings')
      ->setData($form_state->cleanValues()->getValues())
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contactlist.settings'];
  }

  /**
   * Determines which configured contactlist entry fields are displayable on UI.
   */
  protected function getDisplayableContactFieldLabels($display_context = 'view') {
    $field_labels = [];
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_info */
    $field_info = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('contactlist_entry', 'contactlist_entry');
    foreach ($field_info as $field_name => $field) {
      if ($field->isDisplayConfigurable($display_context)) {
        $field_labels[$field_name] = $field->getLabel();
      }
    }
    return $field_labels;
  }

}
