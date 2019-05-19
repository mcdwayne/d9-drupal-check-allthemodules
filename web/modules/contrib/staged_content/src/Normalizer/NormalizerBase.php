<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an ContentEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Checks or the context requires the including of the original id.
   *
   * @param array $context
   *   The context array.
   *
   * @return bool
   *   Should the id be included.
   */
  protected function includeId(array $context) {
    return isset($context['normalizer_options']['include_id']) ? $context['normalizer_options']['include_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    if (isset($this->formats)) {
      @trigger_error('::formats is deprecated in Drupal 8.4.0 and will be removed before Drupal 9.0.0. Use ::$format instead. See https://www.drupal.org/node/2868275', E_USER_DEPRECATED);

      $this->format = $this->formats;
    }

    return parent::checkFormat($format);
  }

}
