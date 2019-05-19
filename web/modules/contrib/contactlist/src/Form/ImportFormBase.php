<?php

namespace Drupal\contactlist\Form;

use Alma\CsvTools\CsvDataListMapper;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\contactlist\Entity\ContactGroup;
use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class ImportFormBase extends FormBase {

  /**
   * Log of the contact list entries that were skipped or failed to import for some reason.
   *
   * @var array
   */
  protected $skipLog = [];


  protected $headerMap;

  /**
   * IDs of the contact list entries that were successfully imported.
   *
   * @var array
   */
  protected $imported = [];

  protected $importData;

  /**
   * Builds an array for mapping CSV data headers to contact entity field names.
   *
   * @param array $header
   *   Array containing the header from the imported CSV file.
   *
   * @return array
   *   An array containing the mapping between CSV header titles as the
   *   key and the machine name of each contact field as the values.
   *   e.g.
   *   @code ['Full name' => 'name', 'Phone number' => 'telephone'] @endcode
   */
  public function getHeaderMapping(array $header) {
    if (!isset($this->headerMap['config'])) {
      // Initialize the array of pre-configured mappings to contact fields.
      $this->headerMap['config'] = [];
      $field_mapping = $this->config('contactlist.settings')->get('field_mapping');
      foreach ($field_mapping as $field_name => $alternates) {
        $this->headerMap['config'][$field_name] = Tags::explode(strtolower($alternates));
      }
    }
    $hash = Crypt::hashBase64(serialize($header));
    if (!isset($this->headerMap[$hash])) {
      // Search for a match with pre-configured fields.
      $mapping = [];
      foreach ($header as $column_name) {
        foreach ($this->headerMap['config'] as $field_name => $alternates) {
          if (strtolower($column_name) == strtolower($field_name) || in_array(strtolower($column_name), $alternates)) {
            $mapping[$field_name] = $column_name;
            break;
          }
        }
      }
      $this->headerMap[$hash] = $mapping;
    }
    return $this->headerMap[$hash];
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

  /**
   * Gets the field definitions for the contact list entry bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions for the bundle, keyed by field name.
   */
  protected function getContactEntryFields() {
    return \Drupal::entityManager()
      ->getFieldDefinitions('contactlist_entry', 'contactlist_entry');
  }

  /**
   * Provides the form for advanced or bulk contact list imports.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import']['has_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('My CSV list has a header (normally the first row in the csv list or file).'),
      '#default_value' => TRUE,
    );

    /* This has not been tested.
    $form['import']['duplicate_behavior'] = array(
      '#type' => 'radios',
      '#title' => $this->t('What to do with duplicated entries.'),
      '#default_value' => 'update',
      '#options' => [
        'skip' => $this->t('Skip duplicated entries'),
        'update' => $this->t('Update with new values'),
      ],
    );*/

    // Pane for preview of items after upload using ajax.
    $form['preview_pane'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
      '#description' => $this->t('Preview of the uploaded contact lists.'),
      '#collapsible' => TRUE,
      '#weight' => 10,
    );

    $form['preview_pane']['preview'] = array(
      '#theme' => 'table',
      '#prefix' => '<div id="preview_container" style="clear:both; max-width: 100%">',
      '#suffix' => '</div>',
      '#header' => array(),
      '#rows' => array(),
      '#empty' => $this->t('No data uploaded.')
    );

    $form['actions']['preview_button'] = array(
      '#type' => 'button',
      '#value' => $this->t('Preview'),
      '#ajax' => array(
        'callback' => [$this, 'previewPane'],
        'wrapper' => 'preview_container',
      ),
      '#weight' => 0,
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#weight' => 6,
    );
    $this->buildGroupFormWidget($form['import'], $form_state);
    $form['#attributes']['enctype'] = 'multipart/form-data';
    return $form;
  }

  /**
   * Ajax callback that returns the updated preview pane.
   */
  public function previewPane(array &$form, FormStateInterface $form_state) {
    /** @var \Alma\CsvTools\CsvDataListMapper $import */
    if ($import = $form_state->getValue('import')) {
      $slice = array_slice($import->getCsvData(), 0, min(10, count($import)));
      $dot_dot = [
        'z' => [
          [
            'data' => '.....',
            'colspan' => count($import->getHeader()),
          ],
        ],
      ];
      $form['preview_pane']['preview']['#rows'] = $slice + $dot_dot;
      $form['preview_pane']['preview']['#header'] = $import->getHeader();
      // @todo the count code needs work.
//      $form['preview_pane']['preview']['#caption'] = $this->t('@num contact(s) imported', array('@num' => count($data)));
    }
    else {
      drupal_set_message($this->t('No data uploaded'), 'warning', FALSE);
    }
    return $form['preview_pane']['preview'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** \CsvTools\CsvDataListMapper $import */
    if ($form_state->getTriggeringElement()['#value']->getUntranslatedString() === 'Import' && ($import = $form_state->getValue('import'))) {
      // @todo Consider batching or queuing in case the CSV data is very huge.
      $group_field = $this->config('contactlist.settings')->get('group_field');
      $default_groups = [];
      if ($group_field && $items = $form_state->getValue([$group_field, 'target_id'])) {
        foreach ($items as $item) {
          if (isset($item['entity'])) {
            $default_groups[] = $item['entity'];
          }
          else if (is_numeric($item['target_id'])) {
            $default_groups[] = ContactGroup::load($item['target_id']);
          };
        }
      }
      $this->saveImportedData($import, $group_field, $default_groups, $form_state->getValue('duplicate_behavior'));
      $this->displaySkipErrorMessages();
      $form_state->setRedirect('entity.contactlist_entry.collection');
    }
  }

  /**
   * Saves the imported data as contact list entries.
   */
  protected function saveImportedData(CsvDataListMapper $import, $group_field, array $default_groups, $duplicate_behavior) {
    // Create a contact list entry for each row of values imported.
    foreach ($import as $row) {
      try {
        /** @var \Drupal\contactlist\Entity\ContactListEntryInterface $contact */
        $contact = ContactListEntry::create($row);
        // Add the default group and set it manually since it is an ER field.
        $groups = (isset($row[$group_field]) ? Tags::explode($row[$group_field]) : []) + $default_groups;
        $contact->setGroups($groups);
        $violations = $contact->validate();
        // @todo Duplicate entries need to be handled.
        $this->checkDuplicates($violations, $duplicate_behavior);
        if (count($violations)) {
          $this->saveSkipLogInfo($row, $violations, NULL);
        }
        else {
          $contact->save();
          $this->imported[] = $contact->id();
        }
      }
      catch (\Exception $e) {
        // Catch exceptions and log messages for failed or skipped rows.
        $this->saveSkipLogInfo($row, NULL, $e->getMessage());
      }
    }
  }

  /**
   * Saves a failed import row to the skip log for later display.
   */
  protected function saveSkipLogInfo(array $csv_row, ConstraintViolationListInterface $violations = NULL, $messages = NULL) {
    $this->skipLog[] = [
      'row' => $csv_row,
      'violations' => $violations,
      'messages' => $messages,
    ];
  }

  /**
   * Displays error messages based on failed imports that were skipped.
   */
  protected function displaySkipErrorMessages() {
    $number_skipped = count($this->skipLog);
    $number_imported = count($this->imported);
    if ($number_skipped + $number_imported == 0) {
      drupal_set_message($this->t('No contact list entry found for import.'));
    }
    else {
      // @todo Need to create the log of error and constraint violation messages.
      $params = array(
        '@user' => $this->currentUser()->getDisplayName(),
        '@count' => $number_imported,
        '@fail' => $number_skipped,
      );
      if ($number_skipped == 0) {
        drupal_set_message($this->t('@count contact list entries successfully imported.', $params));
        $this->logger('contactlist')->info('@user successfully imported @count contactlist_entry.', $params);
      }
      else {
        if ($number_imported == 0) {
          drupal_set_message($this->t('@fail contact list entries failed to import.', $params), 'warning');
          $this->logger('contactlist')->warning('@user\'s attempt to import @fail contact list entries failed.', $params);
        }
        else {
          drupal_set_message($this->t('@count contact list entries successfully imported. @fail contact list entries failed.', $params), 'warning');
          $this->logger('contactlist')->notice('@user successfully imported @count contact list entries while @fail contact list entries failed to import.', $params);
        }
        $hash = Crypt::hashBase64(serialize($this->skipLog));
        drupal_set_message($this->t('Check the <a href=":link">log</a> for a list of failed imports.',
          [':link' => Url::fromRoute('contactlist.failed_imports', ['hash' => $hash, 'user' => $this->currentUser()->id()])->toString()]), 'warning');

        \Drupal::state()->set('contactlist.skiplog.' . $this->currentUser()->id() . '.' . $hash, $this->skipLog);
      }
    }
  }

  /**
   * @param string $form_mode
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected function getContactListFormDisplay($form_mode = 'default') {
    $display = EntityFormDisplay::load('contactlist_entry.contactlist_entry.' . $form_mode);
    if (!$display) {
      $display = EntityFormDisplay::create([
        'targetEntityType' => 'contactlist_entry',
        'bundle' => 'contactlist_entry',
        'mode' => $form_mode,
        'status' => TRUE,
      ]);
    }
    return $display;
  }

  protected function buildGroupFormWidget(array &$form, FormStateInterface $form_state) {
    if ($group_field = $this->config('contactlist.settings')->get('group_field')) {
      $contact = ContactListEntry::create(['name' => 'fake']);
      $form += ['#parents' => []];
      $entity_form_display = $this->getContactListFormDisplay();
      $options = $entity_form_display->getComponent($group_field);
      if ($widget = $entity_form_display->getRenderer($group_field)) {
        $items = $contact->get($group_field);
        $items->filterEmptyItems();
        $form[$group_field] = $widget->form($items, $form, $form_state);
        $form[$group_field]['#access'] = $items->access('edit');
        $form[$group_field]['#description'] = $this->t('The contact groups to add these contact list entries to, if not specified in the import data.');

        // Assign the correct weight. This duplicates the reordering done in
        // processForm(), but is needed for other forms calling this method
        // directly.
        $form[$group_field]['#weight'] = $options['weight'];

        // Associate the cache tags for the field definition & field storage
        // definition.
//      $field_definition = $entity_form_display->getFieldDefinition($group_field);
//      $this->renderer->addCacheableDependency($form[$group_field], $field_definition);
//      $this->renderer->addCacheableDependency($form[$group_field], $field_definition->getFieldStorageDefinition());
      }
    }
  }

  /**
   * Checks whether a contact has duplicates.
   *
   * @param $violations
   * @param $duplicate_behavior
   *
   * @todo Duplicate entries need to be handled.
   */
  protected function checkDuplicates($violations, $duplicate_behavior) { }

  /**
   * Cleans up free text CSV posted in the form.
   *
   * This standardizes the line separators to LF, converts TABS to COMMAS, and
   * removes extra newlines
   *
   * @param string $free_text
   *   The CSV free text to be cleaned.
   *
   * @return string
   *   Cleaned up CSV.
   */
  protected function cleanCsvFreeText($free_text) {
    // Normalize the submitted CSV text into an importable data structure.

    // Replace tabs with commas to facilitate direct copy-pasting from
    // excel-style spreadsheets.
    $csv_text = str_replace("\t", ',', $free_text);

    // Remove extra / unnecessary newlines in the data.
    $csv_text = str_replace("\r\n", "\n", $csv_text);
    $csv_text = preg_replace("/[\n]+/", "\n", $csv_text);
    $csv_text = preg_replace("/(^\n+)|(\n+$)/", '', $csv_text);

    return $csv_text;
  }
}
