<?php

namespace Drupal\tikitoki\Plugin\views\style;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "tikitoki_json",
 *   title = @Translation("Tiki Toki JSON"),
 *   help = @Translation("Serializes views rows data into special Tiki Toki JSON format."),
 *   display_types = {"tikitoki"}
 * )
 */
class TikiTokiJson extends StylePluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;
  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;
  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;
  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, EntityFieldManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->definition         = $plugin_definition + $configuration;
    $this->serializer         = $serializer;
    $this->entityFieldManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    $this->addCategories($rows);
    $result = (string) $this->serializer->serialize($rows, 'json', ['views_style_plugin' => $this]);
    return 'TLonJSONPLoad(' . $result . ')';
  }

  /**
   * Add categories part at the beginning of the result.
   *
   * @param array &$rows
   *   Result rows array.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function addCategories(&$rows) {
    $prevent_duplicates_array = [];
    $color_field_name         = $this->view->rowPlugin->options['color_field'];
    $color_field_column       = $this->getColorFieldColumn($color_field_name);
    foreach ($this->view->result as $row_index => $row) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $row->_entity;
      $field_name = $this->view->rowPlugin->options['category_field'];
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
      if ($entity->hasField($field_name)) {
        $field = $entity->get($field_name);
        if (!$field->isEmpty()) {
          foreach ($field->getValue() as $delta => $value) {
            $term = $value['target_id'];
            if (!in_array($term, $prevent_duplicates_array)) {
              $prevent_duplicates_array[] = $term;
              $term = Term::load($term);
              /** @var \Drupal\Core\Field\FieldItemListInterface $color_field */
              $color_field = $term->hasField($color_field_name)
                ? $term->get($color_field_name)
                : NULL;
              $data = [
                'id'    => $term->id(),
                'title' => $term->getName(),
                'colour' => (!empty($color_field) && !$color_field->isEmpty())
                  ? $color_field->get(0)->getValue()[$color_field_column]
                  : '',
              ];
              array_unshift($rows, $data);
            }
          }
        }
      }
    }
  }

  /**
   * Get an appropriate column name for color field.
   *
   * @param string $field_name
   *   Field name.
   * 
   * @return string
   *   Column name. Could be 'value' or 'color'.
   */
  protected function getColorFieldColumn($field_name) {
    $column = 'value';
    $definitions = $this->entityFieldManager
      ->getFieldStorageDefinitions('taxonomy_term');
    if (isset($definitions[$field_name])
      && $definitions[$field_name] instanceof FieldStorageConfig
      && $definitions[$field_name]->getType() == 'color_field_type'
    ) {
      $column = 'color';
    }
    return $column;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['request_format'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    // The plugin always uses services from the serialization module.
    $providers[] = 'json';
    $providers[] = 'serialization';

    $dependencies += ['module' => []];
    $dependencies['module'] = array_merge($dependencies['module'], $providers);
    return $dependencies;
  }

}
