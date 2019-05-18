<?php

namespace Drupal\business_rules\Plugin\BusinessRulesAction;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesActionPlugin;
use Drupal\business_rules\VariablesSet;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SetFieldValue.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesAction
 *
 * @BusinessRulesAction(
 *   id = "set_field_value",
 *   label = @Translation("Set field value"),
 *   group = @Translation("Entity"),
 *   description = @Translation("Set a value to an Entity field"),
 *   isContextDependent = TRUE,
 *   hasTargetEntity = TRUE,
 *   hasTargetBundle = TRUE,
 *   hasTargetField = TRUE,
 * )
 */
class SetFieldValue extends BusinessRulesActionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {
    $settings['value'] = [
      '#type'          => 'textarea',
      '#title'         => t('Value'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('value'),
      '#description'   => t('The value to be set on the field.
        <br>For a multi-valor field (cardinality > 1) type one value per line starting by pipeline (|) as the example:
        <br>|Value 1
        <br>|Value 2
        <br>|Value 3'),
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event) {
    /** @var \Drupal\Core\Entity\Entity $entity */
    $variables   = $event->getArgument('variables');
    $field       = $action->getSettings('field');
    $raw_value   = $action->getSettings('value');
    $value       = $this->processVariables($raw_value, $variables);
    $entity      = $event->getArgument('entity');
    $cardinality = $entity->$field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    // Set value to multi-valor field.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || $cardinality > 1) {
      $arr = explode(chr(10) . '|', $value);
      if (substr($arr[0], 0, 1) == '|') {
        $arr[0] = substr($arr[0], 1, strlen($arr[0]) - 1);
      }
      foreach ($arr as $key => $value) {
        if (substr($value, strlen($value) - 1, 1) == "\r") {
          $arr[$key] = substr($value, 0, strlen($value) - 1);
        }
        $arr[$key . '000000'] = $this->processVariables($arr[$key], $variables);
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

      $value = $arr;
    }

    $entity->$field->setValue($value);

    $result = [
      '#type'   => 'markup',
      '#markup' => t('Entity %entity updated. Field: %field, value: %value', [
        '%entity' => $entity->getEntityTypeId(),
        '%field'  => $field,
        '%value'  => is_array($value) ? implode(',', $value) : $value,
      ]),
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processVariables($content, VariablesSet $variables) {

    if ($variables->count()) {
      foreach ($variables->getVariables() as $variable) {
        if (is_string($variable->getValue()) || is_numeric($variable->getValue())) {
          $content = str_replace('{{' . $variable->getId() . '}}', $variable->getValue(), $content);
        }
        elseif (is_array($variable->getValue())) {
          if (preg_match_all(self::VARIABLE_REGEX, $content)) {
            if ($content == '{{' . $variable->getId() . '}}') {
              $content = $variable->getValue();
            }
          }
        }
      }
    }

    return $content;
  }

}
