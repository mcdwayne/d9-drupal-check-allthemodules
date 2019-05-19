<?php
namespace Drupal\taxonews;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

class Taxonews {
  const PATH_SETTINGS = '/admin/structure/taxonomy/taxonews';

  /**
   * Names of persistent variables
   */
  const VAR_LIFETIME       = 'taxonews_lifetime';
  const VAR_MAX_ROWS       = 'taxonews_max_rows';
  const VAR_SHOW_NAME      = 'taxonews_show_name';
  const VAR_SHOW_ARCHIVE   = 'taxonews_show_archive';
  const VAR_SHOW_EMPTY     = 'taxonews_show_empty';
  const VAR_EMPTY_MESSAGES = 'taxonews_empty_messages';
  const VAR_FEED           = 'taxonews_feed';
  const VAR_HEAD_FEEDS     = 'taxonews_head_feeds';
  const VAR_PONDERATED     = 'taxonews_ponderated';
  const VAR_VOCABULARY     = 'taxonews_vid';

  /**
   * Default values for persistent variables
   *
   */
  const DEF_FEED           = TRUE;
  const DEF_HEAD_FEEDS     = NULL;
  const DEF_LIFETIME       = 30;
  const DEF_MAX_ROWS       =  5;
  const DEF_PONDERATED     =  0;
  const DEF_SHOW_ARCHIVE   = TRUE;
  const DEF_SHOW_EMPTY     = TRUE;
  const DEF_SHOW_NAME      = TRUE;
  const DEF_VOCABULARY     = NULL;

  /**
   * @var \Drupal\Core\Block\BlockManager
   *
   * We depend on the concrete block manager because we need two of its interfaces:
   * - BlockManagerInterface
   * - CachedDiscoveryManager
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ConfigFactoryInterface $config, ModuleHandlerInterface $module_handler, BlockManagerInterface $block_manager) {
    $this->config = $config;
    $this->moduleHandler = $module_handler;
    $this->blockManager = $block_manager;
  }

  /**
   * Helper for configuration access.
   *
   * @param string $key
   *
   * @return \Drupal\Core\Config\Config
   */
  public function configGet($key) {
    return $this->config->get('taxonews.settings')->get($key);
  }

  /**
   * Implement the 4.x hook_settings().
   *
   * @return array settings form
   */
  public function adminSettings() {
    $form = array();

//     $sq = 'SELECT v.vid, v.name, v.description '
//         . 'FROM {taxonomy_vocabulary} v '
//         . 'ORDER BY 2, 1 ' ;
//     $result = db_query($sq);
//     $vocabularies = array();
    $vocabularies = entity_load_multiple('taxonomy_vocabulary');
    array_walk($vocabularies, function (&$vocabulary, $vid) {
      $vocabulary = t('@name: @description', array(
        '@name' => $vocabulary->name,
        '@description' => $vocabulary->description,
      ));
    });
    $settings = $this->config->get('taxonews.settings');

    $form[self::VAR_VOCABULARY] = array(
      '#type'             => 'select',
      '#title'            => t('Vocabularies'),
      '#default_value'    => $settings->get(self::VAR_VOCABULARY), // , self::DEF_VOCABULARY),
      '#options'          => $vocabularies,
      '#description'      => t('Vocabularies to be used for the generation of blocks. A block will be defined for each term in each vocabulary selected here.'),
      '#required'         => TRUE,
      '#size'             => count($vocabularies),
      '#multiple'         => TRUE,
    );

    $form[self::VAR_PONDERATED] = array(
      '#type'             => 'select',
      '#title'            => t('Ponderate sorting by'),
      '#options'          => array(
        0 => t('Creation date'),
      ),
    );
    if (\Drupal::moduleHandler()->moduleExists('statistics')) {
      $form[self::VAR_PONDERATED]['#options'] += array(
        1 => t('Total views'),
        2 => t('Daily views'),
      );
      $form[self::VAR_PONDERATED] += array(
        '#default_value'  => $settings->get(self::VAR_PONDERATED), // , self::DEF_PONDERATED),
        '#description'    => t('Sort displayed news by descending creation date, possibly ponderated by total number of views and daily views. This ponderation only affects sorting, not news selection, which are always selected by creation date.'),
      );
    }
    else {
      $form[self::VAR_PONDERATED] += array(
        '#default_value'  => self::DEF_PONDERATED,
        '#description'    => t('Sort displayed news by descending creation date. Additional sortings will be available if you !enable. It is currently disabled.',
          array('!enable' => \Drupal::l(t('enable statistics.module'), Url::fromRoute('system.modules_list')))),
      );
    }

    $form[self::VAR_LIFETIME] = array(
      '#type'             => 'textfield',
      '#title'            => t('Lifetime of news before expiration'),
      '#default_value'    => $settings->get(self::VAR_LIFETIME), // , self::DEF_LIFETIME),
      '#size'             => 3,
      '#max_length'       => 3,
      '#description'      => t('The number of days a news item remains displayed in blocks. If set to 0, news never expire.'),
      '#required'         => TRUE,
    );
    $form[self::VAR_MAX_ROWS] = array(
      '#type'             => 'textfield',
      '#title'            => t('Maximum number of rows per block'),
      '#default_value'    => $settings->get(self::VAR_MAX_ROWS), // , self::DEF_MAX_ROWS),
      '#size'             => 3,
      '#max_length'       => 3,
      '#description'      => t('The maximum number of rows that the builtin theme will display in a block.'),
      '#required'         => TRUE
    );
    $form[self::VAR_FEED] = array(
      '#type'            => 'checkbox',
      '#title'           => t('Display RSS feed icon on taxonews blocks'),
      '#default_value'   => $settings->get(self::VAR_FEED), // , self::DEF_FEED),
      '#description'     => t('This setting allows taxonews to display a RSS feed icon on each non empty block. Default value is enabled.'),
    );
    $form[self::VAR_SHOW_NAME] = array(
      '#type'            => 'checkbox',
      '#title'           => t('Prepend taxonews module name in block list'),
      '#default_value'   => $settings->get(self::VAR_SHOW_NAME), // , self::DEF_SHOW_NAME),
      '#description'     => t('This allows the modules to appear grouped on the block list at Administer/Blocks, to avoid cluttering.'),
    );
    $form[self::VAR_SHOW_ARCHIVE] = array(
      '#type'            => 'checkbox',
      '#title'           => t('Include "Archive" link to older articles in block'),
      '#default_value'   => $settings->get(self::VAR_SHOW_ARCHIVE), // , self::DEF_SHOW_ARCHIVE),
      '#description'     => t('If articles matching the term exist, but cannot be placed in the block, for instance if they are too old, add an "Archive" link at the end of the block. The link will not be shown if no matching article exists.'),
    );
    $form[self::VAR_SHOW_EMPTY] = array(
      '#type'            => 'checkbox',
      '#title'           => t('Include blocks with currently no matching nodes in the blocks list'),
      '#default_value'   => $settings->get(self::VAR_SHOW_EMPTY), // , self::DEF_SHOW_EMPTY),
    );
    $form['advanced'] = array(
      '#type'            => 'fieldset',
      '#collapsible'     => TRUE,
      '#collapsed'       => TRUE,
      '#title'           => t('Advanced settings'),
    );

    return $form;
    //return system_settings_form($form);
  }

  /**
   * Is there any published data matching that term beyond the block ?
   *
   * @param int $tid
   * @param array $nids Array of displayed nodes, to ignore
   * @return boolean
   *
   * The function expects the nids of the already displayed nodes
   * to be the keys of the $items array, so it can ignore them.
   *
   * Implementation note: the normal way to count field instances is through
   * field_attach_query(), but taxonomy.module has a special denormalized
   * table taxonomy_index which we can use for more speed.
   */
  public function archiveExists($tid, $items = array()) {
    $query = db_select('taxonomy_index', 'ti');
    $query->addExpression('COUNT(ti.nid)');
    $count = $query
      ->addTag('node_access')
      ->condition('ti.tid', $tid)
      ->execute()->fetchCol();
    $ret = $count[0] > count($items);
    return $ret;
  }

  /**
   * Configure the taxonews block identified by $delta
   *
   * @param mixed $delta
   * @return array
   */
  public function blockConfigure($delta) {
    $description = t('By default, blocks without matching content are not displayed. This setting allows you to force a static content.');
    $form = array();

    $form['Just a check'] = array(
      '#title' => t('just a check'),
      '#type' => 'textfield',
      '#default_value' => $delta,
    );

    $config = $this->config->get('taxonews.settings');

    // No form for invalid deltas
    $term = Term::load($delta);
    $ar_vocabularies = $config->get(self::VAR_VOCABULARY); // , self::DEF_VOCABULARY);
    if (!is_object($term) || !isset($term->vid) || !is_array($ar_vocabularies) || !in_array($term->vid, $ar_vocabularies)) {
      return $form;
    }

    $form[self::VAR_EMPTY_MESSAGES] = array(
      '#title'         => t('Text to be displayed if block has no matching content'),
      '#type'          => 'textfield',
      '#default_value' => self::getEmptyMessages($delta),
      '#size'          => 60,
      '#max_length'    => 60,
      '#description'   => $description,
    );

    $head_feeds = $config->get(self::VAR_HEAD_FEEDS); // Default: self::DEF_HEAD_FEEDS);
    $show_feed = $config->get(self::VAR_FEED); // , self::DEF_FEED)
    $show_feed_label = $show_feed ? t('Enabled') : t('Disabled');

    $in = isset($in_head_feeds[$delta]);
    $form[self::VAR_HEAD_FEEDS . '-' . $delta] = array(
      '#title'        => t('Included in head feeds'),
      '#type'         => 'checkbox',
      '#description'  => t('Include the feed for this block in the page-level feeds if Taxonews feeds are enabled (currently: @feed)', array(
        '@feed' => $show_feed_label)),
      '#default_value'=> $in,
    );

    dsm($form, __METHOD__);
    return $form;
  }

  /**
   * Generate block list for H. _block_info().
   *
   * @return array
   * @see taxonews_block()
   */
  public function blockInfo() {
    $arBlocks = array() ;
    $ar_terms = self::getTerms();
    if (empty($ar_terms)) {
      $modulePath = drupal_get_path('module', 'taxonews');
      drupal_set_message(
        t('WARNING: You will not be able to configure taxonews blocks until you !configure and !define in the taxonews vocabularies. You might want to refer to !install.',
          array(
            '!configure' => l(t('configure taxonews'), self::PATH_SETTINGS),
            '!define'    => l(t('define terms'), 'admin/structure/taxonomy'),
            '!install'   => l('INSTALL.txt', "$modulePath/INSTALL.txt")
            )
          ),
        'error');
      unset($modulePath);
      return $arBlocks; // No need to run the following code: there is no block
    }

    $settings = $this->config;

    $prefix = $settings->get(self::VAR_SHOW_NAME); // , self::DEF_SHOW_NAME)
    $prefix_text = $prefix ? 'Taxonews/' : '' ;
    $showEmpty = $settings->get(self::VAR_SHOW_EMPTY); // , self::DEF_SHOW_EMPTY);
    $sq = 'SELECT ti.tid, COUNT(ti.nid) AS cnt '
        . 'FROM {taxonomy_index} ti '
        . 'INNER JOIN {node} n ON ti.nid = n.nid '
        . 'WHERE n.status = 1 AND ti.tid IN (:terms) '
        . 'GROUP BY 1 ';
    $arCounts = array();
    $result = db_query($sq, array(':terms' => array_keys($ar_terms)));
    foreach ($result as $item) {
      $arCounts[$item->tid] = $item->cnt;
    }

    foreach ($ar_terms as $tid => $term) {
      if ((!isset($arCounts[$tid]) || ($arCounts[$tid] == 0)) && !$showEmpty) {
        continue; // Do not display the block, do not increment the counter
      }
      $arBlocks[$tid] = array() ;
      $arBlocks[$tid]['info'] = "{$prefix_text}$term->vocabulary_name/$term->name" ;
      $arBlocks[$tid]['cache'] = DRUPAL_CACHE_GLOBAL;
      $arBlocks[$tid]['pages'] = "admin\nadmin/*";
    }

    return $arBlocks ;
  }

  /**
   * Save the configuration (i.e. the text) of the selected block.
   *
   * @param mixed $delta Usually an int
   * @param $edit The edit form fields
   * @return void
   * @see hook_block()
   */
  public function blockSave($delta, $edit) {
    $ar_terms = self::getTerms() ;
    if (array_key_exists($delta, $ar_terms)) {
      $arEmptyMessages = self::getEmptyMessages();
      $arEmptyMessages[$delta] = $edit[self::VAR_EMPTY_MESSAGES];
      variable_set(self::VAR_EMPTY_MESSAGES, $arEmptyMessages);
    }
    $head_feeds = config('taxonews.settings')->get(self::VAR_HEAD_FEEDS); // , self::DEF_HEAD_FEEDS);
    if (!isset($head_feeds)) {
      $head_feeds = array();
    }
    if (isset($edit['delta'])) {
      $head_feeds[$delta] = $edit[self::VAR_HEAD_FEEDS . '-' . $edit['delta']];
    }
    variable_set(self::VAR_HEAD_FEEDS, $head_feeds);
  }

  /**
   * Generate block contents for the passed-in delta.
   *
   * Note: 86400 = 24*60*60 = seconds in one day
   *
   * @param mixed $delta Drupal block delta
   * @return string HTML
   */
  public function blockView($delta) {
    dsm($delta);
    return $delta;
    $ar_terms = self::getTerms();
    if (!array_key_exists($delta, $ar_terms)) {
      return array('subject' => NULL, 'content' => NULL);
    }

    $settings = $this->config;

    /**
     * ponderation must only be taken into account if statistics module exists
     * because the module may have been online, allowing ponderation to be set,
     * then removed, causing stats to no longer be updated
     */
    $ponderation = $this->moduleHandler->moduleExists('statistics')
      ? $settings->get(self::VAR_PONDERATED) // , self::DEF_PONDERATED)
      : self::DEF_PONDERATED;

    /**
     * There's a small trick for case 0|default: n.created is not a views count, but can
     * be used exactly like one: more recent nodes will have a higher value in
     * this field, so we can sort on it for display just like we sort on actual
     * view counts otherwise. That way we only have one sort rule for all three
     * different cases.
     */
    switch ($ponderation) {
      case 1: // by total views
        $sq = 'SELECT n.nid, '
            . '  nc.totalcount AS criterium '
            . 'FROM {node} n '
            . '  INNER JOIN {taxonomy_index} ti ON n.nid = ti.nid '
            . '  LEFT JOIN {node_counter} nc on n.nid = nc.nid ' // Some nodes may never have been counted yet
            . '  /* ignore current time: :requesttime */ ';
        break;

      case 2:  // by daily views since creation.
        $sq = 'SELECT n.nid, '
            . '  nc.totalcount*86400/(:requesttime - n.created) AS criterium '
            . 'FROM {node} n '
            . '  INNER JOIN {taxonomy_index} ti ON n.nid = ti.nid '
            . '  LEFT JOIN {node_counter} nc on n.nid = nc.nid ';  // Some nodes may never have been counted yet
        break;

      case 0: // not ponderated
      default: // ignore invalid values
        $sq = 'SELECT n.nid, '
            . '  n.created AS criterium '
            . 'FROM {node} n '
            . '  INNER JOIN {taxonomy_index} ti ON n.nid = ti.nid '
            . '  /* ignore current time: :requesttime */ ';
        break;
    }

    $sq .=    'WHERE ti.tid = :tid '
            . '  AND (n.created > :creation) '
            . '  AND (n.status = 1) '
            . '  ORDER BY n.created DESC, n.changed DESC ';

    $lifetime = $settings->get(self::VAR_LIFETIME); // , self::DEF_LIFETIME);
    $creation = $lifetime
      ? REQUEST_TIME - 86400 * $lifetime
      : 0;

    $ret   = array();
    $items = array();
    if ($result = db_query_range($sq,
      0, $settings->get(self::VAR_MAX_ROWS), // , self::DEF_MAX_ROWS), // range
      array(
        ':requesttime' => REQUEST_TIME,
        ':tid'         => $ar_terms[$delta]->tid,
        ':creation'    => $creation,         // current time, tid, lifetime
      ))) {
      $stats = array();
      foreach ($result as $item) {
        $stats[$item->nid] = $item;
      }
      $nodes = Node::loadMultiple(array_keys($stats));
      $items = array();
      foreach ($nodes as $nid => $node) {
        $items[$nid] = array(
          'criterium' => $stats[$nid]->criterium,
          'node' => $node,
          'link' => l(filter_xss($node->title), 'node/' . $nid),
        );
      }
      uasort($items, array('Taxonews', 'sortView')); // sort descending
    }
    $term = Term::load($ar_terms[$delta]->tid);
    $ret['subject'] = $term->name;
    if (count($items) == 0) {
      $items = NULL;
    }
    $ret['content'] = theme('taxonews_block_view', array(
      'delta'    => $delta,
      'ar_items' => $items,
      'tid'      => $ar_terms[$delta]->tid,
    ));
    return $ret;
  }

  public function cacheFlush() {
    $this->blockManager->clearCachedDefinitions();
  }

  protected function buildPluginId(TermInterface $term) {
    return 'taxonews_block:' . $term->getVocabularyId() . ':' . $term->id();
  }

  /**
   * Delete existing blocks for terms not in allowed vocabularies.
   *
   * These can happen when a previously allowed vocabulary, for which some
   * blocks had been placed, ceases to be allowed. The placed blocks must be
   * removed because their plugin can no longer be instanciated.
   *
   * @param array $allowed_vocabularies
   *   An array of allowed vocabulary ids.
   *
   * @return void
   */
  public function deleteExcessBlocks($allowed_vocabularies = array()) {
    dvm($allowed_vocabularies, __METHOD__);

    // Find all taxonews Block entities.
    $ids = \Drupal::entityQuery('block')
      ->condition('id', ".taxonews_", 'CONTAINS')
      ->execute();

    if (empty($allowed_vocabularies)) {
      $allowed_existing_ids = array();
    }
    else {
      // Find allowed Taxonews Block entities.
      $q = \Drupal::entityQuery('block');
      foreach ($allowed_vocabularies as $vid) {
        $q->condition('id', ".taxonews_{$vid}_", 'CONTAINS');
      }
      $allowed_existing_ids = $q->execute();
    }

    $excess = array_diff($ids, $allowed_existing_ids);
    entity_delete_multiple('block', $excess);
    $this->cacheFlush();
  }

  public function disallowVocabulary(VocabularyInterface $vocabulary) {
    dsm($vocabulary, __METHOD__);
    $old_allowed_vocabularies = config('taxonews.settings')->get('allowed_vocabularies');
    $allowed_vocabularies = array_diff($old_allowed_vocabularies, array($vocabulary->get('vid')->value));
    dsm(get_defined_vars(), __METHOD__ .'/'. __LINE__);
    return $allowed_vocabularies;
  }

  /**
   * Return the list of messages to be issued when an empty taxonews blocks is to be built.
   *
   * @param mixed $delta
   * @return array
   */
  public function getEmptyMessages($delta = NULL) {
    static $arEmptyMessages;

    if (empty($arEmptyMessages)) {
      $arEmptyMessages = $this->config->get(self::VAR_EMPTY_MESSAGES); // , array());
    }

    $ret = isset($delta)
      ? (array_key_exists($delta, $arEmptyMessages) ? $arEmptyMessages[$delta] : NULL)
      : $arEmptyMessages;

    return $ret;
  }

  /**
   * Query for terms in the vocabularies selected in settings.
   *
   * @return array [tid, name]
   */
  public function getTerms($reset = FALSE) {
    static $ar_terms = array();

    if ($reset) {
      $ar_terms = array();
    }

    if (empty($ar_terms)) {
      $arVids = $this->config->get(self::VAR_VOCABULARY); // , array(self::DEF_VOCABULARY));
      $efq = \Drupal::entityQuery('taxonomy_term');
      $efq->condition('vid', $arVids);
      $tids = $efq->execute();
      $ar_terms = entity_load_multiple('taxonomy_term', array_keys($tids));
    }

    return $ar_terms;
  }

/**
   * Comparison callback for blockView()/uasort().
   *
   * This is needed to sort on criterium DESCENDING, hence the inverted sign
   * assignment on the result.
   *
   * @param array $x
   * @param array $y
   * @return int
   */
  private function sortView(array $x, array $y) {
    $diff = $x['criterium'] - $y['criterium'];
    if ($diff > 0) {
      $ret = -1;
    }
    elseif ($diff == 0) {
      $ret = 0;
    }
    else {
      $ret = 1;
    }

    return $ret;
    }

  private function X_____code_below_ok_for_Drupal_8_________________________(){}
  /**
   * Rebuild cached definitions to account for the new term.
   *
   * @param TermInterface $term
   *
   * @todo optimize to build only one definition instead of all.
   */
  public function onTermCreate(TermInterface $term) {
    dsm('Inserting term "' . $term->label() . ' in vocabulary "' . $term->getVocabularyId() . '"."');
    $this->cacheFlush();
  }

  public function onTermDelete(Term $term) {
    dsm('Deleting term "'. $term->label() . '" from vocabulary "' . $term->getVocabularyId() . '"."');
    $plugin_id = $this->buildPluginId($term);
    $blocks = entity_load_multiple_by_properties('block', array('plugin' => $plugin_id));
    entity_delete_multiple('block', array_keys($blocks));
    dsm(get_defined_vars(), __METHOD__ .'/'. __LINE__);
    $this->cacheFlush();
  }

  /**
   * Rebuild cached definitions to account for the update term.
   *
   * @param TermInterface $term
   *
   * @todo optimize to update only one definition instead of all.
   */
  public function onTermUpdate(TermInterface $term) {
    dsm('Updating term "' . $term->label() . '" in vocabulary "' . $term->getVocabularyId() . '".');
    $this->cacheFlush();
  }

  /**
   * Rebuild cached definitions to account for terms in the new vocabulary.
   *
   * @param TermInterface $term
   *
   * @todo optimize to build only their definition instead of all.
   */
  public function onVocabularyCreate(VocabularyInterface $vocabulary)  {
    dsm('Inserting vocabulary "' . $vocabulary->label() . '"."');
    $terms = entity_load_multiple_by_properties('taxonomy_term', array('vid' => $vocabulary->id()));
    dsm($terms);
    // New vocabulary are normally created empty. Getting terms in this hook
    // would probably denote a race condition between web heads like this:
    // - web1 creates the vocabulary, commits it, invokes this hook
    // - web1 stalls on the entity_load_... query
    // - web2 creates a term in the now visible vocabulary without stalling
    // - web1 receives actual terms in the newly created vocabulary.
    if (!empty($terms)) {
      $this->cacheFlush();
    }
  }

  /**
   * Delete all blocks created for a deleted vocabulary and disallow it.
   *
   * @param VocabularyInterface $vocabulary
   */
  public function onVocabularyDelete(VocabularyInterface $vocabulary) {
    dsm('Deleting vocabulary "' . $vocabulary->label() . '"."');
    $allowed_vocabularies = $this->disallowVocabulary($vocabulary);
    dsm($allowed_vocabularies, __FUNCTION__ . " remaining voc");
    $this->deleteExcessBlocks($allowed_vocabularies);
  }

  /**
   * Rebuild cached definitions to account for terms in the new vocabulary.
   *
   * @param TermInterface $term
   *
   * @todo optimize to build only their definition instead of all.
   */
  public function onVocabularyUpdate(VocabularyInterface $vocabulary)  {
    dsm('Updating vocabulary "' . $vocabulary->label() . '"."');
    // Nothing to do ?
  }

} // end of class Taxonews
