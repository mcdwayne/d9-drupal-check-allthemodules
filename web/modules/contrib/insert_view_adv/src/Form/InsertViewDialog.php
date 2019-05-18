<?php

namespace Drupal\insert_view_adv\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\filter\Entity\FilterFormat;
use Drupal\views\Views;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InsertViewDialog
 *
 * @package Drupal\insert_view_adv\Form
 */
class InsertViewDialog extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * InsertViewDialog constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\FormBase|\Drupal\insert_view_adv\Form\InsertViewDialog
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'insert_view_adv_dialog';
  }

  /**
   * Get the field info to have some labels and description for argument
   * in dialog form
   *
   * @param $argument
   *
   * @return array
   */
  private static function getFieldInfo($argument) {
    $info = [];
    $bundle_info = [];
    $argument = array_shift($argument);
    if (!empty($argument['table'])) {
      $keys = explode('__', $argument['table']);
      if (!empty($keys)) {
        $info = FieldStorageConfig::loadByName($keys[0], $keys[1]);
        // if it is entity reference field try to get the target type and selector settings
        if ($info && $info->getType() == 'entity_reference') {
          $bundles = $info->getBundles();
          $bundles_machine_names = array_keys($bundles);
          $bundle_info = FieldConfig::loadByName($keys[0], $bundles_machine_names[0], $keys[1]);
        }
      }
    }
    return ['info' => $info, 'bundle_info' => $bundle_info];
  }

  /**
   * Ajax callback to get the view arguments
   *
   * @param $form
   * @param FormStateInterface $form_state
   *
   * @return mixed
   */
  public function getArguments(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $arguments = 0;
    $num_args = $form_state->get('num_args');
    if (!empty($values['inserted_view_adv'])) {
      $current_view = $values['inserted_view_adv'];
      if (!empty($form['#view_arguments'][$current_view])) {
        $arguments = count($form['#view_arguments'][$current_view]);
      }
    }
    $num_args += $arguments;
    $form_state->set('num_args', $num_args);
    $form_state->setRebuild(TRUE);
    return $form['arguments'];
  }

  /**
   * Create the argument field
   *
   * @param $form
   * @param $form_state
   * @param $view_block
   * @param $num
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function renderArgument(&$form, $form_state, $view_block, $num) {
    if (!empty($form['#view_arguments'][$view_block][$num])) {
      $argument = $form['#view_arguments'][$view_block][$num];
      // get field info
      $info = InsertViewDialog::getFieldInfo($argument);
      $field_info = $info['info'];
      $bundle_info = $info['bundle_info'];
      if ($field_info) {
        $form['arguments']['argument'][$num] = [
          '#type' => ($field_info->getType() == 'entity_reference') ? 'entity_autocomplete' : 'textfield',
          '#title' => empty($bundle_info) ? $field_info->getLabel() : $bundle_info->getLabel(),
          '#description' => empty($bundle_info) ? $field_info->getDescription() : $bundle_info->getDescription(),
          '#default_value' => $this->getUserInput($form_state, 'arguments')[$num],
        ];
        // If it is entity reference and some more settings.
        if (($field_info->getType() == 'entity_reference')) {
          $info_settings = $field_info->getSettings();
          $bundle_settings = $bundle_info->getSettings();
          $form['arguments']['argument'][$num]['#target_type'] = $info_settings['target_type'];
          $form['arguments']['argument'][$num]['#selection_handler'] = $bundle_settings['handler'];
          if (!empty($bundle_settings['handler_settings'])) {
            $form['arguments']['argument'][$num]['#selection_settings'] = $bundle_settings['handler_settings'];
          }
          // Default value could be only entity, let's load one
          $entity_storage = $this->entityTypeManager
            ->getStorage($info_settings['target_type']);
          $entity = $entity_storage->load($form['arguments']['argument'][$num]['#default_value']);
          $form['arguments']['argument'][$num]['#default_value'] = $entity;
        }
      }
      else {
        $argument_definition = reset($argument);
        $property = $argument_definition['id'];
        $entity_definitions = $this->entityTypeManager->getDefinitions();
        $not_found = TRUE;
        if (!empty($argument_definition['relationship']) && $argument_definition['relationship'] == 'none') {
          foreach ($entity_definitions as $entity_type_id => $definition) {
            if (!$definition->getBaseTable()) {
              continue;
            }
            $tables = [
              $definition->getBaseTable(),
              $definition->getDataTable()
            ];
            if (in_array($argument_definition['table'], $tables)) {
              try {
                $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
                if ($definition->getKey('id') == $property || (!empty($base_fields[$property]) && $base_fields[$property]->getType() == 'entity_reference')) {
                  $field_info = $base_fields[$property];
                  $entity_storage = $this->entityTypeManager
                    ->getStorage($entity_type_id);
                  $entity = $entity_storage->load($this->getUserInput($form_state, 'arguments')[$num]);
                  $form['arguments']['argument'][$num] = [
                    '#type' => 'entity_autocomplete',
                    '#title' => $field_info->getLabel(),
                    '#description' => $field_info->getDescription(),
                    '#target_type' => $entity_type_id,
                    '#default_value' => $entity,
                  ];
                  $not_found = FALSE;
                }
              } catch (\LogicException $e) {
                continue;
              }
            }
          }
        }
        if ($not_found) {
          $form['arguments']['argument'][$num] = [
            '#type' => 'textfield',
            '#title' => $property['field'],
            '#default_value' => $this->getUserInput($form_state, 'arguments')[$num],
          ];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // Add AJAX support.
    $form['#prefix'] = '<div id="insert-view-dialog-form">';
    $form['#suffix'] = '</div>';

    // Ensure relevant dialog libraries are attached.
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $filter_settings = $filter_format->filters('insert_view_adv')->settings;
    $allowed_views = array_filter($filter_settings['allowed_views']);
    $views_list = Views::getEnabledViews();
    $arguments = [];
    $options = ['' => $this->t('-- Choose the view --')];
    foreach ($views_list as $machine_name => $view) {
      foreach ($view->get('display') as $display) {
        // Get display name with the view label
        $key = $machine_name . '=' . $display['id'];
        if (!empty($allowed_views) && empty($allowed_views[$key])) {
          continue;
        }
        if (empty($options[$machine_name])) {
          $options[$machine_name] = [];
        }
        $options[$machine_name][$key] = $view->label() . ' ' . $display['display_title'];
        if (empty($display['display_options']['arguments']) && $display['id'] != 'default') {
          $master_display = $view->getDisplay('default');
          if (!empty($master_display['display_options']['arguments'])) {
            $display['display_options']['arguments'] = $master_display['display_options']['arguments'];
          }
        }
        // Get arguments.
        if (!empty($display['display_options']['arguments']) && $display['display_options']['arguments']) {
          foreach ($display['display_options']['arguments'] as $field => $item) {
            $arguments[$machine_name . '=' . $display['id']][] = [$field => $item];
          }
        }
      }
    }
    // pass the arguments to form so we have access to arguments in ajax call
    $form['#view_arguments'] = $arguments;

    // check if the widget edit form is called
    $current_view = $this->getUserInput($form_state, 'inserted_view_adv');
    if ($current_view == '') {
      // try to get the value from submitted
      $values = $form_state->getUserInput();
      if (!empty($values['inserted_view_adv'])) {
        $current_view = $values['inserted_view_adv'];
      }
    }

    // select box with the list of views blocks grouped by view
    $form['inserted_view_adv'] = [
      '#type' => 'select',
      '#title' => $this->t('View to insert'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $current_view,
      '#ajax' => [
        // trigger ajax call on change to get the arguments of the views block
        'callback' => 'Drupal\insert_view_adv\Form\InsertViewDialog::getArguments',
        'event' => 'change',
        'wrapper' => 'arguments',
      ],
    ];

    // Create a settings form from the existing video formatter.
    $form['arguments'] = [];
    $form['arguments']['#type'] = 'fieldset';
    $form['arguments']['#prefix'] = '<div id="arguments">';
    $form['arguments']['#suffix'] = '</div>';
    $form['arguments']['#title'] = $this->t('Arguments');
    $form['arguments']['argument'] = ['#tree' => TRUE];

    if ($current_view) {
      $argument_field = count($form['#view_arguments'][$current_view]);
      $form_state->set('num_args', $argument_field);
    }
    else {
      $argument_field = $form_state->get('num_args');
    }
    if (empty($argument_field)) {
      $form_state->set('num_args', 0);
    }
    for ($i = 0; $i < $argument_field; $i++) {
      $this->renderArgument($form, $form_state, $current_view, $i);
    }

    // if there are no arguments show the message
    if (count($form['arguments']['argument']) == 1) {
      $form['arguments']['argument']['#markup'] = $this->t('No arguments provided');
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'click',
        'wrapper' => 'insert-view-dialog-form',
      ],
    ];
    return $form;
  }

  /**
   * Get a value from the widget in the WYSIWYG.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state to extract values from.
   * @param string $key
   *   The key to get from the selected WYSIWYG element.
   *
   * @return string
   *   The default value.
   */
  protected function getUserInput(FormStateInterface $form_state, $key) {
    return isset($form_state->getUserInput()['editor_object'][$key]) ? $form_state->getUserInput()['editor_object'][$key] : '';
  }

  /**
   * Get the values from the form required for the client.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state from the dialog submission.
   *
   * @return array
   *   An array of values sent to the client for use in the WYSIWYG.
   */
  protected function getClientValues(FormStateInterface $form_state) {
    $view = $form_state->getValue('inserted_view_adv');
    $arguments = $form_state->getValue('argument');
    return [
      'inserted_view_adv' => $view,
      'arguments' => $arguments,
    ];
  }


  /**
   * An AJAX submit callback to validate the WYSIWYG modal.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->getErrors()) {
      // provide the commands for the widget
      $response->addCommand(new EditorDialogSave($this->getClientValues($form_state)));
      $response->addCommand(new CloseModalDialogCommand());
    }
    else {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand(NULL, $form));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The AJAX commands were already added in the AJAX callback. Do nothing in
    // the submit form.
  }

}
