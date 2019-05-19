<?php

namespace Drupal\translation_extractor\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Trait MultivalueRowTrait.
 *
 * @package Drupal\translation_extractor\Form
 */
trait MultivalueRowTrait {

  /**
   * Make sure the string translation trait is present.
   */
  abstract protected function t($string, array $args = [], array $options = []);

  /**
   * Generates a portion of the form that contains multiple values.
   *
   * @param array $container
   *   The form container to work in.
   * @param string $property
   *   The name to assign to the property.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   * @param array $default_values
   *   Array containing the default values for *all* rows.
   */
  final protected function createMultivalueFormPortion(array &$container, $property, FormStateInterface $form_state, array $default_values = [], $empty_text = FALSE) {

    // We don't want this form to be cached in order to use AJAX.
    $form_state->setCached(FALSE);

    // Determine the current configuration.
    if (empty($rows = $form_state->get($property)) && $form_state->get($property) === NULL) {
      if (empty($rows = $default_values)) {
        $rows = [];
      }
      $form_state->set($property, $rows);
    }

    // Initialize the container.
    $container['multivaluePart'] = [
      '#type' => 'container',
      '#prefix' => sprintf('<div id="%s-fieldset-wrapper">', $property),
      '#suffix' => '</div>',
      '#tree' => TRUE,
      'rows' => [
        '#type' => 'container',

      ],
      '#attached' => ['library' => ['translation_extractor/configform']],
    ];

    // Should the input rows be sortable? If so, add sorting
    // library and class names.
    if ($this->isSortable($property)) {
      $container['multivaluePart']['rows']['#attributes'] = ['class' => ['rowsort']];
    }

    // Generate the input rows.
    if (!empty($rows)) {
      foreach ($rows as $delta => $row_values) {

        // Prepare the row container.
        $row = [
          'row' => [
            '#type' => $this->getRowType($property),
            '#attributes' => ['class' => ['multiValueRow']],
          ],
        ];

        // Should a row title be added?
        if (!empty($title = $this->getRowTitle($property))) {
          $row['row']['#title'] = $this->t($title);
        }

        // Should any properties get mixed in the container definition?
        if (!empty($rowproperties = $this->getRowProperties($property))) {
          $row['row'] = array_merge($row['row'], $rowproperties);
        }

        // Add a class name to help for dynamic sorting if desired.
        if ($this->isSortable($property)) {
          $row['row']['#attributes']['class'][] = 'sortableRow';
        }

        // Add the row remove button.
        $row['row']['remove'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['multivalueRowRemoveButton']],
          '#weight' => -99999,
          'value' => [
            '#title' => $this->t('Remove row'),
            '#name' => sprintf('remove:%s:%d', $property, $delta),
            '#type' => 'submit',
            '#value' => 'X',
            '#submit' => ['::removeRow'],
            '#ajax' => [
              'callback' => '::multivalueAjaxCallback',
              'wrapper' => sprintf('%s-fieldset-wrapper', $property),
            ],
            '#limit_validation_errors' => [],
          ],
        ];

        // Get a row of inputs for the current row from the
        // actual implementing class.
        $row_inputs = $this->getInputRow($delta, $row_values, $form_state);

        // Inject each input into the current row.
        foreach ($row_inputs as $key => $input) {
          $row['row'][$key] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['multivalueRowInput']],
            'value' => $input,
          ];
          if (isset($input['#inline']) && $input['#inline']) {
            $row['row'][$key]['#attributes']['class'][] = 'multivalueRowInlineInput';
          }
        }

        // Add a "delta" select if needed.
        if ($this->isSortable($property)) {
          $row['row']['weight'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['multivalueRowWeight']],
            'value' => [
              '#type' => 'select',
              '#title' => $this->t('Weight'),
              '#options' => array_combine(range(-50, 50), range(-50, 50)),
              '#default_value' => $delta,
            ],
          ];
        }

        // Inject the readily built row into the container.
        $container['multivaluePart']['rows'][$delta] = $row;

      }
    }
    elseif ($empty_text !== FALSE) {

      $container['multivaluePart']['empty_text'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t($empty_text),
        '#attributes' => ['class' => ['multivalueEmptyText']],
      ];

    }

    // Add the "action" section.
    $container['multivaluePart']['actions'] = [
      'addRow' => [
        '#type' => 'submit',
        '#name' => sprintf('add:%s:new', $property),
        '#value' => $this->t($this->getAddRowButtonTitle($property)),
        '#submit' => ['::addRow'],
        '#ajax' => [
          'callback' => '::multivalueAjaxCallback',
          'wrapper' => sprintf('%s-fieldset-wrapper', $property),
        ],
        '#weight' => 99999,
      ],
    ];

    // Should empty mandatory fields throw an error if "add row"
    // is clicked? If not...
    if ($this->allowMultipleEmptyAdds($property)) {
      $container['multivaluePart']['actions']['addRow']['#limit_validation_errors'] = [];
    }

    // Add a potential additional selector to the "actions" section.
    if (!empty($element = $this->getAddSelector($property))) {
      $container['multivaluePart']['actions']['addSelector'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['multivalueAddSelector']],
        'value' => $element,
      ];
    }

  }

  /**
   * Returns the form portion that's impacted by ajax changes.
   *
   * @param array $form
   *   The complete form definition.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   *
   * @return array
   *   The AJAXified portion of the form.
   */
  final public function multivalueAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Determine the trigger that led to the AJAX action.
    $trigger = $form_state->getTriggeringElement();

    // Determine the form section name that's affected.
    preg_match('#^(add|remove):(.*?):#', $trigger['#name'], $matches);

    // Find the affected section.
    $section = $this->findSection($matches[2], $form);

    // Return the affected form portion.
    return $section['multivaluePart'];
  }

  /**
   * Helper function: extracts the multivalue part out of the user input.
   *
   * @param string $property
   *   The name of the property to extract.
   * @param array $userInput
   *   The raw user input data.
   *
   * @return mixed
   *   The user data of the multivalue part.
   */
  final protected function findData($property, array $userInput) {
    foreach ($userInput as $key => $value) {
      if ($key === $property && isset($value['multivaluePart'])) {
        return $value['multivaluePart'];
      }
      elseif (is_array($value) && ($subvalue = $this->findData($property, $value)) !== FALSE) {
        return $subvalue;
      }
    }
    return FALSE;
  }

  /**
   * Helper function: transforms (flattens) the multivalue data.
   *
   * @param string $property
   *   The name of the property to extract.
   * @param array $userInput
   *   The raw user input data.
   *
   * @return array
   *   The transformed data.
   */
  final protected function getData($property, array $userInput) {
    $raw = $this->findData($property, $userInput);
    $transformed = [];
    if (isset($raw['rows']) && is_array($raw['rows'])) {
      foreach ($raw['rows'] as $current) {
        $rowdata = [];
        foreach ($current['row'] as $key => $data) {
          if ($key === 'delta') {
            continue;
          }
          $rowdata[$key] = $data['value'];
        }
        $transformed[] = $rowdata;
      }

      if ($this->isSortable($property)) {
        // Sort the collected layers by their respective weight (and delete
        // the now useless key to prevent it from cluttering the
        // configuration data).
        $weights = [];
        foreach ($transformed as $key => &$row) {
          $weights[$key] = $row['weight'];
          unset($row['weight']);
        }
        array_multisort($weights, SORT_NUMERIC, $transformed);
      }
    }

    return $transformed;
  }

  /**
   * If there is an addSelector element, return it's value on an add action.
   *
   * @param string $property
   *   The name of the property to extract.
   * @param array $userInput
   *   The raw user input data.
   *
   * @return mixed|false
   *   The value or FALSE if no selector exists.
   */
  final protected function getAddSelection($property, array $userInput) {
    $rawData = $this->findData($property, $userInput);
    if (!empty($rawData['actions']['addSelector'])) {
      return $rawData['actions']['addSelector']['value'];
    }
    return FALSE;
  }

  /**
   * AJAX callback function.
   *
   * Gets called when the "Add more" button is clicked.
   *
   * @param array $form
   *   The complete form definition.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   */
  final public function addRow(array &$form, FormStateInterface $form_state) {
    // Determine the trigger button.
    $trigger = $form_state->getTriggeringElement();

    // The trigger carries the name of his section in it's name (the only way to
    // securely transport this piece of information).
    list(, $property,) = explode(':', $trigger['#name']);

    // Get the raw user input.
    $user_input = $form_state->getUserInput();

    // Extract the values of the current multivalue part.
    $values = $this->getData($property, $user_input);

    // Determine the current row configuration.
    $rows = $form_state->get($property);

    // Determine the value of a potential addSelector.
    $addSelectorValue = $this->getAddSelection($property, $user_input);

    // Retrieve the data that should get added to the storage object.
    if (($dataToAdd = $this->getDataToAdd($property, $rows, $values, $addSelectorValue, $form_state)) !== FALSE) {
      // Insert the data returned to the form state variable.
      $rows[] = $dataToAdd;
      $form_state->set($property, $rows);
      $form_state->setRebuild();
    }
  }

  /**
   * AJAX callback function.
   *
   * Gets called when the "Remove row" button is clicked.
   *
   * @param array $form
   *   The complete form definition.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   */
  final public function removeRow(array &$form, FormStateInterface $form_state) {
    // Determine the trigger button.
    $trigger = $form_state->getTriggeringElement();

    // The trigger carries the name of his section in it's name (the only way to
    // securely transport this piece of information).
    list(, $property, $delta) = explode(':', $trigger['#name']);

    // Determine the correct data row to unset.
    $rows = $form_state->get($property);
    foreach ($rows as $key => $row) {
      if ($key == (int) $delta) {
        unset($rows[$key]);
        break;
      }
    }

    // Set the new value on the form state variable.
    $form_state->set($property, $rows);

    // Trigger a form rebuild.
    $form_state->setRebuild();
  }

  /**
   * Recursively scans the form definition for a section with a given key.
   *
   * @param string $property
   *   The key to search.
   * @param array $form
   *   The form section definition to search.
   *
   * @return array|bool
   *   When found, the form section that matches the key, otherwise FALSE.
   */
  final protected function findSection($property, array &$form) {
    foreach (Element::children($form) as $key) {
      if ($key === $property && isset($form[$key]['multivaluePart'])) {
        return $form[$key];
      }
      elseif (($section = $this->findSection($property, $form[$key])) !== FALSE) {
        return $section;
      }
    }
    return FALSE;
  }

  /**
   * Should a multivalue form portion be sortable?
   *
   * @param string $property
   *   The property name of the form portion.
   *
   * @return bool
   *   TRUE, if the portion should be sortable.
   */
  protected function isSortable($property) {
    return FALSE;
  }

  /**
   * Returns a form element to prefix to the "add row" button.
   *
   * @param string $property
   *   The property name of the current multivalue form section.
   *
   * @return array|false
   *   The element to inject or NULL
   */
  protected function getAddSelector($property) {
    return FALSE;
  }

  /**
   * Are multiple adds without entering values allowed?
   *
   * @param string $property
   *   The property name of the form portion.
   *
   * @return bool
   *   TRUE, if the portion should be sortable.
   */
  protected function allowMultipleEmptyAdds($property) {
    return TRUE;
  }

  /**
   * Returns the form API type of the row container.
   *
   * @param string $property
   *   The name of the property.
   *
   * @return string
   *   The type of the container.
   */
  protected function getRowType($property) {
    return 'fieldset';
  }

  /**
   * Should a title get added to each row fieldset?
   *
   * @param string $property
   *   The name of the property.
   *
   * @return string|false
   *   The row title (if any).
   */
  protected function getRowTitle($property) {
    return FALSE;
  }

  /**
   * What should be written on the "Add row" button?
   *
   * @param string $property
   *   The name of the property.
   *
   * @return string
   *   The string on the "Add row" button.
   */
  protected function getAddRowButtonTitle($property) {
    return 'Add row';
  }

  /**
   * Returns properties that should get injected in the row container.
   *
   * @param string $property
   *   The name of the property.
   *
   * @return array
   *   Properties that are to be mixed in the row container definition.
   */
  protected function getRowProperties($property) {
    return [];
  }

  /**
   * Returns a single row of input elements for a multivalue form portion.
   *
   * @param int $delta
   *   The delta value of the row requested.
   * @param array $row_defaults
   *   The row default values.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object of the current form.
   *
   * @return array
   *   A complete row of input element definitions.
   */
  abstract protected function getInputRow($delta, array $row_defaults, FormStateInterface $form_state);

  /**
   * Returns the data to add to the current configuration.
   *
   * If FALSE is returned, nothing is added.
   *
   * @param string $property
   *   The name of the property where the add action was triggered.
   * @param array $current_state
   *   The currently configured data.
   * @param array $user_input
   *   The actual user input.
   * @param mixed $addSelectorValue
   *   If there is an addSelector, it's value when "add row" was triggered.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   *
   * @return array|false
   *   The data to add to the storage object or FALSE, if
   *   nothing should get added.
   */
  abstract protected function getDataToAdd($property, array $current_state, array $user_input, $addSelectorValue, FormStateInterface $form_state);

}
