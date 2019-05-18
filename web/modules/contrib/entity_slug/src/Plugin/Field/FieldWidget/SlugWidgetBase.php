<?php

namespace Drupal\entity_slug\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_slug\Plugin\Field\FieldType\SlugItemInterface;

abstract class SlugWidgetBase extends WidgetBase implements SlugWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->input) ? $items[$delta]->input : '';

    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => '60',
      '#maxlength' => 255,
    ];

    return [
      'input' => $element,
      'information' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->getInformation($items),
      ],
      'token_help' => [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all' // TODO: Limit to current entity type field is attached to
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInformation(FieldItemListInterface $slugItems) {
    $information = [];

    $information[] = $this->t('Enter text in the field, and it will be converted into a slug for display or use elsewhere.');

    if (!$slugItems->isEmpty()) {
      /** @var SlugItemInterface $slugItem */
      $slugItem = $slugItems->first();

      foreach ($slugItem->getSlugifiers() as $slugifier) {
        $information = array_merge($information, $slugifier->information());
      }
    }

    return $information;
  }
}
