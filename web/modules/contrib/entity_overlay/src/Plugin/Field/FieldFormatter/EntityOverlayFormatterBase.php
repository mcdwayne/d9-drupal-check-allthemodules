<?php

namespace Drupal\entity_overlay\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Trait EntityOverlayFormatterBase.
 *
 * @package Drupal\entity_overlay\Plugin\Field\FieldFormatter
 */
trait EntityOverlayFormatterBase {

  /**
   * Returns the overlay url.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to display on the overlay.
   * @param string $view_mode
   *   View mode for the overlay.
   *
   * @return \Drupal\Core\Url
   *   Url of the entity overlay.
   */
  public function getOverlayUrl(EntityInterface $entity, $view_mode) {
    // Set up the options for the route, default the method to 'nojs' since
    // the drupal ajax library will replace that.
    $options = [
      'method' => 'nojs',
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'view_mode' => $view_mode,
    ];
    // Create the path from the route, passing the options it needs.
    return Url::fromRoute('entity_overlay.load_entity', $options);
  }

  /**
   * Returns the overlay link.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to display on the overlay.
   * @param string $view_mode
   *   View mode for the overlay.
   * @param string $title
   *   Optional link title override.
   *
   * @return array
   *   Link render array.
   */
  public function getOverlayLink(EntityInterface $entity, $view_mode, $title = '') {
    $url = $this->getOverlayUrl($entity, $view_mode);
    return [
      '#type' => 'link',
      '#title' => empty($title) ? $entity->label() : $title,
      '#url' => $url,
      '#options' => $url->getOptions() + [
        'attributes' => [
          'class' => [
            // Tell Drupal core to treat it as ajax.
            'use-ajax',
            'entity-overlay-link',
            'entity-overlay__' . $entity->getEntityTypeId() . '-' . $entity->id(),
          ],
        ],
      ],
    ];
  }

}
