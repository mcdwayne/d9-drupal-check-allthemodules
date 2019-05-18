<?php

namespace Drupal\group_content_field\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;

/**
 * Plugin implementation of the 'plugin_reference_id' formatter.
 *
 * @FieldFormatter(
 *   id = "group_content_list",
 *   label = @Translation("Group content list."),
 *   field_types = {
 *     "group_content_item"
 *   }
 * )
 */
class GroupContentListFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode):array {
    $elements = [];

    // Initialize field.
    $items->setValue(1);

    foreach ($items as $delta => $item) {
      $value = $item->getValue();
      foreach (Group::loadMultiple($value['entity_gids']) as $group) {
        $elements[$delta][] = [
          '#type' => 'link',
          '#title' => $group->label(),
          '#url' => Url::fromRoute('entity.group.canonical', ['group' => $group->id()]),
          '#prefix' => '<div class="group-content-item">',
          '#suffix' => '</div>'
        ];
      }
    }

    return $elements;
  }

}
