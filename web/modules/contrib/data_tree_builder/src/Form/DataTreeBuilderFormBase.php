<?php

namespace Drupal\data_tree_builder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Drupal\Core\Link;
use Drupal\Component\Utility\NestedArray;

/**
 * Data tree builder base class.
 */
abstract class DataTreeBuilderFormBase extends ConfigFormBase {

  /**
   * The name of the configuration entity.
   */
  const CONFIG_NAME = '';

  /**
   * AJAX callback route name.
   */
  const AJAX_ROUTE = '';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tree_structure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $initial_path = NULL) {
    $data = $this->getData($form_state, $initial_path);

    $identifier = strtr($this->getFormId(), ['_' => '-']);

    $ajax = [
      'callback' => [$this, 'ajaxCallback'],
      'wrapper' => $identifier . '-wrapper',
    ];
    $form['#ajax_selector'] = '#' . $ajax['wrapper'];
    $form['#prefix'] = '<div id="' . $ajax['wrapper'] . '">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    // Display data structure list.
    $form['display_structure'] = $this->getDataRenderable($data);

    // Go back element.
    if (!empty($data['position'])) {
      $form['ascend'] = [
        '#type' => 'submit',
        '#value' => $this->t('Ascend'),
        '#ajax' => $ajax,
      ];
    }

    // The table element.
    $form['elements'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('ID'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $identifier . '-weight',
        ],
      ],
    ];

    foreach ($this->getTableFields([], []) as $field) {
      if (isset($field['#title'])) {
        $form['elements']['#header'][] = $field['#title'];
      }
    }
    $form['elements']['#header'][] = $this->t('Weight');
    $form['elements']['#header'][] = $this->t('Actions');

    // Append the addition element.
    $add_element_weight = -50;
    if (isset($data['current_level']['elements'])) {
      foreach ($data['current_level']['elements'] as $element) {
        if ($element['weight'] >= $add_element_weight) {
          $add_element_weight = $element['weight'] + 1;
        }
      }
    }

    $data['current_level']['elements'][0] = [
      'label' => '',
      'weight' => $add_element_weight,
      'percentage' => 0,
    ];

    // Table rows.
    foreach ($data['current_level']['elements'] as $id => $element) {

      $form['elements'][$id]['#attributes']['class'][] = 'draggable';

      $form['elements'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $element['label'],
        '#maxlength' => 512,
      ];
      $form['elements'][$id]['id'] = [
        '#type' => 'textfield',
        '#size' => 30,
        '#default_value' => $id ? $id : '',
      ];
      $form['elements'][$id] = $this->getTableFields($form['elements'][$id], $element, $form_state);

      $form['elements'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $element['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $element['weight'],
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => [$identifier . '-weight']],
        '#delta' => 50,
      ];

      $form['elements'][$id]['actions'] = ['#type' => 'actions'];

      if ($id !== 0) {
        $form['elements'][$id]['actions']['descendToElement'] = [
          '#type' => 'submit',
          '#value' => $this->t('Descend'),
          '#name' => 'descend-' . $id,
          '#ajax' => $ajax,
        ];
        $form['elements'][$id]['actions']['deleteElement'] = [
          '#type' => 'submit',
          '#value' => $this->t('Delete'),
          '#name' => 'delete-' . $id,
          '#ajax' => $ajax,
        ];
        if (!empty($element['elements'])) {
          $form['elements'][$id]['actions']['deleteElement']['#disabled'] = TRUE;
        }
      }
      else {
        $form['elements'][$id]['actions']['addElement'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add'),
          '#name' => 'add',
          '#ajax' => $ajax,
        ];
      }
    }

    // Additional parameters of an item.
    if (!empty($data['position'])) {
      $ajax_id = $identifier . '-parameters-wrapper';

      if ($this->getOp($form_state) === 'parameters_onoff') {
        $parameters_on = $form_state->getValue('parameters_onoff');
      }
      else {
        $parameters_on = !empty($data['current_level']['_parameters']);
      }
      $form['parameters_onoff'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Additional parameters'),
        '#default_value' => $parameters_on,
        '#ajax' => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper' => $ajax_id,
        ],
      ];
      $form['_parameters'] = [
        '#type' => 'container',
        '#attributes' => ['id' => $ajax_id],
        '#tree' => TRUE,
      ];
      if ($parameters_on) {
        if (!isset($data['current_level']['_parameters'])) {
          $data['current_level']['_parameters'] = [];
        }
        $form['_parameters']['#type'] = 'fieldset';
        $form['_parameters']['#title'] = $this->t('Additional parameters');

        $parameter_values = $form_state->getValue('_parameters');
        if (is_null($parameter_values)) {
          $parameter_values = $data['current_level']['_parameters'];
        }
        $form['_parameters'] = $this->getParameterForm($form['_parameters'], $parameter_values, $form_state);
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
      '#ajax' => $ajax,
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * Get renderable array of the data.
   */
  protected function getDataRenderable($data, $parents = []) {
    $list = [
      '#theme' => 'item_list',
      '#items' => [],
    ];
    if (empty($parents)) {
      $list['#title'] = $this->t('Data structure');
      $list['#empty'] = $this->t('There is no data structure yet.');
      $current_level = $data['structure'];
    }
    else {
      $current_level = NestedArray::getValue($data['structure'], $parents);
    }
    // Check if we're in the current tree.
    $current_tree = TRUE;
    $loop_end = count($parents);
    for ($i = 0; $i < $loop_end; $i++) {
      if (!isset($data['position'][$i]) || $parents[$i] !== $data['position'][$i]) {
        $current_tree = FALSE;
        break;
      }
    }

    // Render items.
    foreach ($current_level['elements'] as $id => $element) {
      $items = [
        'id' => $this->t('ID: %id', ['%id' => $id]),
      ];

      foreach ($this->getTableFields([], []) as $field_id => $field) {
        if (isset($field['#title'])) {
          $items[$field_id] = $field['#title'] . ': ' . $element[$field_id];
          if (isset($field['#field_suffix'])) {
            $items[$field_id] .= $field['#field_suffix'];
          }
        }
      }

      $item_html = $element['label'] . ' (' . implode(', ', $items) . ')';
      if (!empty($element['_parameters'])) {
        $item_html .= ' *';
      }

      // Highlight the current path.
      if ($current_tree && isset($data['position'][count($parents) + 1])) {
        $level_id = $data['position'][count($parents) + 1];
        if ($level_id === $id) {
          $item_html = '<strong>' . $item_html . '</strong>';
          $list['#items'][$id]['#wrapper_attributes'] = ['class' => ['current-branch']];
        }
      }

      // Add AJAX link to display the exact element.
      $branch = $parents;
      $branch[] = 'elements';
      $branch[] = $id;
      $filtered_branch = [];
      for ($i = 0; $i < count($branch); $i++) {
        if ($i % 2 !== 0) {
          $filtered_branch[] = $branch[$i];
        }
      }

      $link = Link::fromTextAndUrl(
        Markup::create($item_html),
        Url::fromRoute(static::AJAX_ROUTE, [], ['query' => ['path' => implode(',', $filtered_branch)]])
      )->toRenderable();
      $link['#attributes']['class'][] = 'use-ajax';
      $list['#items'][$id]['value'] = $link;

      // Render children.
      if (isset($element['elements'])) {
        $next_parents = $parents;
        array_push($next_parents, 'elements', $id);
        $list['#items'][$id]['elements'] = $this->getDataRenderable($data, $next_parents);
      }

    }

    if (empty($parents)) {
      $list['#suffix'] = '<div class="legend"><br />' . $this->t('* Element contains parameters.') . '</div>';
    }

    return $list;
  }

  /**
   * Helper function to get parameters form fields.
   */
  protected function getParameterForm(array $element, array $parameters, FormStateInterface $form_state) {
    return $element;
  }

  /**
   * Helper function to get performed operation.
   */
  protected function getOp(FormStateInterface $form_state) {
    $op = '';
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger)) {
      $op = $trigger['#parents'][count($trigger['#parents']) - 1];
    }
    return $op;
  }

  /**
   * AJAX callback.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    if ($this->getOp($form_state) === 'parameters_onoff') {
      return $form['_parameters'];
    }
    return $form;
  }

  /**
   * Helper method to get form storage.
   */
  protected function getData(FormStateInterface $form_state, $initial_path = NULL) {
    $storage = $form_state->getStorage();
    if (empty($storage['structure'])) {
      $structure = $this->config(static::CONFIG_NAME)->getRawData();
      if (empty($structure)) {
        $structure = [];
      }
    }
    else {
      $structure = $storage['structure'];
    }

    if (!isset($storage['position']) && !empty($initial_path)) {
      $storage['position'] = [];
      for ($i = 0; $i < count($initial_path); $i++) {
        $storage['position'][] = 'elements';
        $storage['position'][] = $initial_path[$i];
      }
      $form_state->setStorage($storage);
    }

    if (!empty($storage['position'])) {
      $data = [
        'structure' => $structure,
        'current_level' => NestedArray::getValue($structure, $storage['position']),
        'position' => $storage['position'],
      ];
    }
    else {
      $data = [
        'structure' => $structure,
        'current_level' => $structure,
        'position' => [],
      ];
    }
    return $data;
  }

  /**
   * Helper function to save structure.
   */
  protected function setStructure($data, FormStateInterface $form_state) {
    // First sort the current level elements by weight.
    if (!empty($data['current_level']['elements'])) {
      uasort($data['current_level']['elements'], ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    }

    if (!empty($data['position'])) {
      NestedArray::setValue($data['structure'], $data['position'], $data['current_level']);
    }
    else {
      $data['structure'] = $data['current_level'];
    }
    unset($data['current_level']);

    $config = $this->config(static::CONFIG_NAME);
    $config->setData($data['structure']);
    $config->save();

    $form_state->setStorage($data);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#parents'][count($trigger['#parents']) - 1];
    $form_state->cleanValues();

    $data = $this->getData($form_state);

    // Update rows in case changes have been made.
    $elements = $form_state->getValue('elements');
    // Skip the add element.
    unset($elements[0]);
    foreach ($elements as $id => $element) {
      foreach ($element as $property => $value) {
        // Skip reserved keys.
        if (in_array($property, ['id', 'actions', 'elements', '_parameters'])) {
          continue;
        }
        $data['current_level']['elements'][$id][$property] = $value;
      }
    }

    // Set or remove parameters.
    $parameters_on = $form_state->getValue('parameters_onoff');
    if ($parameters_on) {
      $parameters = $form_state->getValue('_parameters');
      if (!empty($parameters)) {
        $data['current_level']['_parameters'] = $parameters;
      }
      else {
        $parameters_on = FALSE;
      }
    }
    if (!$parameters_on && isset($data['current_level']['_parameters'])) {
      unset($data['current_level']['_parameters']);
    }

    // Move elements in the tree if IDs changed.
    $new_ids = [];
    foreach ($elements as $id => $element) {
      if ($element['id'] !== $id && isset($data['current_level']['elements'][$id])) {
        $new_ids[$id] = $element['id'];
        $new_element = $data['current_level']['elements'][$id];
        unset($data['current_level']['elements'][$id]);
        $data['current_level']['elements'][$element['id']] = $new_element;
      }
    }

    // Get the trigger row id and execute the right method.
    if (method_exists($this, $op)) {
      if (!in_array($op, ['ascend'])) {
        $id = $trigger['#parents'][count($trigger['#parents']) - 3];
      }
      else {
        $id = 0;
      }

      if (isset($new_ids[$id])) {
        $id = $new_ids[$id];
      }
      $this->{$op}($id, $form_state, $data);
    }
    else {
      $this->setStructure($data, $form_state);
      parent::submitForm($form, $form_state);
    }

    // Always unset user values after submit methods were executed,
    // as we rely on db and form_state storage for elements instead.
    $user_input = $form_state->getUserInput();
    unset($user_input['elements']);
    unset($user_input['parameters_onoff']);
    unset($user_input['_parameters']);
    $form_state->setUserInput($user_input);
  }

  /**
   * Add a row.
   */
  protected function addElement($id, FormStateInterface $form_state, array &$data) {
    $add_data = $form_state->getValue(['elements', 0]);

    $data['current_level']['elements'][$add_data['id']] = [
      'label' => $add_data['label'],
      'weight' => $add_data['weight'],
      'percentage' => $add_data['percentage'],
    ];
    $this->setStructure($data, $form_state);
  }

  /**
   * Delete a row.
   */
  protected function deleteElement($id, FormStateInterface $form_state, array &$data) {
    unset($data['current_level']['elements'][$id]);
    $this->setStructure($data, $form_state);
  }

  /**
   * Descend to element.
   */
  protected function descendToElement($id, FormStateInterface $form_state, array &$data) {
    // Save data first.
    $this->setStructure($data, $form_state);

    // Change level.
    $storage = $form_state->getStorage();
    if (!isset($storage['position'])) {
      $storage['position'] = [];
    }
    $storage['position'][] = 'elements';
    $storage['position'][] = $id;
    $form_state->setStorage($storage);
  }

  /**
   * Ascend one level.
   */
  protected function ascend($id, FormStateInterface $form_state, array &$data) {
    // Save data first.
    $this->setStructure($data, $form_state);

    // Change level.
    $storage = $form_state->getStorage();
    if (!isset($storage['position'])) {
      $storage['position'] = [];
    }
    else {
      for ($i = 0; $i < 2; $i++) {
        unset($storage['position'][count($storage['position']) - 1]);
      }
    }
    $form_state->setStorage($storage);
  }

}
