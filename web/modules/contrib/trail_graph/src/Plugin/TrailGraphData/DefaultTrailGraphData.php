<?php

namespace Drupal\trail_graph\Plugin\TrailGraphData;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\trail_graph\Plugin\TrailGraphDataBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views trail graph data handler.
 *
 * @TrailGraphData(
 *   id = "default_trail_graph_data",
 *   label = @Translation("Default tg data provider"),
 * )
 */
class DefaultTrailGraphData extends TrailGraphDataBase {

  use StringTranslationTrait;


  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * Constructs a Trail graph data object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager object.
   * @param \Drupal\Core\Database\Driver\mysql\Connection $connection
   *   Database connection object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(StylePluginBase $style) {
    $options = $style->displayHandler->getFieldLabels(TRUE);

    $form['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (isset($style->options['trail_data_options']['title'])) ? $style->options['trail_data_options']['title'] : 'title',
      '#description' => $this->t('Title of the node.'),
    ];

    $form['node_label'] = [
      '#type' => 'select',
      '#title' => $this->t('Node label'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (isset($style->options['trail_data_options']['node_label'])) ? $style->options['trail_data_options']['node_label'] : 'title',
      '#description' => $this->t('This will be the title you see on nodes.'),
    ];

    $form['content_preview'] = [
      '#type' => 'select',
      '#title' => $this->t('Content preview link'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (isset($style->options['trail_data_options']['content_preview'])) ? $style->options['trail_data_options']['content_preview'] : 'title',
      '#description' => $this->t('Link that will open preview of nodes.'),
    ];

    $form['trail_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Trail field'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (isset($style->options['trail_data_options']['trail_field'])) ? $style->options['trail_data_options']['trail_field'] : NULL,
      '#description' => $this->t('Provide fields that hold trail id.'),
    ];

    $form['trail_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Trail color field'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (isset($style->options['trail_data_options']['trail_color'])) ? $style->options['trail_data_options']['trail_color'] : NULL,
      '#description' => $this->t('Provide fields that hold trail color.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getAllTrailData(ViewExecutable $view) {
    $nodes = $this->getRowNodes($view->result);
    $trails = $this->getRowTrails($nodes, $view);
    $trail_nodes = $this->getTrailNodes($trails, $nodes);

    return ['trails' => $trails, 'trail_nodes' => $trail_nodes];
  }

  /**
   * Gets list of node entities keyed by nid.
   *
   * @param array $rows
   *   Array $view->result.
   *
   * @return array
   *   Array of loaded entities.
   */
  protected function getRowNodes(array $rows) {
    $data = [];
    foreach ($rows as $row) {
      $data[$row->nid] = $row->_entity;
    }

    return $data;
  }

  /**
   * Gets list of taxonomy terms related as trails.
   *
   * @param array $nodes
   *   Nodes found on view.
   * @param \Drupal\views\ViewExecutable $view
   *   View style plugin.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   Array of loaded term entities.
   */
  protected function getRowTrails(array $nodes, ViewExecutable $view) {
    $tids = [];
    if (!isset($view->style_plugin->options['trail_data_options']['trail_field'])) {
      return [];
    }

    /** @var \Drupal\node\Entity\Node $node */
    foreach ($nodes as $node) {
      if ($node->hasField($view->style_plugin->options['trail_data_options']['trail_field'])) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $trail_field */
        $trail_field = $node->get($view->style_plugin->options['trail_data_options']['trail_field']);
        if ($trail_field && $trail_field->getFieldDefinition()->getType() != 'entity_reference') {
          // @todo this should log error
          return [];
        }
        $trails = $trail_field->getValue();
        foreach ($trails as $trail) {
          $tids[$trail['target_id']] = $trail['target_id'];
        }
      }
    }

    return $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($tids);
  }

  /**
   * Gets list of nodes related to terms that is not in original result set.
   *
   * @param array $trails
   *   List of trails keyed by term id.
   * @param array $nodes
   *   Nodes from original result set.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   List of nodes keyed by nid.
   */
  protected function getTrailNodes(array $trails, array $nodes) {
    if (empty($trails)) {
      return [];
    }

    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition($this->configuration['view']->style_plugin->options['trail_data_options']['trail_field'], array_keys($trails), 'IN')
      ->execute();

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple(array_keys(array_diff_key(array_flip($nids), $nodes)));

    return $nodes;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrailNodeFields(ViewExecutable $view, array $trail_nodes) {
    // Add trail nodes to result set.
    foreach ($trail_nodes as $nid => $node) {
      $row = clone $view->result[0];
      $row->_entity = $node;
      $row->nid = $nid;
      $row->selected = TRUE;
      $view->result[] = $row;
    }
    // Render added result rows.
    // @todo check that method exists
    $view->style_plugin->resetRenderFields($view->result);

    $rows = $view->result;
    $items = [];
    $index = 0;
    $config_fields = $view->style_plugin->options['trail_data_options'];

    while ($rows) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = reset($rows)->_entity;

      $data = [
        'selected' => !isset(reset($rows)->selected),
        'id' => intval($node->id()),
        'published' => $node->isPublished(),
      ];

      foreach (array_keys($view->field) as $field) {
        $value = $view->style_plugin->getFieldValue($index, $field);
        // Assign constant keys for fields that contains vis.js nid and label.
        if (in_array($field, $config_fields)) {
          foreach (array_keys($config_fields, $field) as $key) {
            if ($key == 'content_preview') {
              $data[$key] = $view->style_plugin->getField($index, $field);
            }
            else {
              $data[$key] = $value;
            }
          }
        }
      }

      array_shift($rows);
      $items[$node->id()] = $data;
      $index++;
    }

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrailFields(array $trails, array $trail_info) {
    $trail_data = [];
    foreach ($trail_info as $trail_id => $trail_nodes) {
      if (isset($trails[$trail_id])) {
        $trail_data_options = $this->configuration['view']->style_plugin->options['trail_data_options'];

        $color = NULL;
        if (isset($trail_data_options['trail_color'])) {
          if ($trails[$trail_id]->get($trail_data_options['trail_color']) instanceof FieldItemList) {
            $color = $trails[$trail_id]->get($trail_data_options['trail_color'])->color;
          }
        }

        $trail_data[] = [
          'id' => 'T' . $trail_id,
          'tid' => $trail_id,
          'title' => $trails[$trail_id]->name->value,
          'content_preview' => NULL,
          'selected' => FALSE,
          'color' => $color,
          'published' => TRUE,
          'isHeader' => TRUE,
          'links' => $this->getOrderedLinks($trail_nodes, $trail_id),
        ];
      }
    }

    return $trail_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllNodeWeights(array $term_ids) {
    if (empty($term_ids)) {
      return [];
    }

    $query = $this->connection->select('taxonomy_index', 'ti');
    $query->fields('ti', ['tid', 'nid', 'weight']);
    $query->condition('ti.tid', $term_ids, 'IN');
    $query->orderBy('ti.weight', 'ASC');
    $query->orderBy('ti.nid', 'ASC');

    $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $trail_data = [];
    foreach ($results as $row) {
      $trail_data[$row['tid']][] = ['nid' => $row['nid'], 'weight' => $row['weight']];
    }

    return $trail_data;
  }

  /**
   * Lists trail links.
   *
   * @param array $ordered_trail_nodes
   *   List of node data for specific trail.
   * @param string $trail_id
   *   Term id.
   *
   * @return array
   *   List of links.
   */
  protected function getOrderedLinks(array $ordered_trail_nodes, $trail_id) {
    $links = [];
    foreach ($ordered_trail_nodes as $node) {
      if (!isset($prev)) {
        $prev = 'T' . $trail_id;
        $prev_weight = $node['weight'] - 1;
      }

      $links[] = [
        'from' => $prev,
        'to' => intval($node['nid']),
        'bothNodesHaveSameWeight' => $node['weight'] == $prev_weight,
      ];
      $prev = intval($node['nid']);
      $prev_weight = $node['weight'];
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrailHeaderFields(array $trail_data) {
    $headers = [];
    foreach ($trail_data as $header) {
      unset($header['links']);
      $headers[] = $header;
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getExposedFilterInput(ViewExecutable $view, array $filter_fields) {
    $inputValues = [];

    // Iterate through given filter fields.
    foreach ($filter_fields as $filterField) {
      $input = explode(',', $view->getExposedInput()[$filterField]);

      // TODO: Adjust exposed filter so it contains filtered data in proper
      // format (currently we have to pick id out of raw string)
      // Example: "Equilibrar as Contas Públicas" (224) become "T224".
      if ($filterField === 'trail_id') {
        foreach ($input as $item) {
          if (!empty($item)) {
            $inputValues[$filterField][] = 'T' . preg_replace("/([^0-9])/", '', $item);
          }
        }
      }
      elseif (count($input) > 0 && !empty($input[0])) {
        $inputValues[$filterField] = $input;
      }
    }

    return $inputValues;
  }

}
