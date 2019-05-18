<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Form\NodeTypeForm.
 */

namespace Drupal\nodeletter\Form;


use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\nodeletter\Entity\TemplateVariableSetting;
use Drupal\nodeletter\MailchimpApiTrait;
use Drupal\nodeletter\NodeletterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeTypeForm extends FormBase {

  use MailchimpApiTrait;

  /** @var NodeletterService */
  protected $nodeletterService;

  /** @var EntityFieldManagerInterface */
  protected $entityFieldManager;

  /** @var  FieldTypePluginManagerInterface */
  protected $fieldTypeManager;

  /** @var FormatterPluginManager */
  protected $fieldFormatterManager;


  private static $_node_type_field_options = [];


  public function __construct(NodeletterService $nodeletter_service,
                              EntityFieldManagerInterface $entityFieldManager,
                              FieldTypePluginManagerInterface $fieldTypeManager,
                              FormatterPluginManager $formatterManager) {
    $this->nodeletterService = $nodeletter_service;
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypeManager;
    $this->fieldFormatterManager = $formatterManager;
  }

  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('nodeletter'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.formatter')
    );
  }


  public function getFormId() {
    return "nodeletter_node_type_settings";
  }

  public function buildForm(array $form, FormStateInterface $form_state, NodeType $node_type = NULL) {

    if ( ! $node_type) {
      $node_type = $this->getNodeType($form_state);
    }
    $settings = $this->nodeletterService->getNodeletterSettings($node_type);
    $sender = $this->nodeletterService->getNodeletterSender($node_type);


    // TODO: Refactor for to use NodeletterSender plugins instead of hardcoded mailchimp stuff.

    $mailchimp_template_options = [];
    $mailchimp_list_options = [];

    if (!$this->isMailchimpUsable()) {
      $msg = 'MailChimp setup not functional. Please check the ' .
        '<a href=":mailchimp_settings_url">MailChimp module settings</a>!';
      $msg_vars = [
        ':mailchimp_settings_url' => Url::fromRoute('mailchimp.admin')
          ->toString()
      ];
      drupal_set_message(t($msg, $msg_vars), 'warning');
    }
    else {
      $tpls = $sender->getTemplates();
      foreach ($tpls as $tpl) {
        $mailchimp_template_options[$tpl->getId()] = $tpl->getLabel();
      }

      $recipient_lists = $sender->getRecipientLists();
      foreach($recipient_lists as $list) {
        $mailchimp_list_options[$list->getId()] = $list->getLabel();
      }
    }


    $form['template_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Mailchimp template'),
      '#options' => $mailchimp_template_options,
      '#required' => TRUE,
      '#default_value' => $settings->getTemplateName(),
    ];

    $form['list_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Mailchimp List'),
      '#options' => $mailchimp_list_options,
      '#required' => TRUE,
      '#default_value' => $settings->getListID(),
    ];

//    if (!empty($submitted_values)) {
//      $form['template_name']['#default_value'] =
//        empty($submitted_values['template_name']) ? NULL : $submitted_values['template_name'];
//    }
//    else {
//      $form['template_name']['#default_value'] =
//        empty($settings->getTemplateName()) ? NULL : $settings->getTemplateName();
//    }


    $form['template_variables'] = [
      '#type' => 'template_variables',
      '#title' => $this->t('Template variables'),
      '#nodeletter_settings' => $settings,
    ];

//    $form['template_variables'] = [
//      '#nodeletter_settings' => $settings,
//      '#process' => ['::processTemplateVariableTable'],
//    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#op' => 'save',
    ];

    return $form;
  }

//
//  /**
//   * @param $element array
//   * @param $form_state FormStateInterface
//   * @param $form array
//   * @throws \Exception
//   */
//  public function processTemplateVariableTable(&$element, &$form_state, &$form) {
//
//    $settings = $element['#nodeletter_settings'];
//
//    $tpl_var_header = [
//      $this->t('Variable name'),
//      $this->t('Node field'),
//      [
//        'data' => $this->t('Field formatter'),
//        'colspan' => 2,
//      ],
//    ];
//
//    $tpl_var_rows = [];
//
//    if ($form_state->isProcessingInput()) {
//
//      $tpl_var_values = $form_state->getValue($element['#array_parents'], []);
//      foreach ($tpl_var_values as $row_index => $tpl_var) {
//        $tpl_var_rows[] = $this->buildTemplateVariableTableRow(
//          $form, $form_state, $row_index,
//          $tpl_var['name'],
//          $tpl_var['field'],
//          isset($tpl_var['formatter']) ? $tpl_var['formatter'] : '',
//          isset($tpl_var['formatter_settings']) ? $tpl_var['formatter_settings'] : []
//        );
//      }
//
//      $add_empty_row = FALSE;
//      if (count($tpl_var_values) == 0) {
//        $add_empty_row = TRUE;
//      } else {
//        $last_tpl_var_value = $tpl_var_values[ count($tpl_var_values)-1 ];
//        if ( ! empty($last_tpl_var_value['name']) || ! empty($last_tpl_var_value['field']))
//          $add_empty_row = TRUE;
//      }
//
//    }
//    else {
//
//      if (!empty($settings->getTemplateVariables())) {
//        foreach ($settings->getTemplateVariables() as $tpl_var) {
//          $tpl_var_rows[] = $this->buildTemplateVariableTableRow(
//            $form, $form_state,
//            $tpl_var->getWeight(),
//            $tpl_var->getVariableName(),
//            $tpl_var->getField(),
//            $tpl_var->getFormatter(),
//            $tpl_var->getFormatterSettings()
//          );
//        }
//      }
//
//      $add_empty_row = TRUE;
//    }
//
//    if ($add_empty_row) {
//      $empty_row = $this->buildTemplateVariableTableRow(
//        $form, $form_state, $settings->getTemplateVariablesMaxWeight()
//      );
//      $tpl_var_rows[] = $empty_row;
//    }
//
//
//    // TODO: Add tabledrag to the template variables table.
//
//    $tpl_table_element = [
//      '#type' => 'table',
//      '#id' => $this->getAjaxWrapperId($form_state),
//      '#header' => $tpl_var_header,
//      '#tree' => TRUE,
//    ];
//
//    return array_merge($element, $tpl_table_element, $tpl_var_rows);
//  }


  public function ajax_rebuild(array $form, FormStateInterface $form_state) {
    return $form['template_variables'];
  }

  public function ajax_submit($form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    $op = $trigger_element['#op'];

    // TODO: find a way to use #limit_validation_errors in submit elements
    // Without breaking the FormState !!!
    // Currently FormState looses all values but the ones listed in
    // #limit_validation_errors on submit.
    // On the other hand without the #limit_validation_errors option the
    // form editing lacks user experience.

    if (isset($trigger_element['#formatter_settings'])) {
      $node_type_id = $this->getNodeTypeId($form_state);
      $row_index = $trigger_element['#formatter_settings']['row_id'];

      switch($op) {
        case 'edit':
          $form_state->set('formatter_settings_edit', "$node_type_id-$row_index");
          break;
        case 'submit':

          if ($form_state->get('formatter_settings_edit') != "$node_type_id-$row_index") {
            drupal_set_message('Error processing submission: from state invalid', 'error');
            break;
          }

          $form_state->cleanValues();
          $form_state->set('formatter_settings_edit', NULL);
          break;
        case 'cancel':
        default:
          $form_state->set('formatter_settings_edit', NULL);
          break;
      }
    } else {
      throw new \Exception("Invalid request");
    }

    $form_state->setRebuild();
  }



  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    $nodeletter_settings = $this->nodeletterService->getNodeletterSettings($this->getNodeType($form_state), 'mailchimp');
    $nodeletter_settings
      ->setTemplateName($values['template_name'])
      ->setTemplateVariables($values['template_variables'])
      ->setListID($values['list_id'])
      ->save();

    drupal_set_message($this->t(
      "Nodeletter setup saved for %node_type",
      ['%node_type' => $this->getNodeType($form_state)->label()]
    ));

    $form_state->setRebuild();
  }






//
//
//  protected function buildTemplateVariableTableRow(
//    array $form, FormStateInterface $form_state,
//    $row_index, $var_name = NULL, $field_name = NULL, $formatter_id = NULL, $formatter_settings = []) {
//
//    $node_type_id = self::getNodeTypeId($form_state);
//
//    $name_element = [
//      '#type' => 'textfield',
//      '#default_value' => $var_name,
//    ];
//
//    $field_element = [
//      '#type' => 'select',
//      '#options' => self::getNodeTypeFieldOptions($node_type_id),
//      '#default_value' => $field_name,
//      '#ajax' => [
//        'callback' => '::ajax_rebuild',
//        'wrapper' => self::getAjaxWrapperId($form_state)
//      ],
//      '#field_select' => [
//        'row_id' => $row_index,
//      ],
//    ];
//
//    if ($field_name) {
//      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $node_type_id);
//      if (empty($field_definitions[$field_name])) {
//        throw new \Exception("Illegal parameter \$field_name");
//      }
//      $field_definition = $field_definitions[$field_name];
//
//      if ($form_state->get('formatter_settings_edit') == "$node_type_id-$row_index") {
//
//        $formatter_element = [
//          '#type' => 'value',
//          '#value' => $formatter_id,
//        ];
//        $formatter_settings_element = $this->buildFieldFormatterSettingsElement(
//          $form, $form_state, $row_index, $field_definition,
//          $formatter_id, $formatter_settings
//        );
//        $formatter_summary_element = [];
//
//      } else {
//
//        $formatter_element = $this->buildFieldFormatterElement(
//          $form_state, $row_index, $field_definition,
//          $formatter_id);
//        $formatter_settings_element = [
//          '#type' => 'value',
//          '#value' => $formatter_settings,
//        ];
//        $formatter_summary_element = $this->buildFieldFormatterSummaryElement(
//          $form_state, $row_index, $field_definition,
//          $formatter_id, $formatter_settings
//        );
//
//      }
//
//    }
//    else {
//      $formatter_element = [];
//      $formatter_settings_element = [];
//      $formatter_summary_element = [];
//    }
//
//    // TODO: implement support for formatter third-party settings.
//
//    return [
//      'name' => $name_element,
//      'field' => $field_element,
//      'formatter' => $formatter_element,
//      'formatter_settings' => $formatter_settings_element,
//      'formatter_summary' => $formatter_summary_element,
//    ];
//  }
//
//
//  protected function buildFieldFormatterElement(
//    FormStateInterface $form_state, $row_index,
//    FieldDefinitionInterface $field_definition = NULL,
//    $formatter_id = NULL) {
//
//    if (empty($field_definition)) {
//      return [];
//    }
//
//
//    list($options, $default_id) = $this->getFieldFormatterOptions($field_definition);
//    $formatter_id = in_array($formatter_id, array_keys($options)) ? $formatter_id : $default_id;
//    $configurable = count($options) > 1;
//    $formatter_element = [
//      '#type' => 'select',
//      '#options' => $options,
//      '#default_value' => $formatter_id,
//      '#disabled' => !$configurable,
//      '#ajax' => [
//        'callback' => '::ajax_rebuild',
//        'wrapper' => $this->getAjaxWrapperId($form_state)
//      ],
//      '#formatter_select' => [
//        'row_id' => $row_index,
//      ],
//    ];
//
//    return $formatter_element;
//  }
//
//
//  protected function buildFieldFormatterSettingsElement(
//    array $form, FormStateInterface $form_state,
//    $row_index, FieldDefinitionInterface $field_definition = NULL,
//    $formatter_id = NULL, $formatter_settings = []) {
//
//    if ($formatter_id == "geocoder_geocode_formatter") // geocode formatter is broken.
//    {
//      // TODO: remove me once geocode formatter is fixed.
//      return [];
//    }
//
//    if (empty($field_definition)) {
//      return [];
//    }
//
//    $formatter = $this->fieldFormatterManager->getInstance([
//      'field_definition' => $field_definition,
//      'view_mode' => 'nodeletter',
//      'configuration' => [
//        'label' => 'hidden',
//        'type' => $formatter_id,
//        'settings' => is_array($formatter_settings) ? $formatter_settings : [],
//      ],
//    ]);
//    if (empty($formatter)) {
//      return [];
//    }
//
//    // Base button element for the various plugin settings actions.
//    $base_button = [
//      '#type' => 'submit',
//      '#submit' => ['::ajax_submit'],
//      '#ajax' => [
//        'callback' => '::ajax_rebuild',
//        'wrapper' => $this->getAjaxWrapperId($form_state),
//        'effect' => 'fade',
//      ],
//      '#formatter_settings' => [
//        'row_id' => $row_index,
//      ],
//    ];
//
//    $settings_element = $formatter->settingsForm($form, $form_state);
//
//    foreach($settings_element as $setting_name => &$setting_element) {
//      /** var array $settings_element */
//      if (isset($setting_element['#states'])) {
//
//        // Repair states conditions since all/most formatter settings forms
//        // expect them to be rendered on ordinary EntityDisplayForms and
//        // hand-craft the states condition selectors by the form structure
//        // of EntityDisplayForm which is not applicable for the use in this
//        // very form structure.
//        // ...yes: It's a hackish workaround - but it's Drupal, isn't it??!
//
//        $EntityDisplayForm_selector =
//          "select[name=\"fields[{$field_definition->getName()}][settings_edit_form][settings]";
//        $NodeletterSettingsForm_selector = "select[name=\"template_variables[$row_index][formatter_settings]";
//
//        foreach($setting_element['#states'] as $state => $conditions) {
//          foreach(array_keys($conditions) as $selector) {
//            if (strpos($selector, $EntityDisplayForm_selector) === 0) {
//              $replacement_selector = $NodeletterSettingsForm_selector;
//              $replacement_selector .= substr($selector, strlen($EntityDisplayForm_selector));
//              $setting_element['#states'][$state][$replacement_selector] =
//                $setting_element['#states'][$state][$selector];
//              unset($setting_element['#states'][$state][$selector]);
//            }
//          }
//        }
//      }
//    }
//
//
//
//
//
//
//    $settings_element['actions'] = [
//      '#type' => 'actions',
//      'submit' => $base_button + array(
//          '#button_type' => 'primary',
//          '#value' => $this->t('Submit'),
//          '#op' => 'submit',
////          '#limit_validation_errors' => [
////            ['template_variables',$row_index]
////          ],
//        ),
//      'cancel' => $base_button + array(
//          '#value' => $this->t('Cancel'),
//          '#op' => 'cancel',
//          // Do not check errors for the 'Cancel' button, but make sure we
//          // get the value of the 'plugin type' select.
////          '#limit_validation_errors' => [
////            ['template_variables',$row_index]
////          ],
//        ),
//    ];
//
//    return $settings_element;
//  }
//
//
//  protected function buildFieldFormatterSummaryElement(
//    FormStateInterface $form_state, $row_index,
//    FieldDefinitionInterface $field_definition = NULL,
//    $formatter_id = NULL, $formatter_settings = []) {
//
//    if (empty($field_definition)) {
//      return [];
//    }
//
//    $formatter = $this->fieldFormatterManager->getInstance([
//      'field_definition' => $field_definition,
//      'view_mode' => 'nodeletter',
//      'configuration' => [
//        'label' => 'hidden',
//        'type' => $formatter_id,
//        'settings' => is_array($formatter_settings) ? $formatter_settings : [],
//      ],
//    ]);
//
//    $summary = $formatter->settingsSummary();
//    $settings_summary = [
//      '#type' => 'container',
//      'summary' => [
//        '#type' => 'inline_template',
//        '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
//        '#context' => ['summary' => $summary],
//        ],
//      'edit' => [
//        '#type' => 'image_button',
//        '#src' => 'core/misc/icons/787878/cog.svg',
////        '#submit' => ['::ajax_submit'],
////        '#limit_validation_errors' => [
////          ['template_variables',$row_index]
////          ],
////        '#executes_submit_callback' => FALSE,
//        '#ajax' => [
//          'callback' => '\Drupal\nodeletter\Form\NodeTypeForm::ajax_rebuild',
//          'wrapper' => $this->getAjaxWrapperId($form_state),
//          'effect' => 'fade',
//          ],
//        '#formatter_settings' => [
//          'row_id' => $row_index,
//          ],
//        '#attributes' => [
//          'class' => ['field-plugin-settings-edit'],
//          'alt' => $this->t('Edit')],
//        '#op' => 'edit',
//        ],
//    ];
//
//    return $settings_summary;
//
//  }


  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\node\Entity\NodeTypeInterface
   * @throws \Exception
   */
  protected static function getNodeType(FormStateInterface $form_state) {

    $args = $form_state->getBuildInfo()['args'];
    if (empty($args) || ! $args[0] instanceof NodeType) {
      throw new \Exception("Invalid Form BuildInfo argument");
    }
    return $args[0];
  }

  protected static function getNodeTypeId(FormStateInterface $form_state) {
    return self::getNodeType($form_state)->id();
  }


  protected static function getAjaxWrapperId(FormStateInterface $form_state) {

    $node_type_id = self::getNodeTypeId($form_state);
    if ($node_type_id)
      return "$node_type_id-nodeletter-template-variables";
    else
      return "bam-oida!";
  }

//
//  /**
//   * @param string $node_type_id
//   * @return array
//   */
//  protected function getNodeTypeFieldOptions( $node_type_id) {
//    if (empty(self::$_node_type_field_options)) {
//      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $node_type_id);
//      $field_options = [
//        '' => '',
//      ];
//      foreach ($field_definitions as $def) {
//        $lbl = strval($def->getLabel());
//        if (!empty($lbl)) // only strange internal fields do not have a label set.
//        {
//          $field_options[$def->getName()] = $lbl;
//        }
//      }
//      self::$_node_type_field_options = $field_options;
//    }
//    return self::$_node_type_field_options;
//  }
//
//  /**
//   * @param \Drupal\core\Field\FieldDefinitionInterface $field_definition
//   * @return array
//   * @throws \Exception
//   */
//  protected function getFieldFormatterOptions(FieldDefinitionInterface $field_definition) {
//    $field_type_definition = $this->fieldTypeManager->getDefinition($field_definition->getType());
//    $default_formatter_id = isset($field_type_definition['default_formatter']) ? $field_type_definition['default_formatter'] : NULL;
//    $options = $this->fieldFormatterManager->getOptions($field_definition->getType());
//    if (!$field_definition->isDisplayConfigurable('view')) {
//      return [
//        [$default_formatter_id => strval($options[$default_formatter_id])],
//        $default_formatter_id
//      ];
//    }
//    $formatter_options = [];
//    foreach ($options as $formatter_id => $formatter_label) {
//      /** @var FormatterInterface $formatter_plugin_class */
//      $formatter_plugin_class = DefaultFactory::getPluginClass($formatter_id, $this->fieldFormatterManager->getDefinition($formatter_id));
//      if ($formatter_plugin_class::isApplicable($field_definition)) {
//        $formatter_options[$formatter_id] = strval($formatter_label);
//      }
//    }
//    return [$formatter_options, $default_formatter_id];
//  }


}
