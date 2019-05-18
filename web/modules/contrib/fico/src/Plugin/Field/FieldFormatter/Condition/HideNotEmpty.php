<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * The plugin for check not empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_not_empty",
 *   label = @Translation("Hide when target field is not empty"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideNotEmpty extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $options = [];
    $fields = $this->getEntityFields($settings['entity_type'], $settings['bundle']);

    $not_allowed = [
      "list_string",
      "boolean",
    ];

    foreach ($fields as $field_name => $field) {
      if ($field_name != $settings['field_name']) {
        if (!in_array($field->getType(), $not_allowed)) {
          $options[$field_name] = $field->getLabel();
        }
      }
    }

    $default_value = isset($settings['settings']['target_field']) ? $settings['settings']['target_field'] : NULL;
    $form['target_field'] = [
      '#type' => 'select',
      '#title' => t('Field'),
      '#options' => $options,
      '#default_value' => $default_value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    #kint($settings);
    if (isset($build[$settings['settings']['target_field']]['#items'])) {
      $fields = $build[$settings['settings']['target_field']]['#items'];
      if (is_object($fields)) {
        $field_storage = FieldStorageConfig::loadByName($settings['entity_type'], $settings['settings']['target_field']);
        $values = $fields->getValue();
        switch ($field_storage->getType()) {
          case 'comment':
            if ($values[0]['comment_count'] > 0) {
              $build[$field]['#access'] = FALSE;
            }
            break;

          case 'image':
          case 'entity_reference':
            if (isset($values[0]['target_id'])) {
              $build[$field]['#access'] = FALSE;
            }
            break;

          case 'link':
            if (isset($values[0]['uri'])) {
              $build[$field]['#access'] = FALSE;
            }
            break;

          default:
            if (isset($values[0]['value'])) {
              $build[$field]['#access'] = FALSE;
            }
        }
      }
    }
    else {
      if ($entity = $this->getEntity($build)) {
        if (!$entity->get($settings['settings']['target_field'])->isEmpty()) {
          $build[$field]['#access'] = FALSE;
        }
      }
      else {
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

    return t("Condition: %condition (%settings)", [
      "%condition" => t('Hide when target field is not empty'),
      '%settings' => $options[$settings['settings']['target_field']],
    ]);
  }

}
