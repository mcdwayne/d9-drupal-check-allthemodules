<?php
/**
 * @file
 * Contains Drupal\block_render\Normalizer\BlockResponseNormalizer.
 */

namespace Drupal\block_render\Normalizer;

use Drupal\block_render\Response\BlockResponseInterface;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Class to Normalize the Libraries.
 */
class BlockResponseNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = ['Drupal\block_render\Response\BlockResponseInterface'];

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = array()) {
    if (!($object instanceof BlockResponseInterface)) {
      throw new \InvalidArgumentException('Object must implement Drupal\block_render\Response\BlockResponseInterface');
    }

    return [
      'dependencies' => $this->serializer->normalize($object->getAssets()->getLibraries(), $format, $context),
      'assets' => [
        'header' => $object->getAssets()->getHeader(),
        'footer' => $object->getAssets()->getFooter(),
      ],
      'content' => $this->serializer->normalize($object->getContent(), $format, $context),
    ];
  }

}
