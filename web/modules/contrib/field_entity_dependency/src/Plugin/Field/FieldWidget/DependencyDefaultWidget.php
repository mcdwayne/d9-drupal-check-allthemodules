<?php

namespace Drupal\field_entity_dependency\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'DependencyDefaultWidget' widget.
 *
 * @FieldWidget(
 *   id = "DependencyDefaultWidget",
 *   label = @Translation("Dependency select"),
 *   field_types = {
 *     "Dependency"
 *   }
 * )
 */

class DependencyDefaultWidget extends WidgetBase {

  // overwrite the parent construct
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition,$settings, $third_party_settings);
    // save the current field name on the session
    if (php_sapi_name() !== 'cli') {
      $tempstore = \Drupal::service('user.private_tempstore')->get('field_entity_dependency');
      $tempstore->set('field_name', $this->fieldDefinition->getName());
    }
  }

  /**
   * Define the form for the field type.
   *
   * Inside this method we can define the form used to edit the field type.
   *
   * Here there is a list of allowed element types: https://goo.gl/XVd4tA
   */
  public function formElement(FieldItemListInterface $items, $delta, Array $element, Array &$form, FormStateInterface $form_state) {
    $entity_type_level_1 = $this->getSetting('select_nodes');

    $options = array();
    //get the current delta
    $max_delta = $this->getMaxDelta();

    if ($max_delta < 1 || !$entity_type_level_1 ) {
      $element['label_error'] = [
        '#type' => 'label',
        '#title' =>         t('It seems that you haven\'t configured the Dependency field yet. Go to admin/structure/types/manage/[content type]/form-display to configure it.'),
      ];
    }
    else {

      // default values
      $default_values = $items[$delta]->getValue();

      // get the nodes
      $nodes = $this->getNodesByContentType($entity_type_level_1);
      $options['_none'] = '- none -';
      foreach ($nodes as $node) {
        $options[$node->nid->value] = $node->get('title')->value;
      }
      $element['select_parent'] = [
        '#type' => 'select',
        '#title' => $this->getSetting('label_nodes'),
        '#options' => $options,
        '#default_value' => isset($default_values['select_parent']) ? $default_values['select_parent'] : NULL,
        '#ajax' => [
          'callback' => [get_class($this), 'ajax_dropdown_callback'],
          'event' => 'change',
          'wrapper' => 'dropdown-second-replace-0',
        ],
      ];

      // send a variable
      $element['#attached']['drupalSettings']['select_parent'] = isset($default_values['select_parent']) ? $default_values['select_parent'] : NULL;

      for ($i = 0; $i < $max_delta; $i++) {
        // remove the first value
        unset($default_values['select_parent']);
        $default_values = array_values($default_values);
        // get some settings data
        $current_settings = $this->getSetting('select_fieldset');
        if ($current_settings[$i]['label_referenced_nodes']) {
          $default_label = $current_settings[$i]['label_referenced_nodes'];
        }

        if ($current_settings[$i]['select_referenced_nodes']) {
          $default_select = $current_settings[$i]['select_referenced_nodes'];
        }

        if ($i === ($max_delta - 1)) {
          $element['select_child_' . $i] = [
            '#type' => 'select',
            '#title' => isset($default_label) ? $default_label : '',
            '#options' => ['_none' => '- none -'],
            '#default_value' => isset($default_values[$i]) ? $default_values[$i] : NULL,
            '#prefix' => '<div id="dropdown-second-replace-' . $i . '">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
          ];
        }
        else {
          $next = $i + 1;
          $element['select_child_' . $i] = [
            '#type' => 'select',
            '#title' => isset($default_label) ? $default_label : '',
            '#options' => ['_none' => '- none -'],
            '#default_value' => isset($default_values[$i]) ? $default_values[$i] : NULL,
            '#prefix' => '<div id="dropdown-second-replace-' . $i . '">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
            '#ajax' => [
              'callback' => [get_class($this), 'ajax_dropdown_callback'],
              'event' => 'change',
              'wrapper' => 'dropdown-second-replace-' . $next,
            ],
          ];
        }

        $elements['select_child_' . $i]['#after_build'][] = [
          get_class($this),
          'afterBuild'
        ];

        $element['entity_type_level_' . $i] = [
          '#type' => 'hidden',
          '#value' => $default_select,
        ];

        $element['delta_level_' . $i] = [
          '#type' => 'hidden',
          '#value' => $i,
        ];

        // send a variables
        $element['#attached']['drupalSettings']['select_child_' . $i] = isset($default_values[$i]) ? $default_values[$i] : NULL;
      }

      $form['field_name'] = [
        '#type' => 'hidden',
        '#value' => $this->fieldDefinition->getName(),
      ];

      // attach the js library
      $element['#attached']['library'][] = 'field_entity_dependency/selects';

      // add custom validation
      $form['#validate'][] = [
        get_class($this),
        '_form_validation_field_entity_dependency'
      ];

    }

    return $element;
  }

  /**
   * Validations for the reference field
   *
   */
  public static function _form_validation_field_entity_dependency(array &$form, $form_state) {
    // get the current field name
    if (php_sapi_name() !== 'cli') {
      $tempstore = \Drupal::service('user.private_tempstore')->get('field_entity_dependency');
      $field_name = $tempstore->get('field_name');
    }

    // get the current field delta
    $delta = (int)static::getMaxDelta();
    // get the field values
    $values = $form_state->getValue($field_name);

    // validate the parent select
    if (isset($values[0]['select_parent'])) {
      if ($values[0]['select_parent'] === '_none') {
        $form_state->setErrorByName('field_references', t('You must select a valid value, "none" is not a valid value.'));
      }
      else {
        // validate the children selects
        for ($i = 0; $i < $delta; $i++) {
          if ($values[0]['select_child_'.$i] === '_none') {
            $form_state->setErrorByName('select_child'.$i, t('You must select a valid value, "none" is not a valid value.'));
          }
        }
      }
    }
  }

  /**
   * Selects just the second dropdown to be returned for re-rendering
   *
   * Since the controlling logic for populating the form is in the form builder
   * function, all we do here is select the element and return it to be updated.
   *
   * @return renderable array (the second dropdown)
   */
  public static function ajax_dropdown_callback(array &$form, $form_state) {
    $button = $form_state->getTriggeringElement();
    $options = [];
    if (isset($button['#prefix'])) {
      // get the delta from the prefix
      $delta = (int)substr($button['#prefix'], -3, -2);
    }
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // when the children are
    if (isset($delta)) {
      $parent_id = $element['select_child_'.$delta]['#value'];
      $delta = $delta + 1;
    }
    else {
      $delta = 0;
      $parent_id = $element['select_parent']['#value'];
    }
    if (isset($element['entity_type_level_'.$delta])) {
      $entity_type_level = $element['entity_type_level_'.$delta]['#value'];
      $nodes = DependencyDefaultWidget::getFilteredNodes($parent_id, $entity_type_level);
      if (sizeof($nodes) > 0) {
        $options['_none'] = '- none -';
        foreach ((array) $nodes as  $node) {
          if ($node->nid->value) {
            $options[$node->nid->value] = $node->get('title')->value;
          }
        }
      }
      else {
        $options['_none'] = '- none -';
      }
    }

    $element['select_child_'.$delta]['#options'] = $options;

    return $element['select_child_'.$delta];
  }

  /**
   * It defines the setting for the Widget.
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();

    $options = array();
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $options['_none'] = '- none -';
    foreach ($contentTypes as $contentType) {
      $options[$contentType->id()] = $contentType->label();
    }

    $element['select_nodes'] = [
      '#type' => 'select',
      '#title' => t('Select the main entity'),
      '#default_value' => $this->getSetting('select_nodes'),
      '#required' => TRUE,
      '#options' => $options,
      '#description' => t('Entities list.'),
      '#ajax' => array(
        'callback' => [get_class($this), 'ajax_settings_dropdown_callback'],
        'event' => 'change',
        'wrapper' => 'names-fieldset-wrapper',
      ),
    ];

    $element['label_nodes'] = [
      '#type' => 'textfield',
      '#title' => 'The label to display for the main entity select.',
      '#default_value' => $this->getSetting('label_nodes'),
    ];

    $element = $this->settingsMultipleForm($form, $form_state, $element, $field_name);

    // save the field name
    $element['current_field_name'] = [
      '#type' => 'hidden',
      '#value' => $field_name,
    ];


    return $element;
  }

  /**
   * It defines the setting for the Widget.
   *
   */
  public function settingsMultipleForm(array $form, FormStateInterface $form_state, $element, $field_name) {

    $max_field = $form_state->get('items_count');
    $form['#tree'] = TRUE;

    $element['select_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Referenced Entities'),
      '#prefix' => "<div id='names-fieldset-wrapper'>",
      '#suffix' => '</div>',
    ];

    if (empty($max_field)) {
      $settings_delta = DependencyDefaultWidget::getMaxDelta();
      // check if there is default value on settings
      if (!empty($settings_delta)) {
        $max_field = $settings_delta;
        $form_state->set('items_count', $max_field);
      }
      else {
        $max_field = 1;
        $form_state->set('items_count', 1);
      }
    }

    // save max delta
    $this->saveMaxDelta($max_field, $field_name);

    // the dynamic fields
    for($delta = 0; $delta < $max_field; $delta++) {
      $current_settings = $this->getSetting('select_fieldset');
      // variables definition
      $default_label = '';
      $default_select = '';
      if (isset($current_settings[$delta])) {
        if ($current_settings[$delta]['label_referenced_nodes']) {
          $default_label = $current_settings[$delta]['label_referenced_nodes'];
        }

        if ($current_settings[$delta]['select_referenced_nodes']) {
          $default_select = $current_settings[$delta]['select_referenced_nodes'];
        }
      }

      $element['select_fieldset'][$delta]['select_referenced_nodes'] = [
        '#type' => 'select',
        '#title' => t('Select a related entity'),
        '#default_value' => $default_select,
        '#options' => $this->getChildSelectConfig($field_name, $delta),
        '#required' => TRUE,
        '#description' => t('Related entities list.'),
        '#prefix' => "<div class='inner-fieldset'>",
        '#validated' => TRUE,
      ];

      $element['select_fieldset'][$delta]['label_referenced_nodes'] = [
        '#type' => 'textfield',
        '#title' => 'The label to display for the referenced entity select.',
        '#default_value' => $default_label,
        '#suffix' => '</div>'
      ];
    }


    // the add more action
    $element['add_entity'] = [
      '#type' => 'submit',
      '#value' => t('Add Item'),
      '#submit' => [[get_class($this), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => "names-fieldset-wrapper",
        'effect' => 'fade',
      ],
    ];

    // the add more action
    $element['remove_entity'] = [
      '#type' => 'submit',
      '#value' => t('Remove Item'),
      '#submit' => [[get_class($this), 'removeMoreSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'addMoreAjax'],
        'wrapper' => "names-fieldset-wrapper",
        'effect' => 'fade',
      ],
    ];

    // save the current delta
    $element['current_delta'] = [
      '#type' => 'hidden',
      '#value' => DependencyDefaultWidget::getMaxDelta(),
    ];

    // avoid the cache
    $element['#cache'] = ['max-age' => 0];

    return $element;
  }

  /**
   * Submission handler for the "Add another item" button.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    // get the triggering element
    $button = $form_state->getTriggeringElement();
    // get the current delta
    $delta = $form_state->get('items_count');
    // gte the element
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    // gte the select value
    $value_id = $element['select_fieldset'][$delta - 1]['select_referenced_nodes']['#value'];
    // look for options
    $options = DependencyDefaultWidget::getFilteredContentTypes($value_id);

    if ($delta < 5) {
      if ($options != false && sizeof($options) > 0) {
        // increase the counter
        $form_state->set('items_count', $delta + 1);
      }
      else {
        // throw a error message
        drupal_set_message('Submit Error. There is not related entities to '.$value_id, 'error');
      }
    }
    else {
      // throw a error message
      drupal_set_message('Submit Error. Too many nested elements', 'error');
    }


    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    // get the triggered element
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    if (!array_search('remove_entity', $button['#array_parents'])) {
      // the current delta
      $delta = (int)$form_state->get('items_count');
      // get the last select value (2 because the submit already did the addition)
      $value_id = $element['select_fieldset'][$delta - 2]['select_referenced_nodes']['#value'];
      // search for options
      $options = DependencyDefaultWidget::getFilteredContentTypes($value_id);

      // result validations
      if ($options != false && sizeof($options) > 0) {
        // Add a DIV around the delta receiving the Ajax effect.
        $element['select_fieldset'][$delta]['#prefix'] = '<div class="ajax-new-content">' . (isset($element[$delta]['#prefix']) ? $element[$delta]['#prefix'] : '');
        $element['select_fieldset'][$delta]['#suffix'] = (isset($element[$delta]['#suffix']) ? $element[$delta]['#suffix'] : '') . '</div>';
        // set the options
        $element['select_fieldset'][$delta - 1]['select_referenced_nodes']['#options'] = $options;
        $field_name = $element['current_field_name']['#value'];
        DependencyDefaultWidget::saveChildSelectConfig($options, $field_name, ($delta - 1));
      }
      else {
        // throw a error message
        drupal_set_message('Ajax Error. There is not related entities to '.$value_id, 'error');
      }
    }

    // return the response
    return $element['select_fieldset'];
  }

  /**
   * Submission handler for the "Remove
   * another item" button.
   */
  public static function removeMoreSubmit(array $form, FormStateInterface $form_state) {
    // get the current value
    $val = $form_state->get('items_count');
    // it must be at least 1
    if ($val > 1) {
      $form_state->set('items_count', $val - 1);
    }
    else {
      drupal_set_message('Ajax Error. You must have at least one nested element.', 'error');
    }
    // rebuild the form
    $form_state->setRebuild();
  }

  /**
   * Selects just the second dropdown to be returned for re-rendering
   *
   * Since the controlling logic for populating the form is in the form builder
   * function, all we do here is select the element and return it to be updated.
   *
   * @return renderable array (the second dropdown)
   */
  public static function ajax_settings_dropdown_callback(array &$form, $form_state) {
    // get the element that triggers the action
    $button = $form_state->getTriggeringElement();
    // get the element
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    if (strpos( $button['#name'], 'select_nodes' ) !== false) {
      $parent_id = $element['select_nodes']['#value'];
      $options = DependencyDefaultWidget::getFilteredContentTypes($parent_id);
      if ($options) {
        // set the select options
        $element['select_fieldset'][0]['select_referenced_nodes']['#options'] = $options;
        $field_name = $element['current_field_name']['#value'];
        DependencyDefaultWidget::saveChildSelectConfig($options, $field_name, 0);
      }
      else {
        drupal_set_message('There is not related entities to '.$parent_id, 'error');
        $element['select_referenced_nodes']['#errors'] = array('There is an error.');
        // clear the select options
        $options['_none'] = '- none -';
        $element['select_fieldset'][0]['select_referenced_nodes']['#options'] = $options;
      }

     // return $element;
    }
    else {

      //return;
    }

    return $element['select_fieldset'];
  }

  /**
   * After-build handler for field elements in a form.
   *
   * This stores the final location of the field within the form structure so
   * that flagErrors() can assign validation errors to the right form element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    // send the element id
    $element['#attached']['drupalSettings']['id_parent'] =  $element['#id'];
    return $element;
  }


  /**
   * It defines the default setting for the settings widget form.
   *
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings_default = [
      'current_delta' => FALSE,
      'select_nodes' => FALSE,
      'label_nodes' => FALSE,
    ];

    // get the max delta
    $delta = (int)static::getMaxDelta();
    for ($i = 0; $i < $delta; $i++) {
      // set the default settings
      $settings_default['select_fieldset'][$i] = [
        'select_referenced_nodes' => FALSE,
        'label_referenced_nodes' => FALSE
      ];
    }

    return $settings_default + parent::defaultSettings();
  }

  /**
   * It defines the setting summary on the Widget.
   *
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // get the parent info
    $summary = parent::settingsSummary();
    // validate the value
    if ($this->getSetting('select_nodes')) {
      $summary[] = t('Main entity selected: '. $this->getSetting('select_nodes'));
    }
    else {
      $summary[] = t('Main entity selected: none');
    }

    $current_settings = $this->getSetting('select_fieldset');
    // get the max delta
    $delta = (int)static::getMaxDelta();
    // go through each level
    for ($i = 0; $i < $delta; $i++) {
      $value = $current_settings[$i]['select_referenced_nodes'];
      $level = $i + 1;
      // validate the values
      if ($value != '_none' && $value != '') {
        $summary[] = t('Referenced entities: '. $value .' (Level= '.$level.')');
      }
      else {
        $summary[] = t('Referenced entities: none (Level= '.$level.')');
      }
    }

    return $summary;
  }

  /**
   * It gets by a given content type.
   *
   */
  function getNodesByContentType($content_type) {
    $values = ['type' => $content_type];
    $nodes = \Drupal::entityTypeManager()->getListBuilder('node')->getStorage()->loadByProperties($values);
    return $nodes;
  }

  /**
   * It gets the filtered nodes by a parent reference.
   *
   */
  public static function getFilteredNodes($parent_id, $content_type) {
    // get the fields
    $entityFieldManager = Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions('node', $content_type);
    // look for the entity reference fields
    foreach ($fields as $field) {
      $type = $field->getType();
      if ($type === 'entity_reference') {
        $field_name = $field->getName();
        if ($field_name != 'revision_uid' && $field_name != 'uid' && $field_name != 'menu_link') {
          $values = ['type' => $content_type, $field_name => $parent_id];
          $nodes = \Drupal::entityTypeManager()->getListBuilder('node')->getStorage()->loadByProperties($values);
          if (!empty($nodes) && $nodes != null) {
            return $nodes;
          }
        }
      }
    }
  }

  /**
   * It gets the content types related.
   *
   */
  public static function getFilteredContentTypes($parent_id) {
    // array response
    $results = [];
    // get a list of the content available on the site
    $content_type_list = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    // get the fields
    $entityFieldManager = Drupal::service('entity_field.manager');
    foreach ($content_type_list as $content_type) {
      $fields = $entityFieldManager->getFieldDefinitions('node', $content_type->id());
      // look for the entity reference fields
      foreach ($fields as $field) {
        $type = $field->getType();
        if ($type === 'entity_reference') {
          $settings = $field->getSettings();
          if (isset($settings['handler_settings']['target_bundles'][$parent_id])) {
            if ($settings['handler_settings']['target_bundles'][$parent_id] === $parent_id) {
              $results[$content_type->id()] = $content_type->id();
            }
          }
        }
      }
    }

    if (sizeof($results) > 0) {
      return $results;
    }
    else {
      return false;
    }
  }

  /**
   * It saves the children selects config.
   *
   */
  public static function saveChildSelectConfig($options, $field_name, $delta) {
    // get the current value
    $config = \Drupal::config('field_entity_dependency.settings');
    $array_children = $config->get('children_options');

    $concat = $field_name.','.$delta.',';
    foreach ($options as $item) {
      $concat .= $item.',';
    }

    $count = 0;
    $flag = 0;
    foreach ((array)$array_children as $child) {
      $split = explode(',', $child);
      if ($split[0] === $field_name && $split[1] === $delta) {
        // update the value
        $array_children[$count] = $concat;
        $flag = 1;
      }
      $count++;
    }
    // update and save the value
    if ($flag === 0) {
      $array_children[] = $concat;
    }
    $config = \Drupal::service('config.factory')->getEditable('field_entity_dependency.settings');
    $config->set('children_options', $array_children)->save();
  }

  /**
   * It gets the children selects config.
   *
   */
  public static function getChildSelectConfig($field_name, $delta) {
    $config = \Drupal::config('field_entity_dependency.settings');
    $array_children = $config->get('children_options');
    $options = [];

    if ($array_children) {
      $flag = 0;
      foreach ((array)$array_children as $child) {
        $split = explode(',', $child);
        if ($split[0] != '' && $split[0] != null && $split[0] === $field_name && $split[1] === (string)$delta) {
          // remove the control values
          unset($split[0]);
          unset($split[1]);
          foreach ($split as $item) {
            if ($item != '') {
              $options[$item] = $item;
            }
          }
          // indicate that there are data
          $flag++;
        }
      }

      if ($flag === 0) {
        // set default option
        $options['_none'] = '- none -';
      }
    }
    else {
      // set default option
      $options['_none'] = '- none -';
    }

    return $options;
  }

  /**
   * It saves the children max delta config.
   *
   */
  public static function saveMaxDelta($max, $field_name) {
    $data = $field_name.','.$max;
    $config = \Drupal::config('field_entity_dependency.settings');
    $children = $config->get('child_delta');
    $flag = 0;
    if ($children) {
      $count = 0;
      foreach ((array)$children as $child) {
        $split = explode(',', $child);
        if ($split[0] === $field_name) {
          // update the value
          $children[$count] = $data;
          $flag = 1;
        }
        $count++;
      }
    }
    // update and save the value
    if ($flag === 0) {
      $children[] = $data;
    }

    $config = \Drupal::service('config.factory')->getEditable('field_entity_dependency.settings');
    $config->set('child_delta', $children)->save();
  }

  /**
   * It gets the children max delta config.
   *
   */
  public static function getMaxDelta() {
    // get info saved on session
    $field_name = '';
    if (php_sapi_name() !== 'cli') {
      $tempstore = \Drupal::service('user.private_tempstore')->get('field_entity_dependency');
      $field_name = $tempstore->get('field_name');
    }
    // get config data
    $config = \Drupal::config('field_entity_dependency.settings');
    $children_delta = $config->get('child_delta');
    $max = '';

    if ($children_delta) {
      $flag = 0;
      foreach ((array)$children_delta as $child) {
        $split = explode(',', $child);
        if ($split[0] != '' && $split[0] != null && $split[0] === $field_name) {
          $max = $split[1];
          // indicate that there are data
          $flag++;
        }
      }

      if ($flag === 0) {
        // set default option
        $max = 0;
      }
    }
    else {
      // set default option
      $max = 0;
    }

    return $max;
  }
}
