<?php
/**
 * @file
 * Contains Drupal\block_render\Normalizer\RenderedContentNormalizer.
 */

namespace Drupal\block_render\Normalizer;

use Drupal\block_render\Content\RenderedContentInterface;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Class to Normalize the Rendered Content.
 */
class RenderedContentNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ['Drupal\block_render\Content\RenderedContentInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    if (!($object instanceof RenderedContentInterface)) {
      throw new \InvalidArgumentException('Object must implement Drupal\block_render\Content\RenderedContentInterface');
    }

    $result = array();
    foreach ($object as $id => $content) {
      $result[$id] = (string) $content;
    }

    if ($object->isSingle()) {
      $result = reset($result);
    }

    return $result;
  }

}
