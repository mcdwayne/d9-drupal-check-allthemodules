<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\serialization\Normalizer\FieldItemNormalizer as BaseFieldItemNormalizer;

/**
 * Ensures that changed dates are not exported.
 *
 * Since this will all show up in git as changes. It seems far more pragmatic
 * to skip exporting this data.
 */
class CreatedItemNormalizer extends BaseFieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem';

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
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  protected $time;

  /**
   * FieldItemNormalizer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time handler.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository, TimeInterface $time) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityRepository = $entityRepository;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($item, $format = NULL, array $context = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    return [
      'value' => $this->time->getRequestTime(),
      'format' => 'U',
    ];
  }

}
