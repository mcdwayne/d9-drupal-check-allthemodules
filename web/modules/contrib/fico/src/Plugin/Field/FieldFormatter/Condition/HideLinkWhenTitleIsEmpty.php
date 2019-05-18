<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_link_when_title_is_empty",
 *   label = @Translation("Hide link when link title is empty"),
 *   dsFields = TRUE,
 *   types = {
 *     "link_field",
 *     "link"
 *   }
 * )
 */
class HideLinkWhenTitleIsEmpty extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {}

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    if (!empty($build[$field]['#items'])) {
      foreach ($build[$field]['#items'] as $item) {
        $info = $item->getValue($field);
        if (!$info['title'] || $info['title'] === $info['uri']) {
          $build[$field]['#access'] = FALSE;
        }
      }
    }

    if (empty($build[$field]['#items'])) {
      $build[$field]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    return t('Condition: %condition', [
      "%condition" => t('Hide link when link title is empty'),
    ]);
  }

}
