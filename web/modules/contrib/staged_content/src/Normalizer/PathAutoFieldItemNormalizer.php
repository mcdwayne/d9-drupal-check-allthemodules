<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\serialization\Normalizer\FieldItemNormalizer as BaseFieldItemNormalizer;

/**
 * Remove the "pid" when exporting path auto items. Since this tends to change
 * all the time and doesn't contain any real relevant information.
 */
class PathAutoFieldItemNormalizer extends BaseFieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\pathauto\PathautoItem';

  /**
   * {@inheritdoc}
   */
  protected $format = ['storage_json'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   *   The entity repository.
   */
  protected $entityRepository;

  /**
   * FieldItemNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($item, $format = NULL, array $context = []) {
    $data = parent::normalize($item, $format, $context);
    unset($data['pid']);
    return $data;
  }

}
