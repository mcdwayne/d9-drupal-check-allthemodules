<?php

/**
 * @file
 * Contains \Drupal\iban_validator\Plugin\FieldValidationRule\IbanFieldValidationRule.
 */

namespace Drupal\iban_validator\Plugin\FieldValidationRule;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_validation\ConfigurableFieldValidationRuleBase;
use Drupal\field_validation\FieldValidationRuleSetInterface;

/**
 * IbanFieldValidationRule.
 *
 * @FieldValidationRule(
 *   id = "iban_field_validation_rule",
 *   label = @Translation("IBAN"),
 *   description = @Translation("Verifies that user-entered values are valid IBANs.")
 * )
 */
class IbanFieldValidationRule extends ConfigurableFieldValidationRuleBase {

  /**
   * {@inheritdoc}
   */
  public function addFieldValidationRule(FieldValidationRuleSetInterface $field_validation_rule_set) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'field_validation_rule_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($params) {
    $value = isset($params['value']) ? $params['value'] : '';
    $rule = isset($params['rule']) ? $params['rule'] : NULL;
    $context = isset($params['context']) ? $params['context'] : NULL;

    if (!$value) {
      // Don't validate empty field. This is up to the required-field validator.
      return;
    }
    // Load library.
    if (($library = libraries_load('php-iban')) && !empty($library['loaded'])) {
      // Verify value.
      if (!verify_iban($value)) {
        $context->addViolation($rule->getErrorMessage());
      }
    }
    else {
      // No library, no validation.
      $context->addViolation($rule->getErrorMessage());
    }
  }

}
