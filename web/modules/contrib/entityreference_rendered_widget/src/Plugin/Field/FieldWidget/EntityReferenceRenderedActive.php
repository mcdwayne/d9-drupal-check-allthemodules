<?php

namespace Drupal\entityreference_rendered_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_reference_rendered_disabled' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_rendered_active",
 *   label = @Translation("Entity reference rendered active"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class EntityReferenceRenderedActive extends EntityReferenceRenderedBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $target_ids = array_keys($this->getOptions($items->getEntity()));
    $targets = $this->entityTypeManager->getStorage($this->targetEntityType)->loadMultiple($target_ids);

    $view_builder = $this->entityTypeManager->getViewBuilder($this->targetEntityType);

    foreach ($targets as $target) {
      $gen_view = $view_builder->view($target, $this->getSetting('display_mode'), $target->language()->getId());
      $element['#options'][$target->id()] = render($gen_view);
    }

    return $element;
  }

}
