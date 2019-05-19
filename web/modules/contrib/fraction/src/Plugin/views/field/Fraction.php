<?php

namespace Drupal\fraction\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Field handler for Fraction database columns.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("fraction")
 */
class Fraction extends FieldPluginBase {

  /**
   * @inheritdoc
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Default to automatic precision.
    $options['precision'] = array('default' => 0);
    $options['auto_precision'] = array('default' => TRUE);

    return $options;
  }

  /**
   * @inheritdoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    // Add fields for configuring precision and auto_precision.
    $form['precision'] = array(
      '#type' => 'textfield',
      '#title' => t('Precision'),
      '#description' => t('Specify the number of digits after the decimal place to display when converting the fraction to a decimal. When "Auto precision" is enabled, this value essentially becomes a minimum fallback precision.'),
      '#default_value' => $this->options['precision'],
    );
    $form['auto_precision'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto precision'),
      '#description' => t('Automatically determine the maximum precision if the fraction has a base-10 denominator. For example, 1/100 would have a precision of 2, 1/1000 would have a precision of 3, etc.'),
      '#default_value' => $this->options['auto_precision'],
    );

    // Merge into the parent form.
    parent::buildOptionsForm($form, $form_state);

    // Remove the 'click_sort_column' form element, because we provide a custom
    // click_sort function below to use the numerator and denominator columns
    // simultaneously.
    unset($form['click_sort_column']);
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {

    // Ensure the main table for this field is included.
    $this->ensureMyTable();

    // Formula for calculating the final value, by dividing numerator by denominator.
    // These are available as additional fields.
    $formula = $this->tableAlias . '.' . $this->definition['additional fields']['numerator'] . ' / ' . $this->tableAlias . '.' . $this->definition['additional fields']['denominator'];

    // Add the orderby.
    $this->query->addOrderBy(NULL, $formula, $order, $this->tableAlias . '_decimal');
  }

  /**
   * Load the numerator and denominator values and perform conversion to decimal.
   */
  public function getValue(ResultRow $values, $field = NULL) {

    // Find the numerator and denominator field aliases.
    $numerator_alias = $this->aliases[$this->definition['additional fields']['numerator']];
    $denominator_alias = $this->aliases[$this->definition['additional fields']['denominator']];

    // If both values are available...
    if (isset($values->{$numerator_alias}) && isset($values->{$denominator_alias})) {

      // Convert to decimal.
      $numerator = $values->{$numerator_alias};
      $denominator = $values->{$denominator_alias};
      $precision = $this->options['precision'];
      $auto_precision = $this->options['auto_precision'];
      return fraction($numerator, $denominator)->toDecimal($precision, $auto_precision);
    }
  }
}
