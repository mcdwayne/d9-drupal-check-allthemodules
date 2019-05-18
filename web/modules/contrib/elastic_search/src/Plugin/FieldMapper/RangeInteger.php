<?php

namespace Drupal\elastic_search\Plugin\FieldMapper;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elastic_search\Elastic\ElasticDocumentManager;
use Drupal\elastic_search\Plugin\FieldMapperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RangeInteger.
 *
 * Meta type for Range field from the module Range.
 *
 * This FieldMapper provides support to map the type integer.
 *
 * @FieldMapper(
 *   id = "range_integer",
 *   label = @Translation("Range Integer")
 * )
 */
class RangeInteger extends FieldMapperBase {

  use StringTranslationTrait;

  /**
   * @var  EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ElasticDocumentManager
   */
  protected $documentManager;

  /**
   * @inheritdoc
   */
  public function getSupportedTypes() {
    return [
      'range_integer',
    ];
  }

  /**
   * FieldMapperBase constructor.
   *
   * @param array  $configuration
   * @param string $plugin_id
   * @param mixed  $plugin_definition
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entityTypeManager,
                              ElasticDocumentManager $documentManager) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->documentManager = $documentManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array                                                     $configuration
   * @param string                                                    $plugin_id
   * @param mixed                                                     $plugin_definition
   *
   * @return static
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('elastic_search.document.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function getDslFromData(array $data): array {
    return [
      'type'       => 'nested',
      'properties' => [
        'from' => [
          'type' => 'integer',
        ],
        'to'   => [
          'type' => 'integer',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function normalizeFieldData(string $id,
                                     array $data,
                                     array $fieldMappingData) {
    $objectMappings = [];

    // Getting the values that we will pass to ES.
    $allowedProperties = $this->getDslFromData($data);
    $allowedProperties = array_keys($allowedProperties['properties']);

    foreach ($data as $key => $range) {
      $values = [];
      foreach ($allowedProperties as $property) {
        if (isset($range[$property])) {
          $values[$property] = $range[$property];
        }
      }
      if ($values) {
        $objectMappings[] = $values;
      }
    }
    return $objectMappings;
  }

}
