<?php

namespace Drupal\mask\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Module settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mask_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['mask.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mask.settings');

    // Gets the rows from the form state.
    $this->updateFormState($form_state);
    $translation = $form_state->get('mask.translation');

    // Adds form elements.
    $form['use_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a CDN'),
      '#description' => $this->t('Serve the jQuery Mask Plugin from a CDN.'),
      '#default_value' => $config->get('use_cdn'),
    ];
    $form['plugin_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local file path'),
      '#description' => $this->t('Path to the minified jQuery Mask Plugin library in the public files folder.'),
      '#size' => 90,
      '#default_value' => $config->get('plugin_path'),
      '#states' => [
        'visible' => [
          ':input[name="use_cdn"]' => ['checked' => FALSE],
        ],
      ],
      '#prefix' => $config->get('plugin_path') ? '<div>' : '<div class="hidden">',
      '#suffix' => '</div>',
    ];
    $form['translation'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Patterns'),
      '#description' => $this->t('Define the substitution patterns that will be available when writting masks. Default patterns cannot be altered.'),
    ];
    $form['translation']['symbols'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#prefix' => '<div id="mask-translation-symbols">',
      '#suffix' => '</div>',
    ];
    $form['translation']['add_another'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#submit' => [[$this, 'addAnotherSubmit']],
      '#name' => 'add_another',
      '#ajax' => [
        'callback' => [$this, 'updateSymbolsAjaxCallback'],
        'wrapper' => 'mask-translation-symbols',
      ],
    ];

    // Adds current translation characters to the form.
    foreach ($translation as $delta => $values) {
      $form['translation']['symbols'][$delta] = $this->patternRowElements($delta, $values);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do not validate when removing or adding rows.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#name'])) {
      if (strpos($triggering_element['#name'], 'remove') === 0 ||
          strpos($triggering_element['#name'], 'add_another') === 0) {
        return;
      }
    }

    // Validates the symbols entries.
    $this->validateSymbols($form, $form_state);

    // Validates remaining settings.
    if (!$form_state->getValue('use_cdn')) {
      // Validates the file path.
      $plugin_path = trim($form_state->getValue('plugin_path'));
      if ($plugin_path) {
        // Checks if file exists in public files folder.
        if (strpos($plugin_path, 'public://') !== 0) {
          $form_state->setError($form['plugin_path'], $this->t('The jQuery Mask Plugin library must be placed in the public files directory.'));
        }
        elseif (!file_exists($plugin_path)) {
          $form_state->setError($form['plugin_path'], $this->t('File %path does not exist or is not accessible.', [
            '%path' => $plugin_path,
          ]));
        }
      }
      else {
        // Tries to download from the CDN defined in the mask.libraries.yml
        // file.
        $http_client = \Drupal::httpClient();
        $response = $http_client->get(MASK_PLUGIN_CDN_URL);
        if ($response->getStatusCode() == 200) {
          // Saves the downloaded file.
          $destination = 'public://jquery.mask.min.js';
          $plugin_path = file_unmanaged_save_data($response->getBody(), $destination);
          $form_state->setValue('plugin_path', $plugin_path);
        }
        if (!$plugin_path) {
          // Could not download the file.
          $form_state->setError($form['plugin_path'], $this->t('The library could not be downloaded to the public files folder. Please do it manually and provide its local path.'));

          // Shows the field to enter the path.
          $form['plugin_path']['#prefix'] = '<div>';
        }
      }
    }
  }

  /**
   * Validates the symbols entries.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateSymbols(array &$form, FormStateInterface $form_state) {
    $symbol_values = $form_state->getValue('symbols');

    // Checks if there are empty or duplicated symbols and missing patterns.
    $empty_symbols = [];
    $empty_pattern = [];
    $duplicates = [];
    foreach ($symbol_values as $delta => $value) {
      $symbol = isset($value['symbol']) ? $value['symbol'] : '';
      $pattern = isset($value['pattern']) ? $value['pattern'] : '';
      if ($symbol !== '') {
        // Count this delta for the symbol.
        $duplicates[$symbol][] = $delta;

        // Checks if pattern is missing.
        if (empty($value['pattern'])) {
          $empty_pattern[$symbol][] = $delta;
        }
      }
      elseif ($pattern !== '') {
        // Missing symbol.
        $empty_symbols[] = $delta;
      }
    }

    // Shows messages for encountered errors.
    foreach ($empty_symbols as $delta) {
      $element = &$form['translation']['symbols'][$delta]['symbol'];
      $form_state->setError($element, $this->t('Symbol is empty.'));
    }
    foreach ($empty_pattern as $symbol => $deltas) {
      foreach ($deltas as $delta) {
        $element = &$form['translation']['symbols'][$delta]['pattern'];
        $form_state->setError($element, $this->t('Pattern for symbol %symbol is empty.', [
          '%symbol' => $symbol,
        ]));
      }
    }
    foreach ($duplicates as $symbol => $deltas) {
      if (count($deltas) > 1) {
        // The first value is not considered a duplicate.
        array_shift($deltas);
        foreach ($deltas as $delta) {
          $element = &$form['translation']['symbols'][$delta]['symbol'];
          $form_state->setError($element, $this->t('There already is a pattern for the %symbol symbol.', [
            '%symbol' => $symbol,
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Saves settings.
    $this->config('mask.settings')
         ->set('use_cdn', $form_state->getValue('use_cdn'))
         ->set('plugin_path', $form_state->getValue('plugin_path'))
         ->set('translation', $this->getTranslationValue($form_state))
         ->save();

    // Clears library definition cache if necessary.
    $changed_use_cdn = $form['use_cdn']['#default_value'] != $form_state->getValue('use_cdn');
    $changed_plugin_path = $form['plugin_path']['#default_value'] != $form_state->getValue('plugin_path');
    if ($changed_use_cdn || $changed_plugin_path) {
      \Drupal::service('library.discovery')->clearCachedDefinitions();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit callback for the "Add another" button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addAnotherSubmit(array &$form, FormStateInterface $form_state) {
    // Adds another empty row.
    $value = $form_state->getValue('symbols');
    $value[] = [];
    $form_state->setValue('symbols', $value);

    // Updates the form state and rebuilds the form.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit callback for the "Remove" buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function removeSubmit(array &$form, FormStateInterface $form_state) {
    // Removes the corresponding delta from the value.
    $triggering_element = $form_state->getTriggeringElement();
    $delta = substr($triggering_element['#name'], 7);
    $form_state->unsetValue(['symbols', $delta]);

    // Updates the form state and rebuilds the form.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for the "Add another" and "Remove" buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateSymbolsAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['translation']['symbols'];
  }

  /**
   * Updates the form state storage values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function updateFormState(FormStateInterface $form_state) {
    $translation = [];

    if ($form_state->get('mask.translation') === NULL) {
      // Form state was not initialized yet.
      $config_value = $this->config('mask.settings')->get('translation');
      if ($config_value !== NULL) {
        // Assigns a delta to each value. Locked rows are placed above.
        $locked = [];
        $unlocked = [];
        foreach ($config_value as $symbol => $options) {
          if (empty($options['locked'])) {
            $unlocked[] = $options + ['symbol' => $symbol];
          }
          else {
            $locked[] = $options + ['symbol' => $symbol];
          }
        }
        $translation = array_merge($locked, $unlocked);
      }
    }
    else {
      // Updates with submitted values.
      $translation = $form_state->getValue('symbols');
    }

    // Ensures that there is at least one row.
    if (empty($translation)) {
      $translation[] = [];
    }

    // Sorts the array by delta.
    // @todo Remove after https://www.drupal.org/project/drupal/issues/2396923
    // has been solved.
    ksort($translation);

    // Stores updated value.
    $form_state->set('mask.translation', $translation);
  }

  /**
   * Returns the submitted translation value to be saved.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function getTranslationValue(FormStateInterface $form_state) {
    $translation = [];
    if ($submitted_value = $form_state->getValue('symbols')) {
      foreach ($submitted_value as $values) {
        $symbol = isset($values['symbol']) ? trim($values['symbol']) : '';
        if ($symbol !== '' && !empty($values['pattern'])) {
          $translation[$symbol] = [
            'pattern' => $values['pattern'],
            'fallback' => $values['fallback'] ?: '',
            'optional' => $values['optional'] ?: FALSE,
            'recursive' => $values['recursive'] ?: FALSE,
            'locked' => $values['locked'] ?: FALSE,
          ];
        }
      }
    }
    return $translation;
  }

  /**
   * Returns an element with fields to edit a pattern.
   *
   * @param int $delta
   *   The delta (row number) of the element.
   * @param array $values
   *   The options for this pattern. It may contain the following keys:
   *   - symbol: The character used as placeholder.
   *   - pattern: The regular expression for substitution.
   *   - optional: Whether the character is optional.
   *   - recursive: Whether the pattern is applied recursively.
   *   - fallback: Fallback character.
   */
  protected function patternRowElements($delta, array $values = []) {
    // Default settings.
    $values += [
      'symbol' => '',
      'pattern' => '',
      'fallback' => '',
      'optional' => FALSE,
      'recursive' => FALSE,
      'locked' => FALSE,
    ];

    // Defines form elements.
    $element = [
      '#type' => 'container',
      '#weight' => $delta,
    ];
    $element['symbol'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Symbol'),
      '#size' => 4,
      '#maxlength' => 1,
      '#default_value' => $values['symbol'],
      '#disabled' => $values['locked'],
    ];
    $element['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('A regular expression to match allowed characters.'),
      '#size' => 40,
      '#default_value' => $values['pattern'],
      '#disabled' => $values['locked'],
    ];
    $element['fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback'),
      '#description' => $this->t('This value is used when the user types an invalid character for the current position.'),
      '#size' => 10,
      '#default_value' => $values['fallback'],
      '#disabled' => $values['locked'],
    ];
    $element['optional'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Optional'),
      '#default_value' => $values['optional'],
      '#disabled' => $values['locked'],
    ];
    $element['recursive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recursive'),
      '#default_value' => $values['recursive'],
      '#disabled' => $values['locked'],
    ];
    $element['locked'] = [
      '#type' => 'value',
      '#value' => $values['locked'],
    ];
    if (empty($values['locked'])) {
      $element['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => "remove_$delta",
        '#submit' => [[$this, 'removeSubmit']],
        '#ajax' => [
          'callback' => [$this, 'updateSymbolsAjaxCallback'],
          'wrapper' => 'mask-translation-symbols',
        ],
      ];
    }
    else {
      // Puts an empty column to have all rows with the same size.
      $element['remove'] = [
        '#type' => 'container',
      ];
    }

    return $element;
  }

}
