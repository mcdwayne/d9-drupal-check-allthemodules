<?php

namespace Drupal\entityreference_rendered_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_rendered_disabled' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_rendered_disabled",
 *   label = @Translation("Entity reference rendered disabled"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceRenderedDisabled extends EntityReferenceRenderedBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if (empty($items->get($delta)->getValue()['target_id'])) {
      $element['#markup'] = '<div class="label">' . $element['#title'] . '</div>';
      $element['#markup'] .= '<div>' . $this->t('Value creation for this field is disabled.') . '</div>';
      return $element;
    }

    $target = $this->entityTypeManager->getStorage($this->targetEntityType)->load($items->get($delta)->getValue()['target_id']);
    $view_builder = $this->entityTypeManager->getViewBuilder($target->getEntityTypeId());
    $element['entity'] = $view_builder->view($target, $this->getSetting('display_mode'), $target->language()->getId());

    return $element;
  }

}
