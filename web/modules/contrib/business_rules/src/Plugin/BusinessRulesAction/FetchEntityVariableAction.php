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
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FetchEntityVariable.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "fetch_entity_variable",
 *   label = @Translation("Fetch entity variable by id"),
 *   group = @Translation("Variable"),
 *   description = @Translation("Fetch an entity variable by id provided by
 *   another variable or a constant value."), isContextDependent = FALSE,
 *   hasTargetEntity = TRUE, hasTargetBundle = TRUE, hasTargetField = TRUE,
 * )
 */
class FetchEntityVariableAction extends BusinessRulesActionPlugin {

  /**
   * If the entity is fetched.
   *
   * @var bool
   */
  private $entityIsFetched = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings = [];

    if (!$item->isNew()) {

      $settings['empty_variable'] = [
        '#type'          => 'select',
        '#title'         => t('Empty variable'),
        '#required'      => TRUE,
        '#description'   => t('Empty variable to be filled in.'),
        '#options'       => $this->getAvailableEmptyVariables($item),
        '#default_value' => empty($item->getSettings('empty_variable')) ? '' : $item->getSettings('empty_variable'),
      ];

      $settings['value'] = [
        '#type'          => 'textfield',
        '#title'         => t('Value'),
        '#default_value' => $item->getSettings('value'),
        '#description'   => t('The entity ID value to fill the variable.'),
      ];

    }

    return $settings;
  }

  /**
   * Get the available empty variables for the context.
   *
   * @param \Drupal\business_rules\Entity\Action $item
   *   The action.
   *
   * @return array
   *   Array of available entities variables.
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
    asort($output);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    $form['settings']['field']['#description'] = t('Select the entity id field.');
    $form['settings']['field']['#title']       = t('Entity id field.');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {

    /** @var \Drupal\business_rules\VariablesSet $variables */
    $id_variable         = $action->getSettings('empty_variable');
    $variables           = $event->getArgument('variables');
    $processed_variables = $this->fetchEntityVariable($action, $variables);
    $event->setArgument('variables', $processed_variables);

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Entity variable %variable fetched.', ['%variable' => $id_variable]),
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchEntityVariable($action, VariablesSet $event_variables) {

    /** @var \Drupal\business_rules\VariableObject $variable */
    /** @var \Drupal\business_rules\Entity\Action $action */
    $id_field = $action->getSettings('field');
    $bundle = $action->getTargetBundle();
    $id = $action->getSettings('value');
    $id = parent::processVariables($id, $event_variables);
    $empty_variable = $action->getSettings('empty_variable');
    $value = NULL;

    if ($event_variables->count()) {
      foreach ($event_variables->getVariables() as $variable) {
        if ($variable->getType() == 'entity_empty_variable' && $empty_variable == substr($variable->getId(), 0, strlen($empty_variable))) {

          $original_variable_value = $variable->getValue();

          if (!stristr($variable->getId(), '->')) {
            $entity = $this->fetchEntity($id, $variable, $id_field, $action, $bundle, $original_variable_value);
            $event_variables->replaceValue($variable->getId(), $entity);
          }
          else {
            if (!$this->entityIsFetched) {
              $entity = $this->fetchEntity($id, $variable, $id_field, $action, $bundle, $original_variable_value);
            }

            if ($entity instanceof Entity) {

              $field       = explode('->', $variable->getId())[1];

              if ($entity->$field) {
                $already_replaced = [];
                $definition  = $entity->$field->getFieldDefinition();
                $field_type  = $definition->getType();
                $cardinality = $definition->getFieldStorageDefinition()
                  ->getCardinality();

                if ($field_type == 'entity_reference') {
                  $property_name = 'target_id';
                }
                else {
                  $property_name = 'value';
                }

                if ($cardinality === 1) {
                  $value = $entity->get($field)->$property_name;
                }
                else {
                  $arr              = $entity->$field->getValue();
                  $field_definition = $entity->getFieldDefinition($field);

                  // Check if value is entity reference.
                  if ($field_definition->getType() == 'entity_reference') {
                    $entity_references = $entity->get($field)
                      ->referencedEntities();
                    foreach ($arr as $key => $item) {
                      $arr[$key] = $item['target_id'];
                      if (substr($variable->getId(), strlen($variable->getId()) - strlen('->label'), strlen('->label')) != '->label') {
                        $variable_key_id = substr($variable->getId(), strlen($variable->getId()) - strlen("[$key]"), strlen("[$key]")) == "[$key]" ? $variable->getId() : $variable->getId() . "[$key]";
                        $multi_val = new VariableObject($variable_key_id, $item['target_id'], $variable->getType());
                        $event_variables->append($multi_val);

                        // Prepare the variable field with the entity reference
                        // title / name / other property name.
                        /** @var \Drupal\Core\Entity\Entity $entity_reference */
                        $entity_reference = $entity_references[$key];
                        $entity_reference_value = $entity_reference->label();
                        $variable_key_label_id = substr($variable->getId(), strlen($variable->getId()) - strlen("[$key]->label"), strlen("[$key]->label")) == "[$key]->label" ? $variable->getId() : $variable->getId() . "[$key]->label";
                        $entity_reference_label = new VariableObject($variable_key_label_id, $entity_reference_value, $variable->getType());
                        $event_variables->append($entity_reference_label);
                      }
                      // Fetch array labels variable.
                      $arr_labels = [];
                      foreach ($entity_references as $reference) {
                        $arr_labels[] = $reference->label();
                      }
                      if (count($arr_labels)) {
                        $variable_label_id = substr($variable->getId(), strlen($variable->getId()) - strlen('->label'), strlen('->label')) == '->label' ? $variable->getId() : $variable->getId() . "->label";
                        $entity_reference_labels = new VariableObject($variable_label_id, $arr_labels, $variable->getType());
                        $event_variables->append($entity_reference_labels);
                        $event_variables->replaceValue($variable_label_id, $arr_labels);
                        $already_replaced[] = $variable_label_id;
                      }
                    }
                  }
                  else {
                    foreach ($arr as $key => $item) {
                      if (isset ($item['value'])) {
                        $arr[$key] = $item['value'];
                        $multi_val = new VariableObject($variable->getId() . "[$key]", $item['value'], $variable->getType());
                        $event_variables->append($multi_val);
                      }
                    }
                  }
                  $value = $arr;
                }
                if (!in_array($variable->getId(), $already_replaced)) {
                  $event_variables->replaceValue($variable->getId(), $value);
                }
              }

            }

          }


        }
      }
    }

    return $event_variables;
  }

  /**
   * Fetch the entity.
   *
   * @param string $id
   *   The entity id.
   * @param \Drupal\business_rules\VariableObject $variable
   *   The VariableObject.
   * @param string $id_field
   *   The field id.
   * @param \Drupal\business_rules\Entity\Action $action
   *   The Business rule Action action.
   * @param string $bundle
   *   The bundle.
   * @param mixed $original_variable_value
   *   The original variable value.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  private function fetchEntity($id, VariableObject $variable, $id_field, Action $action, $bundle, $original_variable_value) {
    try {
      $var = Variable::load($variable->getId());
      if ($var) {
        $entity_type = $var->getTargetEntityType();
        $entity      = \Drupal::entityTypeManager()
          ->getStorage($entity_type)
          ->load($id);

        if (is_object($entity)) {
          $new_entity            = clone $entity;
          $this->entityIsFetched = TRUE;

          return $new_entity;
        }
        else {
          // drupal_set_message(t("Action: %action fail. It's not possible to fetch entity %entity, bundle %bundle, with id=%id", [
          //   '%action' => $action->label() . ' [' . $action->id() . ']',
          //   '%entity' => $entity_type,
          //   '%bundle' => $bundle,
          //   '%id'     => $id,
          // ]), 'error');

          return $original_variable_value;
        }
      }
      else {
        // drupal_set_message(t("Action: %action fail. Variable: %variable could not be loaded.", [
        //   '%action'   => $action->label() . ' [' . $action->id() . ']',
        //   '%variable' => $variable->getId(),
        // ]), 'error');

        return $original_variable_value;
      }
    }
    catch (\Exception $e) {
      drupal_set_message($e, 'error');

      return $original_variable_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVariables(ItemInterface $item) {
    $variableSet    = parent::getVariables($item);
    $empty_variable = new VariableObject($item->getSettings('empty_variable'));

    $variableSet->append($empty_variable);

    return $variableSet;
  }

}
