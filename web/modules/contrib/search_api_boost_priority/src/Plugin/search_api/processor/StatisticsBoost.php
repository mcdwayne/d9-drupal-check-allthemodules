<?php

namespace Drupal\search_api_boost_priority\Plugin\search_api\processor;

use Drupal\comment\CommentInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\statistics\StatisticsViewsResult;

/**
 * Adds a boost to indexed items based on Node Statistics.
 *
 * @SearchApiProcessor(
 *   id = "search_api_boost_priority_statistics",
 *   label = @Translation("Statistics specific boosting"),
 *   description = @Translation("Adds a boost to indexed items based on Node Statistics."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   }
 * )
 */
class StatisticsBoost extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The available boost factors.
   *
   * @var string[]
   */
  protected static $boostFactors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * The available boost scale.
   *
   * @var string[]
   */
  protected static $boostScale = [
    '100' => '1-100',
    '500' => '101-500',
    '5000' => '1001-5000',
    '10000' => '5001-10000',
    '50000' => '10001-50000',
    '100000' => '50001-100000',
    '500000' => '100000-500000',
    '1000000' => '500000-1000000',
    '10000000' => '1000000-10000000',
    '10000001' => 'More than 10000000',
  ];

  /**
   * Can only be enabled for an index that indexes Nodes.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      $allowedEntityTypes = self::allowedEntityTypes();
      $entityType = $datasource->getEntityTypeId();

      if (in_array($entityType, $allowedEntityTypes)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Whitelist of allowed entity types.
   *
   * @return array
   *   Whitelist of allowed entity types.
   */
  private static function allowedEntityTypes() {
    return [
      'node',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boost_table' => [
        'weight' => '0.0',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['boost_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('View Statistics'),
        $this->t('Boost'),
      ],
    ];

    $statScale = static::$boostScale;

    // Loop over each scale and create a form row.
    foreach ($statScale as $scaleId => $scaleDesc) {
      if (isset($this->configuration['boost_table'][$scaleId]['weight'])) {
        $weight = $this->configuration['boost_table'][$scaleId]['weight'];
      }
      elseif (isset($this->configuration['boost_table']['weight'])) {
        $weight = $this->configuration['boost_table']['weight'];
      }

      // Table columns containing raw markup.
      $form['boost_table'][$scaleId]['label']['#plain_text'] = $scaleDesc;

      // Weight column element.
      $form['boost_table'][$scaleId]['weight'] = [
        '#type' => 'select',
        '#title' => t('Weight for @title', ['@title' => $scaleDesc]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#options' => static::$boostFactors,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      $entityTypeId = $item->getDatasource()->getEntityTypeId();

      // TODO Extend for other entities.
      switch ($entityTypeId) {
        case 'node':
          // Get the node object.
          $node = $this->getNode($item->getOriginalObject());

          // Get statistics for this node.
          $nodeStatistics = $this->statisticsGet($node->id());
          $boost = $this->getStatBoost($nodeStatistics);
          break;
      }

      if ($boost) {
        $item->setBoost($boost);
      }
    }
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * Will be either the node itself, or the node the comment is attached to.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof CommentInterface) {
      $item = $item->getCommentedEntity();
    }
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

  /**
   * Retrieves a node's "view statistics".
   *
   * See statistics.module.
   *
   * @param int $id
   *   Node Id.
   *
   * @return array
   *   Stats.
   */
  public function statisticsGet(int $id) {
    if ($id > 0) {
      /** @var \Drupal\statistics\StatisticsViewsResult $statistics */
      $statistics = \Drupal::service('statistics.storage.node')->fetchView($id);

      // For backwards compatibility, return FALSE if an invalid node ID was
      // passed in.
      if (!($statistics instanceof StatisticsViewsResult)) {
        return [
          'totalcount' => $this->configuration['totalcount'],
          'daycount' => $this->configuration['daycount'],
          'timestamp' => $this->configuration['timestamp'],
        ];
      }

      return [
        'totalcount' => $statistics->getTotalCount(),
        'daycount' => $statistics->getDayCount(),
        'timestamp' => $statistics->getTimestamp(),
      ];
    }
  }

  /**
   * Retrieves the boost related to a Node Statistics.
   *
   * @param array $nodeStatistics
   *   Stats.
   *
   * @return float
   *   Boost Value.
   */
  protected function getStatBoost(array $nodeStatistics) {
    // Get configured stats.
    $boosts = $this->configuration['boost_table'];
    $totalCount = (int) $nodeStatistics['totalcount'];

    // Loop over and find boost.
    foreach ($boosts as $boostId => $boostWeight) {
      if ($totalCount > 0 && $totalCount <= $boostId) {
        return (double) $boostWeight['weight'];
      }
    }

    return NULL;
  }

}
