<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class FillEntityVariableFields.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "fill_entity_variable_fields",
 *   label = @Translation("Set values to entity variable"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Set fields values to an entity variable."),
 *   isContextDependent = FALSE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = FALSE,
 * )
 */
class FillEntityVariableFields extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'fill_entity_variable_fields', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Add field and value.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function fieldValueSave(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\business_rules\Entity\Action $action */
    $field = $form_state->getValue('entity_field');
    $value = $form_state->getValue('field_value');

    $action              = $form_state->get('business_rules_item');
    $field_value         = is_array($action->getSettings('fields_values')) ? $action->getSettings('fields_values') : [];
    $field_value[$field] = [
      'entity_field' => $field,
      'field_value'  => $value,
    ];
    $action->setSetting('fields_values', $field_value);
    $action->save();

    \Drupal::request()->query->remove('destination');
    $form_state->setRedirect('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()], ['fragment' => 'field_value-' . $field]);
  }

  /**
   * Validate handler for field and value.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function formValidate(array $form, FormStateInterface $form_state) {
    if (!$form_state->getValue('entity_field')) {
      $form_state->setErrorByName('field', t('Select an field to add.'));
    }
    if (!$form_state->getValue('field_value')) {
      $form_state->setErrorByName('field_value', t('Fill a field value to add.'));
    }
  }

  /**
   * Remove one field/value's setting.
   *
   * @param string $action
   *   The action id.
   * @param string $field
   *   The field id.
   * @param string $method
   *   The method ajax|nojs.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The AjaxResponse or RedirectResponse object.
   */
  public static function removeFieldValue($action, $field, $method) {
    $action        = Action::load($action);
    $fields_values = $action->getSettings('fields_values');
    unset($fields_values[$field]);
    $action->setSetting('fields_values', $fields_values);
    $action->save();

    if ($method == 'ajax') {
      $response = new AjaxResponse();
      $response->addCommand(new RemoveCommand('#field_value-' . $field));

      return $response;
    }
    else {
      $url = new Url('entity.business_rules_action.edit_form', ['business_rules_action' => $action->id()]);

      return new RedirectResponse($url->toString());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $form['#attached']['library'][] = 'business_rules/style';
    $settings                       = [];

    if (!$item->isNew()) {
      $settings['variable'] = [
        '#type'          => 'select',
        '#title'         => t('Entity variable'),
        '#required'      => TRUE,
        '#description'   => t('Entity variable to be saved. Remember to create actions to fill the entity variable fields and execute them before save the entity.'),
        '#options'       => $this->getAvailableEmptyVariables($item),
        '#default_value' => empty($item->getSettings('variable')) ? '' : $item->getSettings('variable'),
      ];

      $settings['field_value_title'] = [
        '#type'  => 'item',
        '#title' => t('Fields and values for the variable'),
      ];

      $settings['field_value'] = [
        '#type'   => 'table',
        '#sticky' => TRUE,
        '#header' => [
          t('Field'),
          t('Value'),
          t('Operations'),
        ],
        '#empty'  => t('There are currently no values. Add one by selecting an option below.'),
      ];

      $fields_values = $item->getSettings('fields_values');
      $fields        = $this->util->getBundleFields($item->getTargetEntityType(), $item->getTargetBundle());

      $settings['fields_values'] = [
        '#type'  => 'value',
        '#value' => $fields_values,
      ];

      if (is_array($fields_values)) {
        foreach ($fields_values as $key => $value) {

          $settings['field_value'][$key]['#attributes'] = ['id' => 'field_value-' . $key];

          $settings['field_value'][$key]['entity_field'] = [
            'data' => [
              'label' => [
                '#plain_text' => $fields[$value['entity_field']],
              ],
            ],
          ];

          $settings['field_value'][$key]['field_value'] = [
            'data' => [
              'label' => [
                '#markup' => nl2br($value['field_value']),
              ],
            ],
          ];

          $links = [];

          $links['delete']                             = [
            'title' => t('Remove'),
            'url'   => Url::fromRoute('business_rules.plugins.action.fill_entity_variable_fields.remove_field', [
              'action' => $item->id(),
              'field'  => $key,
              'method' => 'nojs',
            ], [
              'attributes' => ['class' => ['use-ajax']],
            ]),
          ];
          $settings['field_value'][$key]['operations'] = [
            'data' => [
              '#type'  => 'operations',
              '#links' => $links,
            ],
          ];

        }
      }

      $settings['field_value']['new'] = [
        '#tree' => FALSE,
      ];

      $settings['field_value']['new']['entity_field'] = [
        'data'    => [
          'entity_field' => [
            '#type'          => 'select',
            '#title'         => t('Field'),
            '#title_display' => 'invisible',
            '#options'       => $fields,
            '#empty_option'  => t('Select the field'),
          ],
        ],
        '#prefix' => '<div class="field-value-new">',
        '#suffix' => '</div>',
      ];

      $settings['field_value']['new']['field_value'] = [
        '#type'        => 'textarea',
        '#rows'        => 1,
        '#description' => t('The value to be set on the field. For a multi-valor field (cardinality > 1) type one value per line starting by pipeline (|) as the example:
          <br>|Value 1
          <br>|Value 2
          <br>|Value 3'),
      ];

      $settings['field_value']['new']['add'] = [
        '#type'     => 'submit',
        '#value'    => t('Add'),
        '#validate' => [get_class($this) . '::formValidate'],
        '#submit'   => [get_class($this) . '::fieldValueSave'],
      ];

      $form_state->set('business_rules_item', $item);
    }

    return $settings;
  }

  /**
   * Get the available empty variables for the context.
   *
   * @param \Drupal\business_rules\Entity\Action $item
   *   The action object.
   *
   * @return array
   *   Array of available variables.
   */
  public function getAvailableEmptyVariables(Action $item) {
    $variables = Variable::loadMultiple();
    $output    = [];

    /** @var \Drupal\business_rules\Entity\Variable $variable */
    foreach ($variables as $variable) {
      if ($item->getTargetEntityType() == $variable->getTargetEntityType() &&
        $item->getTargetBundle() == $variable->getTargetBundle() &&
        $variable->getType() == 'entity_empty_variable'
      ) {
        $output[$variable->id()] = $variable->label() . ' [' . $variable->id() . ']';
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function processSettings(array $settings, ItemInterface $item) {
    // Unset the values from the add new line.
    unset($settings['field_value']);
    unset($settings['entity_field']);
    unset($settings['field_value_title']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    $entity_variable_id = $action->getSettings('variable');
    $event_variables    = $event->getArgument('variables');
    $entity_variable    = $event_variables->getVariable($entity_variable_id);
    $result             = [];

    if ($entity_variable) {
      /** @var \Drupal\Core\Entity\Entity $entity */
      $entity = $entity_variable->getValue();

      $fields_values = $action->getSettings('fields_values');
      if (is_array($fields_values)) {
        foreach ($fields_values as $field_value) {
          $cardinality = $entity->get($field_value['entity_field'])
            ->getFieldDefinition()
            ->getFieldStorageDefinition()
            ->getCardinality();

          if ($cardinality === 1) {
            // Single value field.
            // TODO check this variable processing.
            $value = $this->processVariables($field_value['field_value'], $event_variables);
          }
          else {
            // Multiple value field.
            $arr = explode(chr(10) . '|', $field_value['field_value']);
            if (substr($arr[0], 0, 1) == '|') {
              $arr[0] = substr($arr[0], 1, strlen($arr[0]) - 1);
            }
            foreach ($arr as $key => $value) {
              if (substr($value, strlen($value) - 1, 1) == "\r") {
                $arr[$key] = substr($value, 0, strlen($value) - 1);
              }
              $arr[$key . '000000'] = $this->processVariables($arr[$key], $event_variables);
              unset($arr[$key]);
            }

            // Put all values at the array root.
            foreach ($arr as $key => $item) {
              if (is_array($item)) {
                unset($arr[$key]);
                foreach ($item as $new_key => $new_item) {
                  $arr[$key + $new_key] = $new_item;
                }
              }
            }
            ksort($arr);

            // Remove empty values.
            if (is_array($arr) && count($arr)) {
              foreach ($arr as $key => $item) {
                if (empty($item) || is_null($item) || (is_string($item) && strlen(trim($item)) == 0)) {
                  $arr[$key] = NULL;
                }
              }
            }

            $value = $arr;
          }

          if (empty($value) || is_null($value) || (is_string($value) && strlen(trim($value)) === 0)) {
            $value = NULL;
          }

          $entity->set($field_value['entity_field'], $value);

          $result[$field_value['entity_field']] = [
            '#type'   => 'markup',
            '#markup' => t('Entity variable: %variable field: %field filled with value: %value.', [
              '%variable' => $entity_variable_id,
              '%field'    => $field_value['entity_field'],
              '%value'    => is_array($value) ? implode(',', $value) : $value,
            ]),
          ];

        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet = new VariablesSet();
    $variable    = new VariableObject($item->getSettings('variable'));
    $variableSet->append($variable);

    $fields_values_variables = $item->getSettings('fields_values');

    if (is_array($fields_values_variables)) {
      foreach ($fields_values_variables as $fields_values_variable) {
        $field_value = $fields_values_variable['field_value'];
        $variable_names = $this->pregMatch($field_value);
        if (is_array($variable_names)) {
          foreach ($variable_names as $variable_name) {
            $variable = new VariableObject($variable_name);
            $variableSet->append($variable);
          }
        }
      }
    }

    return $variableSet;
  }

}
