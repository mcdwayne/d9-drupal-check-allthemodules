<?php

namespace Drupal\field_expression\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Webit\Util\EvalMath\EvalMath;

/**
 * Plugin implementation of the 'field_expression' field type.
 *
 * @FieldType(
 *   id = "field_expression",
 *   label = @Translation("Expression"),
 *   description = @Translation("Create a field value calculated by evaluating an expression that can include tokens."),
 *   default_widget = "field_expression_default",
 *   default_formatter = "field_expression_value"
 * )
 */
class ExpressionItem extends FieldItemBase {


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'expression' => '',
      'default_zero' => TRUE,
      'suppress_errors' => TRUE,
      'debug_mode' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $entity_type_id = $this->getEntity()->getEntityTypeId();

    $element['expression'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Expression'),
      '#description' => $this->t('Enter a mathematical expression to calculate the value for this field. Expressions may include basic operators <code>(+-*/^)</code>, as well as the following functions:<br><code>sin(), sinh(), arcsin(), asin(), arcsinh(), asinh(), cos(), cosh(), arccos(), acos(), arccosh(), acosh(), tan(), tanh(), arctan(), atan(), arctanh(), atanh(), pow(), exp(), sqrt(), abs(), ln(), log(), time(), ceil(), floor(), min(), max(), round()</code><br>Tokens will be automatically replaced upon saving of the entity this field is attached to.<br>Default values can be provided for tokens by including them after the token, wrapped in curly braces. The default value will be used if the token does not resolve. Example: <code>[node:field_some_number]{100}</code><br>Line breaks will be replaced with spaces in the resulting expression.'),
      '#default_value' => $this->getSetting('expression'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => [$entity_type_id],
      '#required' => TRUE,
    ];

    $element['token_tree_link'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$entity_type_id],
    ];

    $element['default_zero'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Evalute empty tokens to zero?'),
      '#description' => $this->t('Select this option to default any unresolved tokens to zero. If unchecked, unresolved tokens will remain in the expression, likely resulting in an invalid expression, and thus a blank value (and an error depending on if you have error suppression on below). It is recommended that you use token-specific default values whenever possible (see above description for how to implement a token default value).'),
      '#default_value' => $this->getSetting('default_zero'),
    ];

    $element['suppress_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Supress Errors'),
      '#description' => $this->t('Check this box to supress any errors that occur when evaluating the expression. If an error occurs the evaluated value will just be blank.'),
      '#default_value' => $this->getSetting('suppress_errors'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('devel')) {
      $element['debug_mode'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Debug Mode'),
        '#description' => $this->t('Check this box to enable debug mode. After an expression is evaluated (e.g. after saving the entity this field is attached to), debug messages will be output with some feedback about the expression.'),
        '#default_value' => $this->getSetting('debug_mode'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->value = $this->evaluateExpression($this->value);
  }

  /**
   * Evaluate the expression for the field value.
   */
  public function evaluateExpression($expression) {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    // Replace line breaks
    $expression = str_replace(["\r", "\n"], '', $expression);
    $original_expression = $expression;

    // Replace the tokens
    $token_service = \Drupal::token();
    $expression = $token_service->replace($expression,
      [$entity_type => $entity],
      ['clear' => FALSE]
    );

    // Add back the default values for any tokens still there
    $remaining_tokens = $token_service->scan($expression);
    foreach ($remaining_tokens as $type => $tokens) {
      foreach ($tokens as $name => $token) {
        $matches = [];

        // First process any items with default values
        if (preg_match_all('/' . preg_quote($token) . '\{(.*?)\}/', $expression, $matches)) {
          // Replace any matches with the default value
          foreach ($matches[0] as $index => $match) {
            $expression = preg_replace('/' . preg_quote($match) . '/', $matches[1][$index], $expression);
          }
        }

        // We may also have instances of this token without default values, so
        // we process those as well
        if (preg_match('/' . preg_quote($token) . '/', $expression)) {
          if ($this->getSetting('default_zero')) {
            // We're using the default_zero
            $expression = preg_replace('/' . preg_quote($token) . '/', 0, $expression);
          }
        }

        // Clean up any remaining default value wrappers
        $expression = preg_replace('/\{.*?\}/', '', $expression);
      }
    }

    // Evaluate the final expression
    $math = new EvalMath;
    $math->suppress_errors = $this->getSetting('suppress_errors');
    $value = $math->evaluate($expression);

    // Support debugging expressions with devel module
    if (\Drupal::moduleHandler()->moduleExists('devel') && $this->getSetting('debug_mode')) {
      $debug = [
        'Original Expression:' => $original_expression,
        'Token Replaced Expression:' => $expression,
        'Expression Result:' => $value
      ];
      dpm($debug, 'Field Token Expression Debug Output');
    }

    return $value;
  }

}
