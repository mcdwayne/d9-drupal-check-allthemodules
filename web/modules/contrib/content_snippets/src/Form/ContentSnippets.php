<?php

namespace Drupal\content_snippets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Custom Texts.
 */
class ContentSnippets extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'content_snippets';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['content_snippets.content_scraps.items'];
  }

  /**
   * Settings for "Custom Text" form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    // Add form header describing purpose and use of form.
    $form['header'] = [
      '#type' => 'markup',
      '#markup' => t('<h3>Create custom text snippets for use on the site.</h3><p>The contents of the snippets can be edited by Content Editors. The snippets can be used in code, either using <code>content_snippets_retrieve(snippetname)</code> or with tokens.</p>'),
    ];

    $typeoptions = [
      'textfield' => 'Line (plain text)',
      'textarea' => 'Paragraph (formatted)',
    ];

    $snippets = $this->config('content_snippets.items')->get();
    $form['snippets'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => 'Existing Snippets',
      '#description' => t('Configure existing snippets by changing their text style. Select "Delete" to remove the snippet.'),
      '#attributes' => ['id' => 'snippets'],
    ];
    foreach ($snippets as $key => $type) {
      $form['snippets'][$key] = [
        '#title' => $key,
        '#type' => 'select',
        '#options' => $typeoptions,
        '#default_value' => $type,
      ];
      $form['snippets'][$key]['#options']['delete'] = 'Delete';
    }

    $form['name'] = [
      '#title' => t('Add New Snippet'),
      '#type' => 'machine_name',
      '#machine_name' => ['exists' => 'Drupal\content_snippets\Form\ContentSnippets::checkExisting'],
      '#required' => FALSE,
    ];

    $form['type'] = [
      '#title' => t('New Snippet Type'),
      '#type' => 'select',
      '#options' => $typeoptions,
    ];

    $form['actions']['submit']['#value'] = t('Save');
    return $form;
  }

  /**
   * Validation callback for the "name" machinename field.
   */
  public static function checkExisting($value, array $element, FormStateInterface $form_state) {
    if (!empty('#value')) {
      if (isset($form_state->getValues()['snippets'][$value])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $items = $this->configFactory()->getEditable('content_snippets.items');
    $values = $form_state->cleanValues()->getValues();
    if (!empty($values['snippets'])) {
      foreach ($values['snippets'] as $field_key => $field_value) {
        if ($field_value == 'delete') {
          $items->clear($field_key);
        }
        else {
          $items->set($field_key, $field_value);
        }
      }
    }
    if (!empty($values['name'])) {
      $items->set($values['name'], $values['type']);
    }
    $items->save();
    parent::submitForm($form, $form_state);
  }

}
