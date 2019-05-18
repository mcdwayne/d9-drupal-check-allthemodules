<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "entity_fields",
 *  label = @Translation("Fields"),
 *  description = "Checks entity fields for usage.",
 *  tags = {
 *   "content",
 *  }
 * )
 */
class EntityFields extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $entityQuery;

  /**
   * ContentTypes constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $entity_type_mgr, $entity_field_mgr, $entity_query) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->entityTypeManager = $entity_type_mgr;
    $this->entityFieldManager = $entity_field_mgr;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    $map = $this->getFieldMap();
    foreach ($map as $entity_type => $fields) {
      $findings += $this->enumerateFields($entity_type, $fields);
    }

    return $findings;
  }

  /**
   * Process the fields for the given entity type.
   *
   * @param string $entity_type
   *   The entity type as a string.
   * @param array $fields
   *   An array of field descriptions keyed by name.
   *
   * @return array
   *   And array of Findings
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function enumerateFields($entity_type, $fields) {

    $findings = [];

    $entity_def = $this->entityTypeManager->getDefinition($entity_type);
    $bundle_column = $entity_def->getKey('bundle');

    foreach ($fields as $field_name => $field_description) {
      foreach ($field_description['bundles'] as $bundle_name) {
        $counts = $this->countInstances($entity_type, $bundle_column, $bundle_name, $field_name);

        $finding_key = implode('.', [
            $this->getPluginId(),
            $entity_type,
            $bundle_name,
            $field_name,
          ]);

        if (empty($counts)) {
          $finding = $this->needsReview($finding_key, [
            'entity_type' => $entity_type,
            'bundle_name' => $bundle_name,
            'field_name' => $field_name,
          ]);

          $finding->setLabel($this->t(
            'Unused field :field_name on entity :bundle_name', [
              ':bundle_name' => $bundle_name,
              ':field_name' => $field_name,
            ]
          ));

          $finding->setMessage($this->t(
            'The field :field_name on the :bundle_name bundle of type :entity_type is unused.', [
            ':entity_type' => $entity_type,
            ':bundle_name' => $bundle_name,
            ':field_name' => $field_name,
          ]));

          $findings[] = $finding;
        }
        else {
          $finding = $this->noActionRequired($finding_key, [
            'entity_type' => $entity_type,
            'bundle_name' => $bundle_name,
            'field_name' => $field_name,
            'count' => $counts,
          ]);

          $finding->setLabel($this->t(
            'Field :field_name on :bundle_name in use', [
              ':bundle_name' => $bundle_name,
              ':field_name' => $field_name,
            ]
          ));

          $finding->setMessage($this->t(
            'The field :field_name on the :bundle_name bundle of type :entity_type is used :count time(s).', [
              ':entity_type' => $entity_type,
              ':bundle_name' => $bundle_name,
              ':field_name' => $field_name,
              ':count' => $counts,
            ]
          ));

          $findings[] = $finding;
        }
      }
    }

    return $findings;
  }

  /**
   * Count the instances of the field.
   *
   * @param $entity_type
   * @param $bundle_column
   * @param $bundle_name
   * @param $field_name
   *
   * @return array|int
   */
  protected function countInstances($entity_type, $bundle_column, $bundle_name, $field_name) {
    $query = $this->entityQuery->get($entity_type);

    // If the entity type is bundled, then add that as a condition.
    if (!empty($bundle_column)) {
      $query->condition($bundle_column, $bundle_name);
    }

    // If the field exists, count it.
    $query->exists($field_name)
      ->count();

    try {
      // Get the result.
      $result = $query->execute();

      return $result;
    }
    catch (DatabaseExceptionWrapper $e) {
    }

    return 0;
  }

  /**
   * Gets the field map for custom fields.
   *
   * @return array
   *   The field map.
   *
   * @see \Drupal\Core\Entity\EntityFieldManager::getFieldMap()
   */
  protected function getFieldMap() {
    $out = [];

    // Get the field map from the field manager.
    $map = $this->entityFieldManager->getFieldMap();

    // Go through each entry in the field map for data we need.
    foreach ($map as $entity_type => $fields) {

      // Filter out only custom fields.
      $fields_for_type = array_filter($fields,
        function ($key) {
          return preg_match('/^field\_/', $key);
        },
        ARRAY_FILTER_USE_KEY);

      // Add fields to the output, skipping entity types with no custom fields.
      if (!empty($fields_for_type)) {
        $out[$entity_type] = $fields_for_type;
      }
    }

    return $out;
  }
}
