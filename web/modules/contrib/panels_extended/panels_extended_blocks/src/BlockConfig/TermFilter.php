<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\BlockConfigBase;
use Drupal\panels_extended\BlockConfig\BlockFormInterface;
use Drupal\panels_extended_blocks\NodeListBlockBase;
use Drupal\taxonomy\TermInterface;
use Exception;

/**
 * Adds filtering on one or more terms to the block.
 */
class TermFilter extends BlockConfigBase implements AdminInfoInterface, AlterQueryInterface, BlockFormInterface {

  /**
   * Name of the configuration field for selected terms.
   */
  const CFG_NAME_TERMS = 'terms';

  /**
   * Name of the configuration field for auto detect.
   */
  const CFG_AUTO_DETECT = 'autodetect_term';

  /**
   * Name of the configuration field for exclude terms.
   */
  const CFG_NAME_EXCLUDE = 'exclude_terms';

  /**
   * A list of vocabularies for which filters are generated.
   *
   * Key/value = voc ID/name.
   *
   * @var array
   */
  protected $vocabularies;

  /**
   * Constructor.
   *
   * @param \Drupal\panels_extended_blocks\NodeListBlockBase $block
   *   The block.
   * @param array $vocabularies
   *   A list of vocabularies for which filters are generated.
   *   Key/value = vocabulary ID/name.
   */
  public function __construct(NodeListBlockBase $block, array $vocabularies = []) {
    parent::__construct($block);

    $this->vocabularies = $vocabularies;
  }

  /**
   * {@inheritdoc}
   */
  public function modifyBlockForm(array &$form, FormStateInterface $form_state) {
    $form['term'] = [
      '#title' => t('Taxonomy filter'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['term'][self::CFG_AUTO_DETECT] = [
      '#title' => t('Auto-detect terms'),
      '#description' => t('If checked, automatically detects the term based on the visited page.'),
      '#type' => 'checkbox',
      '#default_value' => $this->isAutoDetect(),
      '#return_value' => TRUE,
    ];

    $allTermIds = isset($this->configuration[self::CFG_NAME_TERMS]) ? $this->configuration[self::CFG_NAME_TERMS] : [];
    foreach ($this->vocabularies as $vocId => $vocName) {
      $form['term']['voc_' . $vocId] = [
        '#title' => $vocName,
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#selection_settings' => [
          'target_bundles' => [$vocId],
          'match_operator' => 'STARTS_WITH',
        ],
        '#tags' => TRUE,
        '#multiple' => TRUE,
        '#process_default_value' => TRUE,
        '#default_value' => isset($allTermIds[$vocId]) ? $this->getTermStorage()->loadMultiple($allTermIds[$vocId]) : [],
        '#size' => 80,
        '#maxlength' => 1024,
        '#states' => [
          'visible' => [
            [':input[name="settings[term][' . self::CFG_AUTO_DETECT . ']"]' => ['checked' => FALSE]],
          ],
        ],
      ];
    }

    $form['term'][self::CFG_NAME_EXCLUDE] = [
      '#title' => t('Exclude these terms?'),
      '#description' => t('Check to exclude instead of include the terms.'),
      '#type' => 'checkbox',
      '#default_value' => $this->isExcluding(),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          [':input[name="settings[term][' . self::CFG_AUTO_DETECT . ']"]' => ['checked' => FALSE]],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitBlockForm(array &$form, FormStateInterface $form_state) {
    $this->block->setConfigurationValue(self::CFG_AUTO_DETECT, $form_state->getValue(['term', self::CFG_AUTO_DETECT]));

    $tids = [];
    foreach ($this->vocabularies as $vocId => $vocName) {
      $terms = $form_state->getValue(['term', 'voc_' . $vocId]);
      if (!empty($terms)) {
        foreach ($terms as $term) {
          $tids[$vocId][] = (int) $term['target_id'];
        }
      }
    }
    $this->block->setConfigurationValue(self::CFG_NAME_TERMS, $tids);
    $this->block->setConfigurationValue(self::CFG_NAME_EXCLUDE, $form_state->getValue(['term', self::CFG_NAME_EXCLUDE]));
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery(SelectInterface $query, $isCountQuery) {
    if (!$this->block instanceof NodeListBlockBase) {
      return;
    }

    $termIds = $this->getTermIds();
    if (empty($termIds)) {
      return;
    }
    $subQuery = $this->block->getSelectForTable('taxonomy_index', 'ti')
      ->fields('ti', ['nid']);
    $subQuery->condition('ti.tid', $termIds, 'IN');

    $query->condition('nfd.nid', $subQuery, ($this->isExcluding() ? 'NOT IN' : 'IN'));
  }

  /**
   * Are we auto-detecting the term?
   *
   * @return bool
   *   TRUE when auto-detect is enabled, FALSE for manual selection.
   */
  private function isAutoDetect() {
    return isset($this->configuration[self::CFG_AUTO_DETECT]) ? $this->configuration[self::CFG_AUTO_DETECT] : TRUE;
  }

  /**
   * Are we excluding terms?
   *
   * @return bool
   *   TRUE when excluding, FALSE for default including.
   */
  private function isExcluding() {
    return isset($this->configuration[self::CFG_NAME_EXCLUDE]) ? $this->configuration[self::CFG_NAME_EXCLUDE] : FALSE;
  }

  /**
   * Gets the storage for terms.
   *
   * @return \Drupal\taxonomy\TermStorageInterface
   *   The term storage.
   */
  private function getTermStorage() {
    if (!$this->block instanceof NodeListBlockBase) {
      throw new Exception('Block is not an instance of NodeListBlockBase but ' . get_class($this));
    }
    return $this->block->getEntityTypeManager()->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPrimaryInfo() {
    if ($this->isAutoDetect()) {
      return t('Auto-detect terms');
    }

    $configTermIds = isset($this->configuration[self::CFG_NAME_TERMS]) ? $this->configuration[self::CFG_NAME_TERMS] : [];
    if (empty($configTermIds)) {
      return NULL;
    }

    $data = [];
    foreach ($configTermIds as $vocId => $tids) {
      $terms = $this->getTermStorage()->loadMultiple($tids);
      $termLabels = array_map(function (EntityInterface $term) {
        return $term->label();
      }, $terms);
      $data[] = $this->vocabularies[$vocId] . ': ' . implode(', ', $termLabels);
    }

    return ($this->isExcluding() ? t('Exclude') . ': ' : '') . implode(' - ', $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminSecondaryInfo() {
    return NULL;
  }

  /**
   * Gets the taxonomy term IDs relevant for this block.
   *
   * @return int[]
   *   A list of term IDs to use as filter for the result.
   */
  protected function getTermIds() {
    if ($this->isAutoDetect()) {
      $term = static::getCurrentTermByRoute();
      if ($term instanceof TermInterface) {
        return $this->expandToAllTermIds($term->id());
      }
      return [];
    }

    $configTermIds = isset($this->configuration[self::CFG_NAME_TERMS]) ? $this->configuration[self::CFG_NAME_TERMS] : [];
    $termIds = [];
    foreach ($configTermIds as $tids) {
      $termIds = array_merge($termIds, $tids);
    }
    return $termIds;
  }

  /**
   * Gets the term for the current page based on the current route.
   *
   * @return \Drupal\taxonomy\TermInterface|false
   *   The term or FALSE when we aren't viewing a term page.
   */
  public static function getCurrentTermByRoute() {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['pe_extended_current_term_by_route'] = &drupal_static(__FUNCTION__);
    }
    $term = &$drupal_static_fast['pe_extended_current_term_by_route'];
    if (!isset($term)) {
      $routeMatch = \Drupal::routeMatch();
      if ($routeMatch->getRouteName() === 'entity.taxonomy_term.canonical') {
        $term = $routeMatch->getParameter('taxonomy_term');
        if ($term === NULL) {
          $term = FALSE;
        }
      }
      else {
        $term = FALSE;
      }
    }
    return $term;
  }

  /**
   * Gets an array of IDs of the children of the given term.
   *
   * NOTE: This will also include the ID of the given term and only fetch the
   * direct children (1 level below) of the given term.
   *
   * @param int $termId
   *   The term ID to get the children for.
   *
   * @return int[]
   *   Term ID + list of children IDs.
   */
  public function expandToAllTermIds($termId) {
    if (empty($termId)) {
      return [];
    }

    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['pe_extended_term_children'] = &drupal_static(__FUNCTION__, []);
    }
    $expandedIds = &$drupal_static_fast['pe_extended_term_children'];
    if (!isset($expandedIds[$termId])) {
      $childIds = array_keys($this->getTermStorage()->loadChildren($termId));
      $expandedIds[$termId] = array_merge([$termId], $childIds);
    }
    return $expandedIds[$termId];
  }

}
