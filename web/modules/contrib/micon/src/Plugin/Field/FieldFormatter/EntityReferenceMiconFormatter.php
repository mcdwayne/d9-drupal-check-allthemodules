<?php

namespace Drupal\micon\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\micon\MiconIconize;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_micon",
 *   label = @Translation("Label with Micon"),
 *   description = @Translation("Display the icon of an entity reference."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceMiconFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $icon = new MiconIconize($entity->label());
      if ($icon) {
        $bundle = $entity->bundle() == 'node_type' ? 'content_type' : $entity->bundle();
        $icon->addMatchPrefix($bundle)->setIconOnly();
        // If the link is to be displayed and the entity has a uri, display a
        // link.
        if ($output_as_link && !$entity->isNew()) {
          try {
            $uri = $entity->urlInfo();
          }
          catch (UndefinedLinkTemplateException $e) {
            $output_as_link = FALSE;
          }
        }

        if ($output_as_link && isset($uri) && !$entity->isNew()) {
          $elements[$delta] = [
            '#type' => 'link',
            '#title' => $icon,
            '#url' => $uri,
            '#options' => $uri->getOptions(),
          ];

          if (!empty($items[$delta]->_attributes)) {
            $elements[$delta]['#options'] += ['attributes' => []];
            $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and shouldn't be rendered in the field template.
            unset($items[$delta]->_attributes);
          }
        }
        else {
          $elements[$delta]['#markup'] = $icon->render();
        }
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    return $elements;
  }

}
