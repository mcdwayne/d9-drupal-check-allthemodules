<?php

namespace Drupal\entity_slug\Plugin\Field\FieldWidget;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'slug path' widget.
 *
 * @FieldWidget(
 *   id = "slug_path_default",
 *   module = "entity_slug",
 *   label = @Translation("Slug path field widget"),
 *   field_types = {
 *     "slug_path",
 *   }
 * )
 */
class SlugPathWidget extends SlugWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function getInformation(FieldItemListInterface $slugItems) {
    $information = [];

    $information[] = $this->t('Multiple slugs can be separated with a "/" to form a path.');

    $information = array_merge($information, parent::getInformation($slugItems));

    return $information;
  }
}
