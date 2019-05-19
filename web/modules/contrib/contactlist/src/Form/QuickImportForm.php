<?php

namespace Drupal\contactlist\Form;

use Alma\CsvTools\CsvDataListMapper;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * ContactListEntry import form.
 */
class QuickImportForm extends ImportFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contactlist_quick_import_form';
  }

  /**
   * Quick contact import form from pasted contact phone numbers.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contactlist.settings');
    if (empty($config->get('default_field'))) {
      if ($this->currentUser()->hasPermission('administer contact lists')) {
        $message = t('!link have not been configured.', ['!link' => $this->l('ContactListEntry import settings', Url::fromRoute('contactlist.admin_form'))]);
      }
      else {
        $message = t('ContactListEntry import settings have not been configured. Contact the site administrator.');
      }
      drupal_set_message($message);
      $form['fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Import settings not configured'),
        '#description' => $message,
      );
    }
    else {
      $form['import'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Contact list entry import'),
        '#collapsible' => TRUE,
        '#prefix' => 'Use this utility to bulk import contacts from a list of numbers or other formats.',
      );

      $form['import']['free_text'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Free text'),
        '#columns' => 38,
        '#rows' => 5,
        '#required' => TRUE,
        '#description' => $this->t('Paste a list of contact information you wish to import here (e.g. a list of numbers), then select which field to import to.'),
      );

      if ($config->get('expose_default_field')) {
        $form['import']['default_field'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the contact list field you are importing.'),
          '#options' => $this->getDisplayableContactFieldLabels('form'),
          '#required' => TRUE,
          '#default_value' => $config->get('default_field'),
          '#description' => $this->t('The list you paste above will be imported into your contact list with the selected field as values'),
        );
      }
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Import'),
        '#weight' => 6,
      );
    }
    $this->buildGroupFormWidget($form['import'], $form_state);
    $form['#attributes']['enctype'] = 'multipart/form-data';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($csv_text = $this->cleanCsvFreeText($form_state->getValue('free_text'))) {
      $default_field = $form_state->getValue('default_field') ?: $this->config('contactlist.settings')->get('default_field');
      // Data mapping. Use the default_field for column 0, and any other required
      // fields.
      $mapping = [
        $default_field => 0,
      ];

      // Create a dummy contact list entry to get field meta-info.
      $dummy = ContactListEntry::create([]);
      foreach ($this->getContactEntryFields() as $field_name => $field) {
        // Ensure all required fields are mapped to something in the imported CSV.
        if ($field->isRequired() && !$field->getDefaultValue($dummy)) {
          $mapping[$field_name] = 0;
        }
      }
      $import = (new CsvDataListMapper())
        ->setSourceText($csv_text)
        ->setHasHeader(FALSE)
        ->setDataMap($mapping)
        ->setSkipEmptyRows(TRUE);

      $form_state->setValue('import', $import);
    }
  }

}
