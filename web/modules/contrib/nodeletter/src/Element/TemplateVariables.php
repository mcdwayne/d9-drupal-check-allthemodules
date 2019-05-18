<?php

namespace Drupal\nodeletter\Element;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\nodeletter\Entity\NodeTypeSettings;
use Drupal\Component\Utility\Html;
use Drupal\nodeletter\Entity\TemplateVariableSetting;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TemplateVariables
 * @package Drupal\nodeletter\Element
 *
 * @FormElement("template_variables")
 */
class TemplateVariables extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#nodeletter_settings' => NULL,
      '#progress_indicator' => 'throbber',
      '#progress_message' => NULL,
      '#process' => [
        [$class, 'processTemplateVariables'],
      ],
      '#after_build' => [
        [$class, 'preserveValues'],
      ],
      '#element_validate' => [
        [$class, 'validateTemplateVariables'],
      ],
      '#pre_render' => [
        [$class, 'preRenderTemplateVariablesElement'],
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      /** @var NodeTypeSettings $settings */
      $settings = $element['#nodeletter_settings'];
      if (empty($settings)) {
        // For default value handling, simply return #default_value. Additionally,
        // for a NULL default value, set #has_garbage_value to prevent
        // FormBuilder::handleInputElement() converting the NULL to an empty
        // string, so that code can distinguish between nothing selected and the
        // selection of a radio button whose value is an empty string.
        $element['#has_garbage_value'] = TRUE;
        return [];
      } else {
        return $settings->getTemplateVariables();
      }
    } else if (is_null($input)) {
      return [];
    } else {

      $tpl_vars = [];
      foreach($input as $index => $row) {

        if (empty($row['field']) && empty($row['variable_name']))
          continue;

        // Since "storing" arbitrary formatter settings inside a Drupal form
        // element prove to be impossible in a clean and reliable manner
        // the solution of passing a serialized settings object back and
        // force between server and browser in a hidden html field seems to
        // be the most stable approach without relying on any Drupal form
        // storage variant (which could only be enforced if this whole logic
        // is implemented on form level, not element level.
        if (!empty($row['formatter_settings']['data'])) {
          $data = unserialize($row['formatter_settings']['data']);
          if ($data === FALSE) {
            unset($row['formatter_settings']);
          } else {
            $row['formatter_settings'] = $data;
          }
        }
        $var_setting = array_merge([
          'weight' => $index,
          'formatter_settings' => []
        ], $row);
        $tpl_vars[] = TemplateVariableSetting::fromArray($var_setting);
      }

      return $tpl_vars;
    }
  }

  public static function preserveValues($element, FormStateInterface $form_state) {

    // Since this element builds on sub-elements (selects, textfields, ...)
    // it's necessary to overrule their values by explicitly settings
    // this elements values before the sub-elements read $form_state->values.
    $form_state->setValueForElement($element, $element['#value']);

    return $element;
  }

  public static function processTemplateVariables(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var NodeTypeSettings $settings */
    $settings = $element['#nodeletter_settings'];

    $ajax_wrapper_id = Html::getUniqueId("edit-tpl-vars");

    $ajax_settings = [
      'callback' => [get_called_class(), 'ajaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    $tpl_var_header = [
      t('Variable name'),
      t('Node field'),
      [
        'data' => t('Field formatter'),
        'colspan' => 2,
      ],
    ];

    $tpl_var_rows = [];

    $last_template_var = NULL;
    $formatter_settings_edit_idx = $form_state->get('formatter_settings_edit');
    foreach($element['#value'] as $idx => $tpl_var) {
      /** @var TemplateVariableSetting $tpl_var */

      if ( ! $tpl_var instanceof  TemplateVariableSetting) {
        // skip non template variables
        continue;
      }
      $last_template_var = $tpl_var; // see code after foreach.

      $formatter_settings_editing =  !is_null($formatter_settings_edit_idx) &&
        $formatter_settings_edit_idx == $idx;

      $name_element = [
        '#type' => 'textfield',
        '#size' => 23,
        '#default_value' => $tpl_var->getVariableName(),
      ];

      $field_name = $tpl_var->getField();
      $field_element = [
        '#type' => 'select',
        '#options' => self::getNodeTypeFieldOptions($settings->getNodeTypeId()),
        '#default_value' => $field_name,
        '#ajax' => $ajax_settings,
      ];

      $formatter_element = [];
      $formatter_settings_element = [];
      if ( ! empty($field_name) ) {

        /** @var EntityFieldManagerInterface $entityFieldManager */
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $field_definitions = $entityFieldManager->getFieldDefinitions('node', $settings->getNodeTypeId());
        if ( empty($field_definitions[$field_name]) ) {
          throw new \Exception("Illegal parameter \$field_name");
        }
        $field_definition = $field_definitions[$field_name];
        $formatter_id = $tpl_var->getFormatter();

        list($options, $default_id) = self::getFormatterOptions($field_definition);
        $formatter_id = in_array($formatter_id, array_keys($options)) ? $formatter_id : $default_id;
        $formatter_element = [
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $formatter_id,
          '#ajax' => $ajax_settings,
        ];


        /** @var FormatterPluginManager $formatterManager */
        $formatterManager = \Drupal::service(
          'plugin.manager.field.formatter');
        $formatter = $formatterManager->getInstance([
          'field_definition' => $field_definition,
          'view_mode' => 'nodeletter',
          'configuration' => [
            'label' => 'hidden',
            'type' => $formatter_id,
            'settings' => $tpl_var->getFormatterSettings(),
          ],
        ]);
        if ( ! empty($formatter) && ! empty($formatter->getSettings()) ) {

          // build formatter settings summary or form - depending on
          // the form_states value
          // @see ajaxFormatterSettingsSubmit()
          //
          if ($formatter_settings_editing) {

            $formatter_settings_element = $formatter->settingsForm($element,
              $form_state);
            self::cleanFormatterSettingsForm($formatter_settings_element,
              $field_name, $element['#array_parents']);
            // append submit buttons
            $formatter_settings_element['actions'] = [
              '#type' => 'actions',
              'submit' => [
                '#type' => 'submit',
                '#button_type' => 'primary',
                '#value' => t('Submit'),
                '#name' => 'submit-' . $idx,
                '#submit' => [[get_called_class(), 'ajaxFormatterSettingsSubmit']],
                '#ajax' => $ajax_settings,
              ],
            ];

          } else {

            $summary = $formatter->settingsSummary();
            $formatter_settings_element = [
              '#type' => 'container',
              'summary' => [
                '#type' => 'inline_template',
                '#template' => '<div class="field-plugin-summary">'.
                  '{{ summary|safe_join("<br />") }}</div>',
                '#context' => ['summary' => $summary],
              ],
              'actions' => [
                '#type' => 'actions',
                'edit' => [
                  '#type' => 'image_button',
                  '#src' => 'core/misc/icons/787878/cog.svg',
                  '#submit' => [[get_called_class(), 'ajaxFormatterSettingsSubmit']],
                  '#edit_row_idx' => $idx,
                  '#attributes' => [
                    'class' => ['field-plugin-settings-edit'],
                    'alt' => t('Edit')],
                  '#name' => 'edit-' . $idx,
                  '#ajax' => $ajax_settings,
                ],
              ],
              'data' => [
                '#type' => 'hidden',
                '#value' => serialize($tpl_var->getFormatterSettings()),
              ]
            ];

          }
        }
      }

      $tpl_var_rows[] = [
        'variable_name' => $name_element,
        'field' => $field_element,
        'formatter' => $formatter_element,
        'formatter_settings' => $formatter_settings_element,
      ];
    }

    $add_empty_row = FALSE;
    if (empty($element['#value'])) {
      $add_empty_row = TRUE;
    } else if ($last_template_var) {
      if ( ! empty($last_template_var->getVariableName()) ||
        ! empty($last_template_var->getField()) ) {
        $add_empty_row = TRUE;
      }
    }

    if ($add_empty_row) {
      $tpl_var_rows[] = [
        'variable_name' => [
          '#type' => 'textfield',
          '#size' => 23,
          '#default_value' => '',
        ],
        'field' => [
          '#type' => 'select',
          '#options' => self::getNodeTypeFieldOptions($settings->getNodeTypeId()),
          '#default_value' => '',
          '#ajax' => $ajax_settings,
        ],
        'formatter' => [],
        'formatter_settings' => [],
      ];
    }

    // TODO: Add tabledrag to the template variables table.

    $tpl_table_element = [
      '#type' => 'table',
      '#header' => $tpl_var_header,
      '#parents' => $element['#parents'],
    ];

    $element['table'] = array_merge($tpl_table_element, $tpl_var_rows);
    $element['#tree'] = TRUE;
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';
    return $element;
  }

  public static function ajaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Retrieve the element to be rendered.
    $element = NestedArray::getValue($form, $form_parents);

    return $element;

//    $status_messages = ['#type' => 'status_messages'];
//
//    /** @var \Drupal\Core\Render\RendererInterface $renderer */
//    $renderer = \Drupal::service('renderer');
//    $element['#prefix'] .= $renderer->renderRoot($status_messages);
//    $output = $renderer->renderRoot($element);
//
//    $response = new AjaxResponse();
//    $response->setAttachments($element['#attached']);
//
//    return $response->addCommand(new ReplaceCommand(NULL, $output));

  }

  public static function ajaxFormatterSettingsSubmit(&$form, FormStateInterface &$form_state) {

    $trigger_element = $form_state->getTriggeringElement();
    $element_name = explode('-', $trigger_element['#name'], 2);
    $op = $element_name[0];
    $index = $element_name[1];

    switch($op) {
      case 'edit':
        $form_state->set('formatter_settings_edit', $index);
        break;
      case 'submit':
      default:
        $form_state->set('formatter_settings_edit', NULL);
        break;
    }

    $form_state->setRebuild();
  }

    /**
   * Render API callback: Validates the template_variables element.
   */
  public static function validateTemplateVariables(&$element, FormStateInterface $form_state, &$complete_form) {

    // TODO: implement
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if ($clicked_button != 'remove_button' && !empty($element['vars']['#value'])) {

    }


    /** @var TemplateVariableSetting $tpl_var */
    foreach($element['#value'] as &$tpl_var) {
      $formatter = $tpl_var->getFormatter();
      if (empty($formatter))
        continue;

      $field_name = $tpl_var->getField();
      if (empty($field_name))
        continue;

      /** @var NodeTypeSettings $settings */
      $settings = $element['#nodeletter_settings'];
      $field_definition = self::getFieldDefinition($settings->getNodeTypeId(),
        $field_name);
      list($options, $default_id) = self::getFormatterOptions($field_definition);

      if ( ! in_array($formatter, array_keys($options)) ) {
        $tpl_var->setFormatter($default_id);
        $form_state->setError($element, 'bad formatter!');
      }

    }

  }

  public static function preRenderTemplateVariablesElement($element) {

    $element['#attributes']['id'] = $element['#id'] . '--wrapper';
    $element['#theme_wrappers'][] = 'fieldset';
    $element['#attributes']['class'][] = 'fieldgroup';
    $element['#attributes']['class'][] = 'form-composite';

    return $element;
  }















  private static $_field_definitions = [];
  private static $_node_type_field_options = [];
  private static $_field_type_definitions = [];
  private static $_formatter_options = [];


  /**
   * @param string $node_type_id
   * @return FieldDefinitionInterface[]
   */
  protected static function getFieldDefinitions( $node_type_id ) {

    if (!isset(self::$_field_definitions[$node_type_id])) {
      /** @var EntityFieldManagerInterface $entityFieldManager */
      $entityFieldManager = \Drupal::service('entity_field.manager');
      self::$_field_definitions[$node_type_id] =
        $entityFieldManager->getFieldDefinitions('node', $node_type_id);
    }
    return self::$_field_definitions[$node_type_id];
  }

  /**
   * @param string $node_type_id
   * @param string $field_name
   * @return FieldDefinitionInterface
   * @throws \Exception If field is not found on definition of node_type.
   */
  protected static function getFieldDefinition( $node_type_id, $field_name ) {
    $definitions = self::getFieldDefinitions($node_type_id);
    if (isset($definitions[$field_name]))
      return $definitions[$field_name];
    else
      throw new \Exception("Illegal parameter \$field_name");
  }

  protected static function getFieldTypeDefinition(
    FieldDefinitionInterface $field_definition ) {

    $field_type = $field_definition->getType();
    if (!isset(self::$_field_type_definitions[$field_type])) {
      /** @var FieldTypePluginManagerInterface $fieldTypePluginManager */
      $fieldTypePluginManager = \Drupal::service(
        'plugin.manager.field.field_type');
      self::$_field_type_definitions[$field_type] =
        $fieldTypePluginManager->getDefinition($field_definition->getType());

    }
    return self::$_field_type_definitions[$field_type];
  }


  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @param string $formatter_id
   * @param array $formatter_settings
   * @return \Drupal\Core\Field\FormatterInterface|null
   */
  protected static function getFormatter(
    FieldDefinitionInterface $field_definition, $formatter_id,
    array $formatter_settings = [] ) {

    /** @var FormatterPluginManager $formatterManager */
    $formatterManager = \Drupal::service(
      'plugin.manager.field.formatter');
    $formatter = $formatterManager->getInstance([
      'field_definition' => $field_definition,
      'view_mode' => 'nodeletter',
      'configuration' => [
        'label' => 'hidden',
        'type' => $formatter_id,
        'settings' => $formatter_settings,
      ],
    ]);
    return $formatter;
  }


  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   * @return array [0] => array of formatters, [1] => default formatter
   */
  protected static function getFormatterOptions(
    FieldDefinitionInterface $field_definition) {

    $field_type = $field_definition->getType();
    if (!isset(self::$_formatter_options[$field_type])) {

      $field_type_definition = self::getFieldTypeDefinition($field_definition);
      $default_formatter = isset($field_type_definition['default_formatter']) ?
        $field_type_definition['default_formatter'] : NULL;

      /** @var FormatterPluginManager $fieldTypePluginManager */
      $formatterManager = \Drupal::service(
        'plugin.manager.field.formatter');
      $options = $formatterManager->getOptions($field_type);

      // if default formatter is not defined and if a single formatter is provided
      // then fall back to that formatter
      if (!$default_formatter && count($options) == 1) {
        $default_formatter = current(array_keys($options));
      }

      if (!$field_definition->isDisplayConfigurable('view')) {
        self::$_formatter_options[$field_type] = [
          [$default_formatter => strval($options[$default_formatter])],
          $default_formatter
        ];
      } else {

        $formatter_options = [];
        foreach ($options as $formatter_id => $formatter_label) {
          /** @var FormatterInterface $formatter_plugin_class */
          $formatter_plugin_class = DefaultFactory::getPluginClass(
            $formatter_id, $formatterManager->getDefinition($formatter_id));
          if ($formatter_plugin_class::isApplicable($field_definition)) {
            $formatter_options[$formatter_id] = strval($formatter_label);
          }
        }
        self::$_formatter_options[$field_type] = [
          $formatter_options,
          $default_formatter
        ];
      }
    }
    return self::$_formatter_options[$field_type];
  }

  /**
   * @param string $node_type_id
   * @return array
   */
  protected static function getNodeTypeFieldOptions( $node_type_id ) {

    if (!isset(self::$_node_type_field_options[$node_type_id])) {
      $field_definitions = self::getFieldDefinitions($node_type_id);
      $field_options = [
        '' => '',
      ];
      foreach ($field_definitions as $def) {
        $lbl = strval($def->getLabel());
        if (!empty($lbl)) // only strange internal fields do not have a label set.
        {
          $field_options[$def->getName()] = $lbl;
        }
      }
      self::$_node_type_field_options[$node_type_id] = $field_options;
    }
    return self::$_node_type_field_options[$node_type_id];
  }



  /**
   * @param array $settings_form
   * @param string $field_name
   * @param array $array_parents
   */
  public static function cleanFormatterSettingsForm( array $settings_form,
                                                     $field_name,
                                                     array $array_parents ) {

    foreach($settings_form as $setting_name => &$setting_element) {
      /** var array $settings_element */
      if (isset($setting_element['#states'])) {

        // Repair states conditions since all/most formatter settings forms
        // expect them to be rendered on ordinary EntityDisplayForms and
        // hand-craft the states condition selectors by the form structure
        // of EntityDisplayForm which is not applicable for the use in this
        // very form structure.
        // ...yes: It's a hackish workaround - but it's Drupal, isn't it??!

        $EntityDisplayForm_selector =
          "select[name=\"fields[$field_name][settings_edit_form][settings]";
        $NodeletterSettingsForm_selector = "select[name=\"template_variables[][formatter_settings]";

        foreach($setting_element['#states'] as $state => $conditions) {
          foreach(array_keys($conditions) as $selector) {
            if (strpos($selector, $EntityDisplayForm_selector) === 0) {
              $replacement_selector = $NodeletterSettingsForm_selector;
              $replacement_selector .= substr($selector, strlen($EntityDisplayForm_selector));
              $setting_element['#states'][$state][$replacement_selector] =
                $setting_element['#states'][$state][$selector];
              unset($setting_element['#states'][$state][$selector]);
            }
          }
        }
      }
    }
  }

}
