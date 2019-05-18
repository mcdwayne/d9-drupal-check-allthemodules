<?php

namespace Drupal\entity_pilot\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hal\Normalizer\ContentEntityNormalizer;
use Drupal\rest\LinkManager\LinkManagerInterface;

/**
 * Defines a class for normalizing book nodes.
 */
class BookNormalizer extends ContentEntityNormalizer {

  /**
   * Psuedo field name for embedding parent book.
   *
   * @var string
   */
  const PSUEDO_PARENT_FIELD_NAME = 'book_parent';

  /**
   * Psuedo field name for embedding book.
   *
   * @var string
   */
  const PSUEDO_BOOK_FIELD_NAME = 'book';

  /**
   * Allowed node types for books.
   *
   * @var string[]
   */
  protected $allowedTypes = [];

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';

  /**
   * Constructs a new BookNormalizer object.
   *
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   Link manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($link_manager, $entity_manager, $module_handler);
    $this->allowedTypes = $config_factory->get('book.settings')->get('allowed_types');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $normalized = parent::normalize($entity, $format, $context);
    $context += [
      'account' => NULL,
      'included_fields' => NULL,
    ];
    $embedded_cache = [];
    if (in_array($entity->bundle(), $this->allowedTypes, TRUE) && isset($entity->book)) {
      $fields = [
        'bid' => self::PSUEDO_BOOK_FIELD_NAME,
        'pid' => self::PSUEDO_PARENT_FIELD_NAME,
      ];
      foreach ($fields as $field_name => $mock_field_name) {
        if (!empty($context['included_fields']) && !in_array($field_name, $context['included_fields'], TRUE)) {
          // Only normalizing specific fields.
          continue;
        }
        if (isset($entity->book[$field_name]) && $embedded_entity = $this->entityManager->getStorage('node')
          ->load($entity->book[$field_name])
        ) {
          if (!isset($embedded_cache[$embedded_entity->id()])) {
            $langcode = isset($context['langcode']) ? $context['langcode'] : NULL;

            // Normalize the target entity.
            $embedded = $this->serializer->normalize($embedded_entity, $format, ['included_fields' => ['uuid']]);
            $link = $embedded['_links']['self'];
            // If the field is translatable, add the langcode to the link
            // relation object. This does not indicate the language of the
            // target entity.
            if ($langcode) {
              $embedded['lang'] = $link['lang'] = $langcode;
            }
          }
          else {
            $embedded = $embedded_cache[$embedded_entity->id()];
            $link = $embedded['_links']['self'];
            $link['lang'] = $embedded['lang'];
          }
          $mock_field_uri = $this->linkManager->getRelationUri($entity->getEntityTypeId(), $entity->bundle(), $mock_field_name, $context);
          $normalized['_links'][$mock_field_uri] = [$link];
          $normalized['_embedded'][$mock_field_uri] = [$embedded];
          $normalized['book'][$field_name] = $embedded_entity->uuid();
        }
      }
    }
    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $book = FALSE;
    if (isset($data['book'])) {
      $book = $data['book'];
      unset($data['book']);
    }
    $denormalized = parent::denormalize($data, $class, $format, $context);
    if ($book) {
      foreach (['pid', 'bid'] as $field) {
        if (isset($book[$field])) {
          $uuid = $book[$field];
          if ($entity = $this->entityManager->loadEntityByUuid('node', $uuid)) {
            $denormalized->book[$field] = $entity->id();
          }
        }
      }
    }
    return $denormalized;
  }

}
