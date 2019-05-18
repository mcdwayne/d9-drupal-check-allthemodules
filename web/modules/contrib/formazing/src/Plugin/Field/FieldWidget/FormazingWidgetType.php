<?php

namespace Drupal\formazing\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\formazing\Entity\FormazingEntity;

/**
 * Plugin implementation of the 'formazing_widget_type' widget.
 *
 * @FieldWidget(
 *   id = "formazing_widget_type",
 *   label = @Translation("Formazing (select)"),
 *   field_types = {
 *     "formazing_field_type"
 *   }
 * )
 */
class FormazingWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state
  ) {
    $entityType = 'formazing_entity';

    // Load existing formazing forms to build options list
    $query = \Drupal::entityQuery($entityType);
    $entityIds = $query->execute();

    $forms = \Drupal::entityTypeManager()
      ->getStorage($entityType)
      ->loadMultiple($entityIds);

    $element['value'] = $element + [
        '#type' => 'select',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
        '#options' => array_map([$this, 'getNameFromEntity'], $forms),
        '#empty_value' => '',
        '#empty_option' => $element['#required'] ? t('- Select -') : t('- None -'),
      ];

    return $element;
  }

  /**
   * Helper to get the name from a formazing entity
   *
   * @param \Drupal\formazing\Entity\FormazingEntity $entity
   *
   * @return string
   */
  private function getNameFromEntity($entity) {
    if (!$entity instanceof FormazingEntity) {
      return $entity;
    }

    return $entity->getName();
  }
}
