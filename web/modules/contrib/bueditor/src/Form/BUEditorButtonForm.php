<?php

namespace Drupal\bueditor\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for BUEditor Button entities.
 */
class BUEditorButtonForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $bueditor_button = $this->getEntity();
    // Check duplication
    if ($this->getOperation() === 'duplicate') {
      $bueditor_button = $bueditor_button->createDuplicate();
      $bueditor_button->set('label', $this->t('Duplicate of @label', ['@label' => $bueditor_button->label()]));
      $this->setEntity($bueditor_button);
    }
    // Label
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $bueditor_button->label(),
      '#maxlength' => 64,
      '#required' => TRUE,
   ];
    // Id
    $id = $bueditor_button->id();
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [get_class($bueditor_button), 'load'],
        'source' => ['label'],
      ],
      '#default_value' => $id && strpos($id, 'custom_') === 0 ? substr($id, 7) : $id,
      '#maxlength' => 32,
      '#required' => TRUE,
      '#field_prefix' => 'custom_',
    ];
    // Template button
    $code = $bueditor_button->get('code');
    $template = $bueditor_button->get('template');
    $js_info = $this->t('If the code starts with <code>js:</code> it is executed as javascript inside <code>function(E, $){...}</code> where <code>E</code> is the editor instance, and <code>$</code> is JQuery. Ex: <code>js: console.log(this, E, $);</code>');
    $template_checked = [':input[name="is_template"]' => ['checked' => TRUE]];
    $template_unchecked = [':input[name="is_template"]' => ['checked' => FALSE]];
    $form['is_template'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a template button'),
      '#default_value' => $template && !$code,
    ];
    $form['template_button'] = [
      '#type' => 'details',
      '#title' => $this->t('Template button'),
      '#description' => $this->t('A template button is used for inserting a custom element into toolbar.'),
      '#open' => TRUE,
      '#states' => [
        'visible' => $template_checked,
      ],
    ];
    // Template
    $form['template_button']['template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template'),
      '#default_value' => $template,
      '#description' => $this->t('Html template that will be inserted into toolbar.') . '<br />' . $js_info,
      '#states' => [
        'required' => $template_checked,
      ],
    ];
    // Normal button
    $form['button'] = [
      '#type' => 'details',
      '#title' => $this->t('Button properties'),
      '#open' => TRUE,
      '#states' => [
        'visible' => $template_unchecked,
      ],
    ];
    // Code
    $form['button']['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code'),
      '#default_value' => $code,
      '#description' => $this->t('Text to insert into editor textarea. Ex: <code>&lt;strong&gt;|&lt;/strong&gt;</code>. The vertical bar <strong>|</strong> represents the cursor position or the selected text in the textarea.') . '<br />' . $js_info,
      '#states' => [
        'required' => $template_unchecked,
      ],
    ];
    // Button text
    $form['button']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $bueditor_button->get('text'),
      '#description' => $this->t('A text label or html icon for the button element.'),
    ];
    // Tooltip
    $form['button']['tooltip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tooltip'),
      '#default_value' => $bueditor_button->get('tooltip'),
      '#description' => $this->t('Descriptive text displayed on button hover.'),
    ];
    // Class name
    $form['button']['cname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class name'),
      '#default_value' => $bueditor_button->get('cname'),
      '#description' => $this->t('Additional class name for the button element.') . '<br />' . $this->t('Font icon class can be used as <code>ficon-NAME</code> where <code>NAME</code> is one of %names.', ['%names' => 'bold, italic, underline, strike, image, link, quote, code, ul, ol, table, template, undo, redo, preview, help']),
    ];
    // Class name
    $form['button']['shortcut'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shortcut'),
      '#default_value' => $bueditor_button->get('shortcut'),
      '#description' => $this->t('Button shortcut as a combination of Modifier keys (<kbd>Ctrl</kbd>, <kbd>Alt</kbd>, <kbd>Shift</kbd>) and Alphanumeric keys(<kbd>0-9</kbd>, <kbd>A-Z</kbd>) or special keys like Back space(<kbd>BACKSPACE</kbd>), Tabulator(<kbd>TAB</kbd>), Return(<kbd>ENTER</kbd>), Escape(<kbd>ESC</kbd>), Space(<kbd>SPACE</kbd>), Arrow keys(<kbd>LEFT|RIGHT|UP|DOWN</kbd>), Function keys(<kbd>F1-F12</kbd>).') . '<br />' . $this->t('Example shortcuts: <kbd>Ctrl+M</kbd>, <kbd>Alt+Shift+5</kbd>, <kbd>Ctrl+Shift+ENTER</kbd>.') . '<br />' . $this->t('Make sure not to override default shortcuts like <kbd>Ctrl+A|C|V|X</kbd> which are critical for text editing.'),
    ];
    // Libraries
    $form['libraries'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Required libraries'),
      '#default_value' => implode(', ', $bueditor_button->get('libraries')),
      '#description' => $this->t('Comma separated list of required drupal libraries for the button. Ex: core/drupal.progress, node/drupal.node.preview'),
    ];
    // Add demo
    if (!$bueditor_button->isNew()) {
      $bueditor_editor = \Drupal::entityTypeManager()->getStorage('bueditor_editor')->create(['id' => '_button_demo', 'settings' => ['toolbar' => [$bueditor_button->id()]]]);
      $attached['library'] = $bueditor_editor->getLibraries();
      $attached['drupalSettings']['bueditor']['demoSettings'] = $bueditor_editor->getJSSettings();
      $form['demo'] = [
        '#type' => 'text_format',
        '#base_type' => 'textarea',
        '#title' => $this->t('Demo'),
        '#weight' => 1000,
        '#attributes' => ['class' => ['bueditor-demo']],
        '#attached' => $attached,
        '#editor' => FALSE,
        '#input' => FALSE,
        '#value' => NULL,
      ];
    }
    // Add library
    $form['#attached']['library'][] = 'bueditor/drupal.bueditor.admin';
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $bueditor_button = $this->getEntity();
    $values = $form_state->getValues();
    // Add ID prefix
    if (!$form_state->getError($form['id'])) {
      $id = 'custom_' . $values['id'];
      $form_state->setValue('id', $id);
      // Check duplicate.  Entity contains the submitted values
      if ($id != $bueditor_button->getOriginalId()) {
        if ($bueditor_button->load($id)) {
          $form_state->setError($form['id'], $this->t('The machine-readable name is already in use. It must be unique.'));
        }
      }
    }
    // Template button
    if (!empty($values['is_template'])) {
      $form_state->setValue('code', '');
    }
    // Normal button
    else {
      $form_state->setValue('template', '');
      // Check class name
      if (!empty($values['cname']) && preg_match('/[^a-zA-Z0-9\-_ ]/', $values['cname'])) {
        $form_state->setErrorByName('cname', $this->t('@field contains invalid characters.', ['@field' => $this->t('Class name')]));
      }
      // Check shortcut
      if (!empty($values['shortcut']) && preg_match('/[^a-zA-Z0-9\+]/', $values['shortcut'])) {
        $form_state->setErrorByName('shortcut', $this->t('@field contains invalid characters.', ['@field' => $this->t('Shortcut')]));
      }
    }
    // Convert libraries to array.
    if (isset($values['libraries']) && is_string($values['libraries'])) {
      $form_state->setValue('libraries', array_values(array_filter(array_map('trim', explode(',', $values['libraries'])))));
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bueditor_button = $this->getEntity();
    $status = $bueditor_button->save();
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Button %name has been added.', ['%name' => $bueditor_button->label()]));
    }
    elseif ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The changes have been saved.'));
    }
    $form_state->setRedirect('entity.bueditor_button.edit_form', ['bueditor_button' => $bueditor_button->id()]);
  }

}
