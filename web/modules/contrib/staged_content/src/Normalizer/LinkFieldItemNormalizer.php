<?php

namespace Drupal\staged_content\Normalizer;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\serialization\Normalizer\FieldItemNormalizer as BaseFieldItemNormalizer;

/**
 * Converts the Drupal field item object structure to HAL array structure.
 */
class LinkFieldItemNormalizer extends BaseFieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = LinkItem::CLASS;

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
    // If this is a reference to an entity mark it as such instead of adding
    // the string directly.
    $uriParts = parse_url($item->uri);

    list($entityTypeId, $entityId) = explode('/', $uriParts["path"], 2);
    if ($uriParts['scheme'] == 'entity' && $entityId != '') {
      $entity = $this->entityTypeManager->getStorage($entityTypeId)->load($entityId);

      if (isset($entity)) {
        return [
          'target_type' => $entityTypeId,
          'target_uuid' => $entity->uuid(),
          'title' => $item->title,
          'options' => $item->options,
        ];
      }
      // Empty out the value if the entity does not exist.
      return [];
    }

    return parent::normalize($item, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {

    if (isset($data['target_uuid'])) {

      // On the first pass skip any items linking to entities as they might not
      // exist yet.
      if ($context['ignore_references']) {
        $data['uri'] = 'https://example.com';
      }
      else {
        $entity = $this->entityRepository->loadEntityByUuid($data['target_type'], $data['target_uuid']);
        if (!isset($entity)) {
          return [];
        }
        $data['uri'] = 'entity:' . $entity->toUrl()->getInternalPath();
      }

      unset($data['target_type']);
      unset($data['target_uuid']);
    }

    return $data;
  }

}
