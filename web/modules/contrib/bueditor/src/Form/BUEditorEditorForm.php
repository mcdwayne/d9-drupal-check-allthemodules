<?php

namespace Drupal\bueditor\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for BUEditor Editor entities.
 */
class BUEditorEditorForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $bueditor_editor = $this->getEntity();
    // Check duplication
    if ($this->getOperation() === 'duplicate') {
      $bueditor_editor = $bueditor_editor->createDuplicate();
      $bueditor_editor->set('label', $this->t('Duplicate of @label', ['@label' => $bueditor_editor->label()]));
      $this->setEntity($bueditor_editor);
    }
    // Label
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $bueditor_editor->label(),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#weight' => -20,
    ];
    // Id
    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [get_class($bueditor_editor), 'load'],
        'source' => ['label'],
      ],
      '#default_value' => $bueditor_editor->id(),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#weight' => -20,
    ];
    // Description
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $bueditor_editor->get('description'),
      '#weight' => -10,
    ];
    // Toolbar
    $widget = $this->getToolbarWidget();
    $widget_libraries = $widget['libraries'];
    unset($widget['libraries']);
    $form['toolbar_config'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Toolbar configuration'),
      '#attached' => [
        'library' => $widget_libraries,
        'drupalSettings' => ['bueditor' => ['twSettings' => $widget]],
      ],
      '#weight' => -6,
    ];
    $form['toolbar_config']['toolbar'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Active toolbar'),
      '#default_value' => implode(', ', $bueditor_editor->getToolbar()),
      '#attributes' => [
        'class' => ['bueditor-toolbar-input'],
      ],
      '#maxlength' => NULL,
      '#parents' => ['settings', 'toolbar'],
    ];
    // Settings
    $form['settings'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#weight' => -5,
    ];
    // Class name
    $form['settings']['cname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class name'),
      '#default_value' => $bueditor_editor->getSettings('cname'),
      '#description' => $this->t('Additional class name for the editor element.'),
      '#weight' => -8,
    ];
    // Indentation
    $form['settings']['indent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable indentation'),
      '#default_value' => $bueditor_editor->getSettings('indent'),
      '#description' => $this->t('Enable 2 spaces indent by <kbd>TAB</kbd>, unindent by <kbd>Shift+TAB</kbd>, and auto-indent by <kbd>ENTER</kbd>. Once enabled it can be turned on/off dynamically by <kbd>Ctrl+Alt+TAB</kbd>.'),
      '#weight' => -7,
    ];
    // Autocomplete HTML tags
    $form['settings']['acTags'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocomplete HTML tags'),
      '#default_value' => $bueditor_editor->getSettings('acTags'),
      '#description' => $this->t('Automatically insert html closing tags.'),
      '#weight' => -6,
    ];
    // File Browser
    $form['settings']['fileBrowser'] = [
      '#type' => 'select',
      '#title' => $this->t('File browser'),
      '#options' => [],
      '#empty_value' => '',
      '#default_value' => $bueditor_editor->getSettings('fileBrowser'),
      '#description' => $this->t('File browser to use in default image/link dialogs.'),
      '#weight' => -5,
    ];
    // Add demo
    if (!$bueditor_editor->isNew()) {
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
    // Add admin library
    $form['#attached']['library'][] = 'bueditor/drupal.bueditor.admin';
    // Allow plugins to add their elements
    \Drupal::service('plugin.manager.bueditor.plugin')->alterEditorForm($form, $form_state, $bueditor_editor);
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $bueditor_editor = $this->getEntity();
    $toolbar = &$form_state->getValue(['settings', 'toolbar']);
    // Convert toolbar to array.
    if (is_string($toolbar)) {
      $toolbar = array_values(array_filter(array_map('trim', explode(',', $toolbar))));
    }
    // Entity has the raw form values.
    if (is_string($bueditor_editor->getToolbar())) {
      $bueditor_editor->setToolbar($toolbar);
    }
    // Check class name
    $cname = $form_state->getValue(['settings', 'cname']);
    if (!empty($cname) && preg_match('/[^a-zA-Z0-9\-_ ]/', $cname)) {
      $form_state->setError($form['settings']['cname'], $this->t('Class name is invalid.'));
    }
    \Drupal::service('plugin.manager.bueditor.plugin')->validateEditorForm($form, $form_state, $bueditor_editor);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bueditor_editor = $this->getEntity();
    $status = $bueditor_editor->save();
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Editor %name has been added.', ['%name' => $bueditor_editor->label()]));
    }
    elseif ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The changes have been saved.'));
    }
    $form_state->setRedirect('entity.bueditor_editor.edit_form', ['bueditor_editor' => $bueditor_editor->id()]);
  }

  /**
   * Returns toolbar widget data.
   *
   * @return array
   */
  public static function getToolbarWidget() {
    $pm = \Drupal::service('plugin.manager.bueditor.plugin');
    $widget = ['items' => $pm->getButtons(), 'libraries' => ['bueditor/drupal.bueditor.admin']];
    $pm->alterToolbarWidget($widget);
    return $widget;
  }

}
