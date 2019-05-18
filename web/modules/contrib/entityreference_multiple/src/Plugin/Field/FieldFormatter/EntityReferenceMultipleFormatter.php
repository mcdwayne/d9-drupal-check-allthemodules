<?php

namespace Drupal\entityreference_multiple\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'entityreference_entity_multiple_view' formatter.
 *
 * @FieldFormatter(
 *   id = "entityreference_entity_multiple_view",
 *   label = @Translation("Rendered entity with different view modes"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceMultipleFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'textfield',
      '#title' => t('View mode list'),
      '#description' => t('Enter comma separated view modes. Each one represents one entity.<br />
        The last view mode listed here will be used for any remaining entities.<br />
        Example: <pre>featured,teaser,teaser,teaser,default</pre>'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $view_modes = explode(',', $this->getSetting('view_mode'));
    $view_mode = 'default';

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (!empty($view_modes)) {
        $view_mode = trim(array_shift($view_modes));
      }
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());
    }

    return $elements;
  }

}
