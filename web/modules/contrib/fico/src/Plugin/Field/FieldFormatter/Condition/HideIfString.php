<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_if_string",
 *   label = @Translation("Hide when target field contains a string"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideIfString extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $options = [];
    $fields = $this->getEntityFields($settings['entity_type'], $settings['bundle']);
    $allowed_field_types = fico_text_types();

    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name'] && in_array($field->getType(), $allowed_field_types)) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $default_target = isset($settings['settings']['target_field']) ? $settings['settings']['target_field'] : NULL;
    $default_string = isset($settings['settings']['string']) ? $settings['settings']['string'] : NULL;
    $form['target_field'] = [
      '#type' => 'select',
      '#title' => t('Select target field'),
      '#options' => $options,
      '#default_value' => $default_target,
    ];
    $form['string'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter target string'),
      '#default_value' => $default_string,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    $found = fico_string_search($build, $field, $settings);
    if ($found == TRUE) {
      $build[$field]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    $options = [];
    $fields = $this->getEntityFields($settings['entity_type'], $settings['bundle']);
    $allowed_field_types = fico_text_types();

    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name'] && in_array($field->getType(), $allowed_field_types)) {
        $options[$field_name] = $field->label();
      }
    }

    return t('Condition: %condition (%field = "%string")', [
      "%condition" => t('Hide when target field contains a string'),
      '%field' => $options[$settings['settings']['target_field']],
      '%string' => $settings['settings']['string'],
    ]);
  }

}
