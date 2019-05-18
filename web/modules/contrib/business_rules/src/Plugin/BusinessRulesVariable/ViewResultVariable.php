<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\views\Views;

/**
 * Class ViewResultVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVariable
 *
 * @BusinessRulesVariable(
 *   id = "view_result_variable",
 *   label = @Translation("View result variable"),
 *   group = @Translation("Views"),
 *   description = @Translation("Populate this variable with a view result.
 *   This variable can only be on Actions type: "),
 * )
 */
class ViewResultVariable extends BusinessRulesVariablePlugin {

  /**
   * The EntityTypeBundleInfo.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

  /**
   * The EntityFieldManager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'view_result_variable', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityFieldManager = $this->util->container->get('entity_field.manager');
    $this->bundleInfo         = $this->util->container->get('entity_type.bundle.info');
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['view'] = [
      '#type'          => 'select',
      '#title'         => t('View to execute. View name : Display mode id : Display mode title.'),
      '#options'       => $this->util->getViewsOptions(),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('view'),
      '#description'   => t("Select the view to get the results. When you use the view fields, it will always have the raw value."),
    ];

    $settings['arguments'] = [
      '#type'          => 'textarea',
      '#title'         => t('Arguments'),
      '#description'   => t('Any argument the view may need, one per line. Be aware of including them at same order as the CONTEXTUAL FILTERS configured in the view. You may use variables.'),
      '#default_value' => $item->getSettings('arguments'),
    ];

    $form['#attached']['library'][] = 'business_rules/style';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function changeDetails(Variable $variable, array &$row) {
    // Show a link to a modal window which all fields from the view.
    $content = $this->variableFields($variable);
    $keyvalue = $this->util->getKeyValueExpirable('view_result_variable');
    $keyvalue->set('variableFields.' . $variable->id(), $content);

    $details_link = Link::createFromRoute(t('Click here to see the view fields'),
      'business_rules.ajax.modal',
      [
        'method'     => 'nojs',
        'title'      => t('View fields'),
        'collection' => 'view_result_variable',
        'key'        => 'variableFields.' . $variable->id(),
      ],
      [
        'attributes' => [
          'class' => ['use-ajax'],
        ],
      ]
    )->toString();

    $row['description']['data']['#markup'] .= '<br>' . $details_link;

  }

  /**
   * Display the view variable fields.
   *
   * @param \Drupal\business_rules\Entity\Variable $variable
   *   The variable entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   The AjaxResponse or the render array.
   */
  public function variableFields(Variable $variable) {

    // Get settings.
    $defined_view = $variable->getSettings('view');

    // Process settings.
    $defined_view = explode(':', $defined_view);
    $view_id      = $defined_view[0];
    $display      = $defined_view[1];

    // Get view fields.
    $view = Views::getView($view_id);
    $view->setDisplay($display);
    $view->preExecute();
    $view->build();
    $fields = $view->field;

    $header = [
      'variable' => t('Variable'),
      'field'    => t('Field'),
      'type'     => t('Type'),
    ];

    $rows = [];
    if (count($fields)) {
      foreach ($fields as $field_name => $field) {
        $field_id    = $field->field;
        $definition  = $field->definition;
        $entity_type = $definition['entity_type'];

        if ($field->getBaseId() == 'field') {

          // Need to check in all bundles if the field is available.
          $bundles    = $this->bundleInfo->getBundleInfo($entity_type);
          $bundles    = array_keys($bundles);
          $found      = FALSE;
          $idx_bundle = 0;
          while (!$found && $idx_bundle < count($bundles)) {
            $bundle = $bundles[$idx_bundle];

            // Now, with the bundle info, we can load the fields definitions.
            $fields_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
            $field_definition   = isset($fields_definitions[$field_name]) ? $fields_definitions[$field_name] : NULL;
            // If we are not in the correct bundle, lets try again.
            if (empty($field_definition)) {
              $idx_bundle++;
            }
            else {
              $field_type = $field_definition->getType();
              $found      = TRUE;
            }
          }

          $rows[] = [
            'variable' => ['data' => ['#markup' => '{{' . $variable->id() . '->' . $field_id . '}}']],
            'field'    => ['data' => ['#markup' => $field->label() ? $field->label() : $field->realField]],
            'type'     => ['data' => ['#markup' => $field_type]],
          ];
        }
      }
    }
    else {
      $rows[] = [
        'data'    => ['#markup' => t('This view has no fields.')],
        'colspan' => 3,
      ];
    }

    $content['help'] = [
      '#type'   => 'markup',
      '#markup' => t('Notice that as this items are arrays, you only can use this variable values on items inside an action type: "Loop through a view result variable".'),
    ];

    $content['variable_fields'] = [
      '#type'   => 'table',
      '#rows'   => $rows,
      '#header' => $header,
      '#sticky' => TRUE,
    ];

    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {
    // Get settings.
    $defined_view    = $variable->getSettings('view');
    $args            = $variable->getSettings('arguments');
    $event_variables = $event->getArgument('variables');

    // Process settings.
    $defined_view = explode(':', $defined_view);
    $view_id      = $defined_view[0];
    $display      = $defined_view[1];

    $args = explode(chr(10), $args);
    $args = array_map('trim', $args);
    $args = array_filter($args, 'strlen');

    // Process variables.
    foreach ($args as $key => $value) {
      $args[$key] = $this->processVariables($value, $event_variables);
    }

    // Execute view.
    $view = Views::getView($view_id);
    $view->setArguments($args);
    $view->setDisplay($display);
    $view->preExecute();
    $view->build();
    $fields = $view->field;

    $variableObject = NULL;
    if ($view->execute()) {
      $view_result = $view->result;
      $values      = [];
      /** @var \Drupal\views\ResultRow $resultRow */
      foreach ($view_result as $key => $resultRow) {
        /** @var \Drupal\views\Plugin\views\field\Field $field */
        foreach ($fields as $field) {
          $field_id                = $field->field;
          $values[$key][$field_id] = $field->getValue($resultRow);
        }
      }

      $variableObject = new VariableObject($variable->id(), $values, $variable->getType());
    }
    else {
      $this->util->logger->error('View %view could not be executed. Arguments: %args', [
        '%view' => $defined_view,
        '%args' => implode(', ', $args),
      ]);
    }

    return $variableObject;
  }

}
