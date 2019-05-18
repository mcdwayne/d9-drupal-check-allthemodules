<?php
/**
 * @file
 * Contains \Drupal\collect\Normalizer\ContainerNormalizer.
 */

namespace Drupal\collect\Normalizer;

use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Normalizer for the Collect container entity type.
 */
class ContainerNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string|array
   */
  protected $supportedInterfaceOrClass = 'Drupal\collect\CollectContainerInterface';

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * Constructs an ContainerNormalizer object.
   *
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   */
  public function __construct(LinkManagerInterface $link_manager) {
    $this->linkManager = $link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $values = array();

    if (!empty($data['origin_uri'])) {
      $values['origin_uri'] = $data['origin_uri'];
    }
    if (!empty($data['schema_uri'])) {
      $values['schema_uri'] = $data['schema_uri'];
    }
    if (!empty($data['type'])) {
      $values['type'] = $data['type'];
    }
    if (!empty($data['data'])) {
      $values['data'] = $data['data'];
    }
    if (!empty($data['date'])) {
      $values['date'] = $data['date'];
    }

    return $class::create($values);
  }

  /**
   * Normalizes an object into a set of arrays/scalars.
   *
   * @param \Drupal\collect\CollectContainerInterface $entity
   *   Object to normalize.
   * @param string $format
   *   Format the normalization result will be encoded as.
   * @param array $context
   *   Context options for the normalizer.
   *
   * @return array
   *   The normalized data.
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $normalized = array();

    if ($format == 'hal_json') {
      $normalized['_links'] = array(
        'type' => array(
          'href' => $this->linkManager->getTypeUri($entity->getEntityTypeId(), $entity->bundle()),
        ),
      );
    }

    $normalized += array(
      'origin_uri' => $entity->getOriginUri(),
      'schema_uri' => $entity->getSchemaUri(),
      'type' => $entity->getType(),
      // @todo Handle binary data.
      // Binary data needs to be encoded if the format is a text based.
      'data' => $entity->getData(),
      'date' => $entity->getDate(),
    );

    return $normalized;
  }
}
