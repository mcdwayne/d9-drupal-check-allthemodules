<?php

namespace Drupal\entity_slug\Plugin\Field\FieldType;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;

/**
 * Provides a field type of slug.
 *
 * @FieldType(
 *   id = "slug_path",
 *   label = @Translation("Slug path"),
 *   category = @Translation("Slug"),
 *   module = "entity_slug",
 *   description = @Translation("Provides a Slug path field type that allows you slugify all components of a path pattern."),
 *   default_widget = "slug_path_default",
 *   default_formatter = "slug_default",
 * )
 */
class SlugPathItem extends SlugItemBase {

  protected $separator = '/';

  /**
   * {@inheritdoc}
   */
  public function slugify($input) {
    $pathParts = explode($this->separator, trim($input, $this->separator));

    $outputParts = [];

    foreach ($pathParts as $index => $pathPart) {
      $outputParts[] = parent::slugify($pathPart);
    }

    $path = implode($this->separator, $outputParts);
    $path = ltrim($path, $this->separator);
    $path = $this->separator . $path;

    return $path;
  }
}
