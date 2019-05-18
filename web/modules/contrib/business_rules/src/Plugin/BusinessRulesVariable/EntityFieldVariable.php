<?php

namespace Drupal\business_rules\Plugin\BusinessRulesVariable;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesVariablePlugin;
use Drupal\business_rules\VariableObject;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class EntityValue.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesVarthoughtt
 * @BusinessRulesVariable(
 *   id = "entity_filed_variable",
 *   label = @Translation("Value from Entity field"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Set an variable value with a value from entity field."),
 *   reactsOnIds = {},
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class EntityFieldVariable extends BusinessRulesVariablePlugin {

  const CURRENT_DATA = 'current_data';

  const ORIGINAL_DATA = 'original_data';

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['data'] = [
      '#type'          => 'select',
      '#title'         => t('Data'),
      '#required'      => TRUE,
      '#options'       => [
        ''                  => t('- Select -'),
        self::CURRENT_DATA  => t('Current value'),
        self::ORIGINAL_DATA => t('Original value'),
      ],
      '#description'   => t('Current value is the value that is being saved.') . '<br>' . t('Original value is the previous saved value.'),
      '#default_value' => empty($item->getSettings('data')) ? '' : $item->getSettings('data'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function changeDetails(Variable $variable, array &$row) {
    // Show a link to a help modal window.
    $content = '<ul>';
    $content .= '<li>' . t('To access a particular multi-value field such as target id, you can use <code>{{@variable_id[delta]}}</code> where "delta" is the delta value to get a one value or <code>{{@variable_id}}</code> to get an array of values;', ['@variable_id' => $variable->id()]) . '</li>';
    $content .= '<li>' . t('To access a particular multi-value field label you can use <code>{{@variable_id[delta]->label}}</code> where "delta" is the delta value to get one label or <code>{{@variable_id->label}}</code> to get an array of labels;', ['@variable_id' => $variable->id()]) . '</li>';
    $content .= '<li>' . t('If you are looping through this field and this field is NOT an entity reference, you can access the current value using <code>{{@variable_id->current}}</code>;', ['@variable_id' => $variable->id()]) . '</li>';
    $content .= '<li>' . t('If you are looping through this field and this field IS an entity reference, you can use <code>{{@variable_id->current->id}}</code> to get the entity reference target id or <code>{{@variable_id->current->label}}</code> to get the entity reference label.', ['@variable_id' => $variable->id()]) . '</li>';
    $content .= '</ul>';

    $keyvalue = $this->util->getKeyValueExpirable('entity_filed_variable');
    $keyvalue->set($variable->id(), ['#markup' => $content]);

    $details_link = Link::createFromRoute(t('Click here to see how to use this variable.'),
      'business_rules.ajax.modal',
      [
        'method'     => 'nojs',
        'title'      => t('Entity Field Variable Help'),
        'collection' => 'entity_filed_variable',
        'key'        => $variable->id(),
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
   * {@inheritdoc}
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event) {

    $field_name  = $variable->getSettings('field');
    $data        = $variable->getSettings('data');
    $variableSet = new VariablesSet();

    switch ($data) {
      case self::CURRENT_DATA:
        $entity = $event->getArgument('entity');
        break;

      case self::ORIGINAL_DATA:
        $entity = $event->getArgument('entity_unchanged');
        break;
    }

    try {
      $value = $entity->get($field_name)->getValue();
      // Check if value is a entity reference.
      /** @var \Drupal\field\Entity\FieldConfig $field_definition */
      $field_definition = $entity->getFieldDefinition($field_name);
      if ($field_definition->getType() == 'entity_reference') {
        $entity_references = $entity->get($field_name)->referencedEntities();
        foreach ($entity_references as $key => $item) {
          $value[$key]['entity_reference_label'] = $item->label();
        }
      }
    }
    catch (\Exception $e) {
      throw $e;
    }

    $arr_label = [];
    if (count($value) === 1) {
      if (isset($value[0]['value'])) {
        $value = $value[0]['value'];
      }
      elseif (isset($value[0]['target_id'])) {
        $value = $value[0]['target_id'];
      }
      else {
        $value = NULL;
      }
    }
    else {
      $arr_value = [];
      foreach ($value as $key => $item) {
        if (isset($item['value'])) {
          $arr_value[] = $item['value'];
          $multi_val   = new VariableObject($variable->id() . "[$key]", $item['value'], $variable->getType());
        }
        elseif (isset($item['target_id'])) {
          $arr_value[] = $item['target_id'];
          $multi_val   = new VariableObject($variable->id() . "[$key]", $item['target_id'], $variable->getType());
          $title       = new VariableObject($variable->id() . "[$key]->label", $item['entity_reference_label'], $variable->getType());
          $variableSet->append($title);

          $arr_label[] = $item['entity_reference_label'];
        }
        else {
          $arr_value[] = NULL;
          $multi_val   = new VariableObject($variable->id() . "[$key]", NULL, $variable->getType());
        }

        $variableSet->append($multi_val);
      }
      $value = $arr_value;
    }

    $variableObject = new VariableObject($variable->id(), $value, $variable->getType());
    $variableSet->append($variableObject);

    if (count($arr_label)) {
      $variableObject = new VariableObject($variable->id() . '->label', $arr_label, $variable->getType());
      $variableSet->append($variableObject);
    }

    return $variableSet;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    unset($form['variables']);
  }

}
