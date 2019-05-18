<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\BlockConfigBase;
use Drupal\panels_extended\BlockConfig\BlockFormInterface;
use Drupal\panels_extended_blocks\NodeListBlockBase;

/**
 * Adds the configuration to set nodes on fixed positions.
 */
class FixedNodesConfig extends BlockConfigBase implements
    AdminInfoInterface,
    AlterQueryInterface,
    AlterQueryRangeInterface,
    AlterQueryResultInterface,
    BlockFormInterface,
    FixedNodesInterface {

  /**
   * Name of the configuration field.
   */
  const CFG_NAME = 'fixed_positions';

  /**
   * The block.
   *
   * @var \Drupal\panels_extended_blocks\NodeListBlockBase
   */
  protected $block;

  /**
   * The number of fixed positions to show in the form.
   *
   * If 0, this will default to the number fetched by block->getNumberOfItems().
   *
   * @var int
   */
  protected $numberOfPositionsToShow;

  /**
   * Are all fixed position required to be filled in?
   *
   * @var bool
   */
  protected $allRequired;

  /**
   * Local storage for the validated fixed positions.
   *
   * @var array
   */
  private $validatedFixedPositions;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended_blocks\NodeListBlockBase $block
   *   The block.
   * @param int $numberOfPositionsToShow
   *   The number of fixed positions to show in the form.
   *   If 0, this will default to the number fetched by block->getNumberOfItems().
   * @param bool $allRequired
   *   Are all fixed position required to be filled in?
   */
  public function __construct(NodeListBlockBase $block, $numberOfPositionsToShow = 0, $allRequired = FALSE) {
    parent::__construct($block);
    $this->numberOfPositionsToShow = $numberOfPositionsToShow;
    $this->allRequired = $allRequired;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyBlockForm(array &$form, FormStateInterface $form_state) {
    $nrToShow = $this->getNumberPositionsInForm();
    if ($nrToShow <= 0) {
      return;
    }

    $savedPositions = isset($this->configuration[self::CFG_NAME]) ? $this->configuration[self::CFG_NAME] : [];

    $form[self::CFG_NAME] = [
      '#title' => t('Posities overschrijven'),
      '#type' => 'details',
      '#open' => $this->allRequired || !empty($savedPositions),

      'positions' => [
        '#type' => 'table',
        '#header' => $this->getTableColumns(),
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => self::CFG_NAME . '-order-weight',
          ],
        ],
      ],
    ];
    for ($i = 0; $i < $nrToShow; $i++) {
      $form[self::CFG_NAME]['positions'][] = $this->getTableRow($i, (isset($savedPositions[$i]) ? $savedPositions[$i] : NULL));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitBlockForm(array &$form, FormStateInterface $form_state) {
    $positions = [];

    $values = $form_state->getValue([self::CFG_NAME, 'positions']);
    if (is_array($values)) {
      foreach ($values as $value) {
        $positions[] = (!empty($value['node']) ? (int) $value['node'] : NULL);
      }
    }
    $this->block->setConfigurationValue(self::CFG_NAME, $positions);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPrimaryInfo() {
    $fixedNodeIds = $this->getFixedNodeIds();

    $nodeStorage = $this->block->getEntityTypeManager()->getStorage('node');

    $nodes = [];
    foreach ($fixedNodeIds as $idx => $nid) {
      if ($nid) {
        $nodes[] = ($idx + 1) . ' => ' . $nodeStorage->load($nid)->label();
      }
    }
    if (empty($nodes)) {
      return NULL;
    }
    return t('Fixed positions') . ':' . PHP_EOL . '• ' . implode(PHP_EOL . '• ', $nodes);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminSecondaryInfo() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(SelectInterface $query, $isCountQuery) {
    $fixedNodeIds = $this->getFixedNodeIds();
    if (!empty($fixedNodeIds)) {
      $query->condition('nfd.nid', $fixedNodeIds, 'NOT IN');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueryRangeDelta(&$start, &$length) {
    $cnt = count($this->getFixedNodeIds());
    if ($cnt > 0) {
      $length -= $cnt;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterQueryResult(array &$nids) {
    $fixedPositions = $this->getValidatedFixedPositions();

    $newList = [];
    $i = 0;
    foreach ($fixedPositions as $position) {
      if (!empty($position)) {
        $newList[] = $position;
      }
      elseif (isset($nids[$i])) {
        $newList[] = $nids[$i];
        unset($nids[$i]);
        $i++;
      }
    }

    $nids = array_merge($newList, $nids);
  }

  /**
   * {@inheritdoc}
   */
  public function getFixedNodeIds() {
    return array_filter($this->getValidatedFixedPositions());
  }

  /**
   * Gets the validated fixed positions with either a node ID or NULL per position.
   *
   * @return array
   *   Validated fixed positions.
   */
  private function getValidatedFixedPositions() {
    if (isset($this->validatedFixedPositions)) {
      return $this->validatedFixedPositions;
    }

    if (!isset($this->configuration[self::CFG_NAME])) {
      $this->validatedFixedPositions = [];
      return $this->validatedFixedPositions;
    }

    $nrOfItems = $this->block->getNumberOfItems();
    $cnt = 0;

    $nodeStorage = $this->block->getEntityTypeManager()->getStorage('node');

    $validNodeIds = [];
    foreach ($this->configuration[self::CFG_NAME] as $idx => $nid) {
      if ($cnt >= $nrOfItems) {
        break;
      }
      $validNodeIds[$idx] = NULL;
      if ($nid !== NULL) {
        /** @var \Drupal\node\NodeInterface $node */
        $node = $nodeStorage->load($nid);
        if ($node && $node->isPublished()) {
          $validNodeIds[$idx] = $nid;
          $cnt++;
        }
      }
    }

    $this->validatedFixedPositions = $validNodeIds;
    return $this->validatedFixedPositions;
  }

  /**
   * Gets the number of items to be shown in the form.
   *
   * @return int
   *   The number of items to be shown in the form.
   */
  protected function getNumberPositionsInForm() {
    $nrOfItems = $this->block->getNumberOfItems();
    if ($this->numberOfPositionsToShow > 0 && $this->numberOfPositionsToShow < $nrOfItems) {
      return $this->numberOfPositionsToShow;
    }
    return $nrOfItems;
  }

  /**
   * Gets the columns for the table in the configuration form.
   *
   * @return array
   *   A list of columns to be used to the table in the form.
   */
  protected function getTableColumns() {
    return [
      'position' => t('Position'),
      'node' => t('Node'),
      'weight' => t('Weight'),
    ];
  }

  /**
   * Builds the render array for one table row.
   *
   * @param int $idx
   *   Row index.
   * @param int|null $savedValue
   *   Node ID or NULL when not set.
   *
   * @return array
   *   The render array for one table row.
   */
  protected function getTableRow($idx, $savedValue) {
    $row = [
      '#attributes' => ['class' => ['draggable']],
      '#weight' => $idx,

      'position' => ['#plain_text' => ($idx + 1)],
      'node' => [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'match_operator' => 'CONTAINS',
          'sort' => [
            'field' => 'created',
            'direction' => 'DESC',
          ],
        ],
        '#default_value' => $savedValue !== NULL ? $this->block->getEntityTypeManager()->getStorage('node')->load($savedValue) : NULL,
        '#process_default_value' => TRUE,
        '#required' => $this->allRequired,
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $idx]),
        '#title_display' => 'invisible',
        '#default_value' => $idx,
        '#attributes' => ['class' => [self::CFG_NAME . '-order-weight']],
      ],
    ];

    $nodeTypes = $this->block->getNodeTypes();
    if (!empty($nodeTypes)) {
      $row['node']['#selection_settings']['target_bundles'] = $nodeTypes;
    }

    return $row;
  }

}
