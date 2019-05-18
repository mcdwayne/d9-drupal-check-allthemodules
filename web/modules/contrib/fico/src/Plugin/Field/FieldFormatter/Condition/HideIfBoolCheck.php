<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_if_bool_check",
 *   label = @Translation("Hide if checkbox is checked"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideIfBoolCheck extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $options = [];
    $fields = $this->getEntityFields($settings['entity_type'], $settings['bundle']);

    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name'] && $field->getType() == 'boolean') {
        $options[$field_name] = $field->getLabel();
      }
    }

    $default_target_field = isset($settings['settings']['target_field']) ? $settings['settings']['target_field'] : NULL;
    $form['target_field'] = [
      '#type' => 'select',
      '#title' => t('Field'),
      '#options' => $options,
      '#default_value' => $default_target_field,
      '#required' => TRUE,
    ];
    $default_negate = isset($settings['settings']['negate']) ? $settings['settings']['negate'] : NULL;
    $form['negate'] = [
      '#type' => 'checkbox',
      '#title' => t('Negate'),
      '#description' => t('If checked, the condition result is negated such that it returns TRUE if it evaluates to FALSE.'),
      '#default_value' => $default_negate,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    if (!($entity = $this->getEntity($build))) {
      $build[$field]['#access'] = FALSE;
      return;
    }
    $items = $entity->get($settings['settings']['target_field']);

    foreach ($items as $key => $item) {
      $value = $item->getValue();
      if ((empty($settings['settings']['negate']) && !empty($value['value'])) || (!empty($settings['settings']['negate']) && empty($value['value']))) {
        $build[$field]['#access'] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    $options = [];
    $fields = $this->getEntityFields($settings['entity_type'], $settings['bundle']);

    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name']) {
        $options[$field_name] = $field->getLabel();
      }
    }
    $not = $settings['settings']['negate'] ? sprintf(' %s', t('not')) : '';

    return t("Condition: %condition (%settings)", [
      "%condition" => t('Hide if checkbox is%not checked', ['%not' => $not]),
      '%settings' => $options[$settings['settings']['target_field']],
    ]);
  }

}
