<?php

namespace Drupal\entity_normalization\Normalizer;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_normalization\EntityNormalizationManagerInterface;
use Drupal\serialization\Normalizer\NormalizerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

/**
 * Normalizes entities using the entity_normalization definition.
 */
class EntityConfigNormalizer extends NormalizerBase implements NormalizerAwareInterface {

  use NormalizerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = FieldableEntityInterface::class;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The plugin manager for entity normalization definitions.
   *
   * @var \Drupal\entity_normalization\EntityNormalizationManagerInterface
   */
  protected $normalizationManager;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param \Drupal\entity_normalization\EntityNormalizationManagerInterface $normalizationManager
   *   The plugin manager for entity normalization definitions.
   */
  public function __construct(ContainerInterface $container, EntityNormalizationManagerInterface $normalizationManager) {
    $this->container = $container;
    $this->normalizationManager = $normalizationManager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $object */

    $config = $this->normalizationManager->getEntityConfig($object, $format);

    $result = [];

    $fields = $config->getFields();
    foreach ($fields as $field) {
      if (!$field->isRequired() && !$object->hasField($field->getId())) {
        // The field isn't required and we don't have the field, skip it.
        continue;
      }
      $context['field_config'] = $field;

      $normalized = NULL;
      switch ($field->getType()) {
        case 'pseudo':
          $nName = $field->getNormalizerName();
          if ($nName !== NULL && $this->container->has($nName)) {
            $normalizer = $this->container->get($nName);
            if ($normalizer->supportsNormalization($object, $format, $context)) {
              $normalized = $normalizer->normalize($object, $format, $context);
            }
          }
          break;

        default:
          $def = $object->get($field->getId());
          $normalized = $this->normalizer->normalize($def, $format, $context);
          break;
      }
      if (!empty($group = $field->getGroup())) {
        $result[$group][$field->getName()] = $normalized;
      }
      else {
        $result[$field->getName()] = $normalized;
      }
    }

    foreach ($config->getNormalizers() as $normalizer) {
      /** @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface $n */
      $n = $this->container->get($normalizer);
      if ($n->supportsNormalization($object, $format)) {
        $result = array_merge($result, $n->normalize($object, $format, $context));
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return parent::supportsNormalization($data, $format) && $this->normalizationManager->hasEntityConfig($data, $format);
  }

}
