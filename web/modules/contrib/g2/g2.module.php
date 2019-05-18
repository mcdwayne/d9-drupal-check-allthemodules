<?php

/**
 * @file
 * This defines a node-based glossary module, as opposed to the term-based
 * glossary.module
 *
 * @copyright 2005-2015 Frédéric G. Marand, for Ouest Systemes Informatiques.
 *
 * @TODO for D8, in decreasing prioritys
 *  - implement SettingsForm::validateForm() using Requirements
 *  - ensure theme is flushed whenever block.alphabar.row_length config is
 *    updated.
 *  - make g2_requirements() less verbose, at least on success.
 *  - find a way to add the title to the node.add route for ease of creation.
 * @todo TODO test wipes, rss
 *
 * @link http://wiki.audean.com/g2/choosing @endlink
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\g2\G2;
use Drupal\g2\Top;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * XML-RPC callback : returns alphabar data.
 *
 * @return string
 */
function _g2_alphabar() {
  /* @var \Drupal\g2\Alphabar $alphabar */
  $alphabar = \Drupal::service('g2.alphabar');
  $ret = array_map(function (GeneratedLink $link) {
    return "$link";
  }, $alphabar->getLinks());
  return $ret;
}

/**
 * XML-RPC callback : returns the current XML-RPC API version.
 *
 * @return int
 */
function _g2_api() {
  return G2::api();
}

/**
 * XML-RPC callback : returns a list of the latest n nodes.
 *
 * "Latest" nodes are identified by time of latest update.
 *
 * @param int|null $count
 *   The maximum number of entries to return
 *
 * @return array
 *   Note that the results are NOT filtered, and must be filtered when used.
 */
function _g2_latest($count = NULL) {
  $config = \Drupal::config('g2.settings');
  $service_max = $config->get('service.latest.max_count');
  $rpc_throttle = $config->get('rpc.server.throttle');
  $actual_max = $rpc_throttle * $service_max;

  // Limit extraction.
  if (empty($count) || ($count > $actual_max)) {
    $count = $actual_max;
  }

  /* @var \Drupal\g2\Latest $latest */
  $latest = \Drupal::service('g2.latest');
  $links = $latest->getLinks($count);
  $result = [];

  /* @var \Drupal\Core\GeneratedLink $link */
  foreach ($links as $link) {
    $result[] = $link->__toString();
  }
  return $result;
}

/**
 * Returns a list of the top n nodes as counted by statistics.module.
 *
 * - Unpublished nodes are not listed.
 * - Stickyness is ignored for ordering, but returned in the results for
 *   client-side ordering if needed.
 *
 * @param int|null $count
 *   Number or entries to return.
 * @param bool|null $daily_top
 *   Order by daily views if TRUE, otherwise by total views (default).
 *
 * @return array|NULL
 *   Statistics will empty without statistics module.
 *   Note that the title of the nodes is NOT filtered.
 */
function _g2_top($count = NULL, $daily_top = FALSE) {
  $config = \Drupal::config('g2.settings');
  $service_max = $config->get('service.top.max_count');
  $rpc_throttle = $config->get('rpc.server.throttle');
  $actual_max = $rpc_throttle * $service_max;

  // Limit extraction.
  if (empty($count) || ($count > $actual_max)) {
    $count = $actual_max;
  }

  /* @var \Drupal\g2\Top $top */
  $top = \Drupal::service('g2.top');
  $statistic = $daily_top ? Top::STATISTICS_DAY : Top::STATISTICS_TOTAL;
  $links = $top->getLinks($count, $statistic);
  $result = [];

  /* @var \Drupal\Core\GeneratedLink $link */
  foreach ($links as $link) {
    $result[] = $link->__toString();
  }
  return $result;
}

/**
 * Implement hook_help()
 */
function g2_help($route_name, RouteMatchInterface $route_match) {
  $result = '';
  switch ($route_name) {
    case 'help.page.g2':
      $result = t('<p>G2 defines a glossary service for Drupal sites.
       To compare it with the Glossary and Lexicon modules:</p>
       <ul>
         <li>G2 content is node-based, not term-based, provide node access control</li>
         <li>G2 leverages existing code from glossary for input filtering and node marking</li>
         <li>G2 RAM use does not significantly increase with larger entry counts, which makes is more suitable for larger glossaries</li>
         <li>G2 requests much less from the database than the default glossary</li>
         <li>G2 uses a "G2 Context" taxonomy vocabulary by default, but does not require it.</li>
         <li>G2 defines optional blocks</li>
         <li>G2 provides a client and server XML-RPC API</li>
         <li>G2 does not provide term feeds</li>
         </ul>');
      break;

    case 'entity.block.edit_form':
      /* @var \Drupal\block\Entity\Block $block */
      $block = $route_match->getParameter('block');
      $definition = $block->getPlugin()->getPluginDefinition();
      if ($definition['provider'] === 'g2') {
        $id = $block->getPluginId();
        $delta = \Drupal\Component\Utility\Unicode::substr($id, 3);
        $helps = [
          G2::DELTA_ALPHABAR => t('This block displays a clickable list of initials from the G2 glossary.'),
          G2::DELTA_RANDOM => t('This block displays a pseudo-random entry from the G2 glossary.'),
          G2::DELTA_TOP => t('This block displays a list of the most viewed entries in the G2 glossary.'),
          G2::DELTA_LATEST => t('This block displays a list of the most recently updated entries in the G2 glossary.'),
          G2::DELTA_WOTD => t('This block displays a once-a-day entry from the G2 glossary.'),
        ];
        $result = isset($helps[$delta]) ? $helps[$delta] : NULL;
      }
      break;
  }
  return $result;
}

/**
 * Implements hook_theme().
 */
function g2_theme() {
  $config = \Drupal::config('g2.settings');
  $ret = [
// Checked for D8.
    'g2_alphabar' => [
      'variables' => [
        'alphabar' => [],
        'row_length' => $config->get('block.alphabar.row_length'),
      ],
    ],
    'g2_entries' => [
      'variables' => [
        'raw_entry' => '',
        'entries' => [],
        'message' => NULL,
        'offer' => NULL,
      ],
    ],
    'g2_initial' => [
      'variables' => [
        'initial' => NULL,
        'entries' => [],
      ],
    ],
    'g2_main' => [
      'variables' => [
        'alphabar' => $config->get('service.alphabar.contents'),
        'text' => '',
      ],
    ],

    // --- Older versions ------------------------------------------------------
    'g2_random' => ['variables' => ['node' => NULL]],
    'g2_wotd' => ['variables' => ['node' => NULL]],

    'g2_body' => ['variables' => ['title' => '', 'body' => '']],
    'g2_period' => ['variables' => ['title' => '', 'period' => '']],
    'g2_teaser' => ['variables' => ['title' => '', 'teaser' => '']],
    'g2_field' => [
      'variables' => [
        'expansion' => '',
        'name' => '',
        'title' => '',
      ],
    ],
  ];
  return $ret;
}

/* ==== Code below this line has not yet been converted to D8 =============== */

/**
 * AJAX autocomplete for entry
 *
 * @see g2_menu()
 * @see g2_block()
 *
 * @param null|string $us_string The beginning of the entry
 *
 * @return None
 */
function _g2_autocomplete($us_string = NULL) {
  $matches = [];
  if (isset($us_string)) {
    $us_string = drupal_strtolower($us_string);
    $sq = 'SELECT n.nid, n.title, n.sticky '
      . 'FROM {node} n '
      . "WHERE LOWER(n.title) LIKE '%s%%' "
      . "  AND n.type = '%s' "
      . '  AND (n.status = 1)'
      . 'ORDER BY n.sticky DESC, LOWER(n.title) ASC';
    $sq = db_rewrite_sql($sq);
    $q = db_query_range($sq, $us_string, G2NODETYPE, 0, 10);
    while (is_object($o = db_fetch_object($q))) {
      $title = $o->sticky
        ? t('@title [@nid, sticky]', ['@title' => $o->title, '@nid' => $o->nid])
        : t('@title [@nid]', ['@title' => $o->title, '@nid' => $o->nid]);
      $matches[$title] = $o->title;
    }
  }
  drupal_json($matches);
  exit();
}

/**
 * Remove tags from a taxonomy array for non-G2-admins
 *
 * @param array $taxonomy
 *   An array of fully loaded terms (tid, vid, weight..)
 *
 * @return array
 */
function _g2_comb_taxonomy($taxonomy = []) {
  // Filter tag vocabularies for non-G2 admins if this is requested
  if (variable_get(G2VARNOFREETAGGING, G2DEFNOFREETAGGING) && !user_access(G2PERMADMIN)) {
    // No static cache here: there is already one in taxonomy_vocabulary_load().
    $vocabs = [];

    // We still hide the terms within freetagging vocabularies to allow partial display
    foreach ($taxonomy as $tid => $term) {
      // Is the vocabulary for the current term known ?
      $vid = $term->vid;
      if (!array_key_exists($vid, $vocabs)) {
        $vocab = taxonomy_vocabulary_load($vid);
        $vocabs[$vid] = $vocab->tags;
      }

      // If it's a tags vocab, hide its terms
      if (!empty($vocabs[$vid])) {
        unset($taxonomy[$tid]);
      }
    }
  }

  return $taxonomy;
}

/**
 * Return a formatted set of terms as links.
 *
 * Return a span containing links to taxonomy terms, or nothing
 * if node information contains no terms. The "node" passed must
 * contain full term information, not just tids.
 *
 * @param object $node
 *   Imitation of a node
 *
 * @return string
 */
function _g2_entry_terms($node) {
  $ret = NULL;
  if (!empty($node->taxonomy)) {
    $terms = [];
    foreach ($node->taxonomy as $tid => $term) {
      $terms[$tid] = [
        'href' => 'taxonomy/term/' . $term->tid,
        'title' => check_plain($term->name),
        'attributes' => ['rel' => 'tag'],
      ];
    }
    $ret = theme_links($terms);
  }
  return $ret;
}

/**
 * Translate glossary linking elements (<dfn>) to actual links)
 *
 * This function generates absolute links, for the benefit of the WOTD RSS feed
 * If this feed is not used, it is possible to use the (shorter) relative URLs
 * by swapping comments.
 *
 * @param string $entry An entry
 *
 * @return string HTML
 */
function _g2_filter_process($entry) {
  $entry = $entry[1]; // [0] is the original string
  $target = variable_get(G2VARREMOTEG2, G2DEFREMOTEG2);

  // If we are not using a remote glossary
  if ($target == G2DEFREMOTENO) {
    $target = G2PATHENTRIES;

    // No tooltips on remote glossaries: too slow
    if (variable_get(G2VARTOOLTIPS, G2DEFTOOLTIPS)) {
      $nodes = g2_entry_load($entry);
      $count = count($nodes);
      if ($count == 1) {
        $node = reset($nodes);
        $tooltip = check_markup($node->teaser, $nodes->format);
      }
      elseif ($count) {
        $tooltip = t('@count entries for @entry', [
          '@count' => $count,
          '@entry' => $entry,
        ]);
      }
      else {
        $tooltip = t('No such term.');
      }
    }
  }
  else {
    $tooltip = NULL;
  }

  $path = urlencode(G2::encodeTerminal($entry));
  $attributes = [
    'class' => 'g2-dfn-link',
  ];
  if (isset($tooltip)) {
    $attributes['title'] = $tooltip;
  }

  $ret = l($entry, $target . '/' . $path, [
    'absolute' => TRUE,
    'html' => FALSE,
    'attributes' => $attributes,
  ]);
  return $ret;
}

/**
 * Modify the default page title as built by Drupal.
 *
 * Tweaking $conf modifies only the live copy used by Drupal, not the stored
 * value as would be the case using variable_set.
 *
 * @return void
 *
 * @see \Drupal\g2\Controller\Main::indexAction()
 * */
function _g2_override_site_name() {
  if (variable_get(G2VARPAGETITLE, G2DEFPAGETITLE)) {
    global $conf;
    $conf['site_name'] = strtr(variable_get(G2VARPAGETITLE, G2DEFPAGETITLE),
      ['@title' => variable_get('site_name', 'Drupal')]);
  }
}

/**
 * Ancillary function for g2_block to return a pseudo-random entry.
 *
 * Entry is selected to be different from the current WOTD and, in the default
 * setting, from the latest pseudo-random result returned.
 *
 * Only works for glossaries with 3 entries or more.
 *
 * @return object
 *   Title / nid / teaser. Unfiltered contents.
 */
function _g2_random() {
  $wotd_nid = variable_get(G2VARWOTDENTRY, G2DEFWOTDENTRY);

  // Do we have a stored previous random to exclude ?
  $random = variable_get(G2VARRANDOMSTORE, G2DEFRANDOMSTORE)
    ? variable_get(G2VARRANDOMENTRY, G2DEFRANDOMENTRY)
    : ''; // We don't, so just avoid untitled nodes, which should not exist anyway.

  $sq = 'SELECT COUNT(*) cnt '
    . 'FROM {node} n '
    . "WHERE n.type = '%s' AND (n.status = 1) "
    . "  AND NOT (n.title = '%s' OR n.nid = %d)";
  $sq = db_rewrite_sql($sq);
  $q = db_query($sq, G2NODETYPE, $random, $wotd_nid);
  $n = db_result($q);

  $rand = mt_rand(0, $n - 1); // no need to mt_srand() since PHP 4.2

  // Select from the exact same list of nodes, assuming none was inserted/deleted in the meantime
  $sq = 'SELECT n.nid '
    . 'FROM {node} n '
    . '  INNER JOIN {node_revisions} v ON n.vid = v.vid '
    . "WHERE n.type = '%s' AND (n.status = 1) "
    . "  AND NOT (n.title = '%s' OR n.nid = %d) ";
  $sq = db_rewrite_sql($sq);
  $q = db_query_range($sq, G2NODETYPE, $random, $wotd_nid, $rand, 1);
  $nid = db_result($q);
  $node = node_load($nid);
  $node->taxonomy = variable_get(G2VARRANDOMTERMS, G2DEFRANDOMTERMS)
    ? _g2_comb_taxonomy($node->taxonomy)
    : [];

  if (variable_get(G2VARRANDOMSTORE, G2DEFRANDOMSTORE)) {
    variable_set(G2VARRANDOMENTRY, $node->title); // unfiltered
  }

  return $node;
}

/**
 * Counts the number of G2 referer entries.
 *
 * @todo check referer wipe: it may have been damaged in the D6 port
 *
 * @param object $node
 *
 * @return string HTML
 */
function _g2_referer_links($form_state, $node) {

// Build list of referers
  $nid = $node->nid;

  $header = [
    ['data' => t('Clicks'), 'field' => 'incoming', 'sort' => 'desc'],
    ['data' => t('Referer'), 'field' => 'referer'],
    ['data' => t('Related node')],
  ];

  // Can be generated for unpublished nodes by author or admin, so don't
  // filter on node.status = 1
  // The join is needed to avoid showing info about forbidden nodes, and
  // to allow some modules to interfere without breaking because they
  // assume "nid" only exists in {node}.
  $sq = "SELECT gr.referer, gr.incoming "
    . "FROM {g2_referer} gr "
    . "  INNER JOIN {node} n ON gr.nid = n.nid "
    . "WHERE gr.nid = %d "
    . tablesort_sql($header);
  $sq = db_rewrite_sql($sq);
  $q = db_query($sq, $nid);
  $rows = [];
  while (is_object($o = db_fetch_object($q))) {
    $sts = preg_match('/node\/(\d+)/', $o->referer, $matches);
    if ($sts) {
      $node = node_load($matches[1]);
      $title = l($node->title, 'node/' . $node->nid);
    }
    else {
      $title = NULL;
    }
    $rows[] = empty($o->referer)
      ? [$o->incoming, t('<empty>'), $title] // should never happen
      : [
        $o->incoming,
        l($o->referer, $o->referer, ['absolute' => TRUE]),
        $title,
      ];
  }
  if (empty($rows)) {
    $message = t('No referer found. Maybe you just cleaned the list ?');
  }
  else {
    $message = theme('table', $header, $rows);
  }


  // Build form from results
  $form = [];
  $form['links'] = [
    '#type' => 'markup',
    '#prefix' => t('<h3>Local referers for this node</h3>'),
    '#value' => $message,
  ];

  if (!empty($rows)) {
    $form['links']['#suffix'] = t('<p>WARNING: just because a click came from a node doesn\'t mean the node has a link.
    The click may have come from a block on the page. These stats are just a hint for editors.</p>');

    $form['wipe_target'] = [
      '#type' => 'value',
      '#value' => $nid,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Wipe referer info for this entry'),
    ];
  }

  return $form;
}

/**
 * Submit handler for _g2_referer_links().
 *
 * Use the wipe target to redirect to the wipe confirmation form. If we
 * hadn't been using a button for this link, we could just as well have
 * used a plain link.
 *
 * @param array $form
 * @param array $form_state
 *
 * @return void
 */
function _g2_referer_links_submit(&$form, &$form_state) {
  $form_state['redirect'] = 'g2/wipe/' . $form_state['values']['wipe_target'];
}

/**
 * Erase the referer counts on g2 entries
 *
 * Difference from the 4.7 version: it no longer includes a goto when erasing
 * all
 *
 * @FIXME 20090928 recheck
 *
 * @param int $nid
 *   Node from which to erase referers, or NULL to erase all g2 referers
 *
 * @return void
 */
function _g2_referer_wipe($nid = NULL) {
  if (isset($nid)) {
    $sq = 'DELETE from {g2_referer} WHERE nid = %d';
    db_query($sq, $nid);
    drupal_goto('node/' . $nid);
  }
  else {
    $sq = 'DELETE from {g2_referer}';
    db_query($sq);
  }
}

/**
 * Returns a structure for the WOTD.
 *
 * @param int $bodysize
 *
 * @return object title / nid / teaser
 *   Teaser and Body are returned already filtered, not stripped.
 */
function _g2_wotd($bodysize = 0) {
  // No need for a static: this function is normally never called twice.
  $nid = variable_get(G2VARWOTDENTRY, G2DEFWOTDENTRY);
  $node = node_load($nid);
  if (empty($node)) {
    return NULL;
  }

  if (variable_get(G2VARWOTDTERMS, G2DEFWOTDTERMS)) {
    $node->taxonomy = _g2_comb_taxonomy(taxonomy_node_get_terms($node));
  }

  $node->teaser = check_markup($node->teaser, $node->format);

  $node->truncated = FALSE;
  if ($bodysize > 0) {
    $node->raw_body = $node->body; // save the raw version
    if (drupal_strlen($node->body) > $bodysize) {
      $node->truncated = TRUE;
      $body = drupal_substr($node->body, 0, $bodysize);
      $node->body = check_markup($body, $node->format);
    }
  }

  return $node;
}

/**
 * Generate an RSS feed containing the latest WOTD.
 *
 * @return string XML in UTF-8 encoding
 */
function _g2_wotd_feed() {
  global $base_url;

  $channelinfo = [
    // Link element:  Drupal 4.7->6 defaults to $base url
    // Language: Drupal 6 defaults to to $language->language

    // Title: Drupal 6 defaults to site name
    'title' => variable_get(G2VARWOTDFEEDTITLE, variable_get(G2VARWOTDTITLE, G2DEFWOTDTITLE)),

    // Description: Drupal defaults to $site_mission
    'description' => strtr(variable_get(G2VARWOTDFEEDDESCR, G2DEFWOTDFEEDDESCR), ['!site' => $base_url]),
    'managingEditor' => variable_get('site_mail', 'nobody@example.com'),
  ];

  $items = [variable_get(G2VARWOTDENTRY, G2DEFWOTDENTRY)];
  $ret = node_feed($items, $channelinfo);
  return $ret;
}

/**
 * Implements hook_access().
 */
function g2_access($op /* , $node, $account */) {
  switch ($op) {
    case 'create':
    case 'delete':
    case 'update':
      $ret = user_access(G2PERMADMIN);
      break;

    case 'view':
      $ret = user_access(G2PERMVIEW);
      break;
  }

  return $ret;
}

/**
 * Implements hook_block().
 */
function g2_block($op = 'list', $delta = 0, $edit = []) {
  if ($op == 'list') {
    $blocks = [];
    $blocks[G2::DELTA_RANDOM]['info'] = variable_get('g2_random_info', t('G2 Random'));
    $blocks[G2::DELTA_WOTD]['info'] = variable_get('g2_wotd_info', t('G2 Word of the day'));

    // Else it couldn't be random.
    $blocks[G2::DELTA_RANDOM]['cache'] = BLOCK_NO_CACHE;
    $blocks[G2::DELTA_WOTD]['cache'] = BLOCK_CACHE_GLOBAL;
    $ret = $blocks;
  }
  elseif ($op == 'configure') {
    switch ($delta) {
      case G2::DELTA_RANDOM:
        $form[G2VARRANDOMSTORE] = [
          '#type' => 'checkbox',
          '#title' => t('Store latest random entry'),
          '#default_value' => variable_get(G2VARRANDOMSTORE, G2DEFRANDOMSTORE),
          '#description' => t('When this setting is TRUE (default value),
           the latest random value is kept in the DB to avoid showing the same pseudo-random
           value on consecutive page displays.
           For small sites, it is usually best to keep it saved.
           For larger sites, unchecking this setting will remove one database write with locking.'),
        ];
        $form[G2VARRANDOMTERMS] = [
          '#type' => 'checkbox',
          '#title' => t('Return taxonomy terms for the current entry'),
          '#default_value' => variable_get(G2VARRANDOMTERMS, G2DEFRANDOMTERMS),
          '#description' => t('The taxonomy terms will be returned by XML-RPC and made available to the theme.
           Default G2 themeing will display them.'),
        ];
        break;

      case G2::DELTA_WOTD:
        /**
         * Convert nid to "title [<nid>]" even if missing
         *
         * @see _g2_autocomplete()
         */
        $nid = variable_get(G2VARWOTDENTRY, G2DEFWOTDENTRY);
        $node = node_load($nid);
        if (empty($node)) {
          $node = new stdClass();
          $node->nid = 0;
          $node->title = NULL;
        }
        $form[G2VARWOTDENTRY] = [
          '#type' => 'textfield',
          '#title' => t('Entry for the day'),
          '#maxlength' => 60,
          '#autocomplete_path' => G2PATHAUTOCOMPLETE,
          '#required' => TRUE,
          // !title: we don't filter since this is input, not output,
          // and can contain normally escaped characters, to accommodate
          // entries like "<", "C#" or "AT&T"
          '#default_value' => t('!title [@nid]', [
            '!title' => $node->title,
            '@nid' => $nid,
          ]),
        ];
        $form[G2VARWOTDBODYSIZE] = [
          '#type' => 'textfield',
          '#title' => t('Number of text characters to be displayed from entry definition body, if one exists'),
          '#size' => 4,
          '#maxlength' => 4,
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDBODYSIZE, G2DEFWOTDBODYSIZE),
        ];
        $form[G2VARWOTDAUTOCHANGE] = [
          '#type' => 'checkbox',
          '#title' => t('Auto-change daily'),
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDAUTOCHANGE, G2DEFWOTDAUTOCHANGE),
          '#description' => t('This setting will only work if cron or poormanscron is used.'),
        ];
        $form[G2VARWOTDTERMS] = [
          '#type' => 'checkbox',
          '#title' => t('Return taxonomy terms for the current entry'),
          '#default_value' => variable_get(G2VARWOTDTERMS, G2DEFWOTDTERMS),
          '#description' => t('The taxonomy terms will be returned by XML-RPC and made available to the theme.
           Default G2 themeing will display them.'),
        ];
        $form[G2VARWOTDTITLE] = [
          '#type' => 'textfield',
          '#title' => t('Title for the WOTD block'),
          '#description' => t('This title is also the default title for the WOTD feed, if none is defined. It is overridden by the default Drupal block title, if the latter is not empty.'),
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDTITLE, G2DEFWOTDTITLE),
        ];

        $form['wotd_feed'] = [
          '#type' => 'fieldset',
          '#title' => t('RSS Feed'),
        ];
        $form['wotd_feed'][G2VARWOTDFEEDLINK] = [
          '#type' => 'checkbox',
          '#title' => t('Display feed link'),
          '#default_value' => variable_get(G2VARWOTDFEEDLINK, G2DEFWOTDFEEDLINK),
          '#description' => t('Should the theme display the link to the RSS feed for this block ?'),
        ];
        $form['wotd_feed'][G2VARWOTDFEEDTITLE] = [
          '#type' => 'textfield',
          '#title' => t('The feed title'),
          '#size' => 60,
          '#maxlength' => 60,
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDFEEDTITLE, variable_get(G2VARWOTDTITLE, G2DEFWOTDTITLE)),
          '#description' => t('The title for the feed itself.
           This will typically be used by aggregators to remind users of the feed and link to it.
           If nulled, G2 will reset it to the title of the block.'),
        ];
        $form['wotd_feed'][G2VARWOTDFEEDAUTHOR] = [
          '#type' => 'textfield',
          '#title' => t('The feed item author'),
          '#size' => 60,
          '#maxlength' => 60,
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDFEEDAUTHOR, G2DEFWOTDFEEDAUTHOR),
          '#description' => t('The author name to be included in the feed entries.
        In this string @author will be replaced by the actual author information.'),
        ];
        $form['wotd_feed'][G2VARWOTDFEEDDESCR] = [
          '#type' => 'textfield',
          '#title' => t('The feed description'),
          '#size' => 60,
          '#maxlength' => 60,
          '#required' => TRUE,
          '#default_value' => variable_get(G2VARWOTDFEEDDESCR, G2DEFWOTDFEEDDESCR),
          '#description' => t('The description for the feed itself.
        This will typically be used by aggregators when describing the feed prior to subscription.
        It may contain !site, which will dynamically be replaced by the site base URL.'),
        ];
        break;

      default:
        break;
    }
    $ret = $form;
  }
  elseif ($op == 'save') {
    $ret = NULL;
    switch ($delta) {
      case G2::DELTA_RANDOM:
        variable_set(G2VARRANDOMSTORE, $edit[G2VARRANDOMSTORE]);
        variable_set(G2VARRANDOMTERMS, $edit[G2VARRANDOMTERMS]);
        break;

      case G2::DELTA_WOTD:
        // Convert "some title [<nid>, sticky]" to nid
        $entry = $edit[G2VARWOTDENTRY];
        $matches = [];
        $count = preg_match('/.*\[(\d*).*\]$/', $entry, $matches);
        $nid = $count ? $matches[1] : 0;

        variable_set(G2VARWOTDENTRY, $nid);
        variable_set(G2VARWOTDBODYSIZE, $edit[G2VARWOTDBODYSIZE]);
        variable_set(G2VARWOTDAUTOCHANGE, $edit[G2VARWOTDAUTOCHANGE]);
        variable_set(G2VARWOTDDATE, mktime());
        variable_set(G2VARWOTDTERMS, $edit[G2VARWOTDTERMS]);
        variable_set(G2VARWOTDFEEDLINK, $edit[G2VARWOTDFEEDLINK]);
        variable_set(G2VARWOTDFEEDTITLE, $edit[G2VARWOTDFEEDTITLE]);
        variable_set(G2VARWOTDFEEDDESCR, $edit[G2VARWOTDFEEDDESCR]);
        variable_set(G2VARWOTDFEEDAUTHOR, $edit[G2VARWOTDFEEDAUTHOR]);
        variable_set(G2VARWOTDTITLE, $edit[G2VARWOTDTITLE]);
        break;

      default:
        break;
    }
  }
  elseif ($op == 'view') {
    // watchdog('g2', "hook_block/view/$delta");
    switch ($delta) {
      case G2::DELTA_RANDOM:
        $block['subject'] = t('Random G2 glossary entry');
        $block['content'] = theme('g2_random', _g2_random());
        break;

      case G2::DELTA_WOTD:
        $block['subject'] = variable_get(G2VARWOTDTITLE, G2DEFWOTDTITLE);
        $block['content'] = theme('g2_wotd', _g2_wotd(variable_get(G2VARWOTDBODYSIZE, G2DEFWOTDBODYSIZE)));
        break;

      // Should happen only when using a new code version on an older schema
      // without updating: ignore.
      default:
        $block = NULL;
        break;
    }

    $ret = $block;
  }
  return $ret;
}

/**
 * Implement hook_cron().
 *
 * In G2's case, change the WOTD once a day if this feature is enabled,
 * which is the default case.
 */
function g2_cron() {
  return;
  if (variable_get(G2VARWOTDAUTOCHANGE, G2DEFWOTDAUTOCHANGE)) {
    $date0 = date('z', variable_get(G2VARWOTDDATE, mktime()));
    $date1 = date('z');
    if ($date1 <> $date0) {
      $random = _g2_random();
      // watchdog("g2_cron", "d0 = $date0, d1 = $date1, random : " . print_r($random,TRUE) . "</pre>", NULL, WATCHDOG_INFO);
      variable_set(G2VARWOTDENTRY, $random->nid);
      variable_set(G2VARWOTDDATE, mktime());
    }
  }
}

/**
 * Implement hook_delete().
 */
function g2_delete(&$node) {
  db_query('DELETE FROM {g2_node} WHERE nid = %d', $node->nid);
}

/**
 * Menu loader for %g2_entry.
 *
 * Only returns unpublished nodes to users with "administer nodes".
 *
 * @param string $title
 *
 * @return object
 */
function g2_entry_load($title) {
  $sq = 'SELECT n.nid '
    . 'FROM {node} n '
    . "WHERE n.type = '%s' AND n.status >= %d AND n.title LIKE '%s%%' ";
  $sq = db_rewrite_sql($sq);

  $min_status = user_access('administer nodes')
    ? NODE_NOT_PUBLISHED
    : NODE_PUBLISHED;
  $q = db_query($sq, G2NODETYPE, $min_status, $title);
  $nodes = [];
  while (is_object($node = db_fetch_object($q))) {
    $nodes[$node->nid] = node_load($node->nid);
  }
  return $nodes;
}

/**
 * Implement hook_filter().
 *
 * @link http://drupal.org/node/267484 @endlink
 * @link http://drupal.org/node/209715 @endlink
 */
function g2_filter($op, $delta = 0, $format = -1, $text = '', $cache_id = 0) {
  switch ($op) {
    case 'list':
      return [0 => t('G2 Glossary filter')];

    case 'description':
      return t('Allows users to link to G2 entries using &lt;dfn&gt; elements.');

    case 'prepare':
      $text = preg_replace('@<dfn>(.+?)</dfn>@s', "[g2-dfn]\\1[/g2-dfn]", $text);
      return $text;

    case "process":
      $text = preg_replace_callback('@\[g2-dfn\](.+?)\[/g2-dfn\]@s', '_g2_filter_process', $text);
      return $text;

    default:
      return $text;
  }
}

/**
 * Implement hook_filter_tips().
 */
function g2_filter_tips($delta, $format, $long = FALSE) {
  $ret = $long
    ? t('Wrap &lt;dfn&gt; elements around the terms for which you want a link to the available G2 definition(s).')
    : t('You may link to G2 definitions using &lt;dfn&gt; elements.');
  return $ret;
}

/**
 * Implement hook_form().
 */
function g2_form(&$node, $form_state) {
  if (!isset($node->title)) {
    $node->title = check_plain(drupal_substr($_REQUEST['q'],
      drupal_strlen(G2PATHNODEADD) + 1));
  }

  $type = node_get_types('type', G2NODETYPE);
  $form = [];
  $form['content'] = [
    '#type' => 'fieldset',
    '#title' => t('Contents'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => -10,
  ];
  $form['content']['title'] = [
    '#type' => 'textfield',
    '#title' => filter_xss_admin($type->title_label),
    '#required' => TRUE,
    '#default_value' => $node->title,
  ];
  $form['content']['teaser'] = [
    '#type' => 'textfield',
    '#title' => t('Entry expansion (for acronyms) or translation'),
    '#required' => FALSE,
    '#default_value' => isset($node->teaser) ? $node->teaser : NULL,
  ];
  $form['content']['body'] = [
    '#type' => 'textarea',
    '#title' => filter_xss_admin($type->body_label),
    '#rows' => 10,
    '#required' => TRUE,
    '#default_value' => isset($node->body) ? $node->body : NULL,
  ];
  $form['content']['format'] = filter_form($node->format);
  $form['content']['period'] = [
    '#type' => 'textfield',
    '#title' => t('Life period of this entry'),
    '#required' => FALSE,
    '#description' => t('This is the period of time during which the entity described by the term was actually alive, not the lifetime of the term itself, since any term is immortal to some extent.'),
    '#default_value' => isset($node->period) ? $node->period : NULL,
  ];
  $form['publishing'] = [
    '#type' => 'fieldset',
    '#title' => t('Editor-only information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Information in this box is not published in view mode, only during node edition.'),
    '#weight' => -5,
  ];
  $form['publishing']['complement'] = [
    '#type' => 'textarea',
    '#title' => t('Complement'),
    '#rows' => 10,
    '#required' => FALSE,
    '#description' => t('Information not pertaining to origin of document: comments, notes...'),
    '#default_value' => isset($node->complement) ? $node->complement : NULL,
  ];
  $form['publishing']['origin'] = [
    '#type' => 'textarea',
    '#title' => t('Origin/I.P.'),
    '#rows' => 10,
    '#required' => FALSE,
    '#description' => t('Informations about the origin/IP licensing of the definition'),
    '#default_value' => isset($node->origin) ? $node->origin : NULL,
  ];

  return $form;
}

/**
 * Implements hook_init()
 *
 * - place paths starting by the current G2 main path in G2 context
 */
function g2_init() {
  if (arg(0) == variable_get(G2VARPATHMAIN, G2DEFPATHMAIN)) {
    $GLOBALS['g2-context'] = TRUE;
  }
}

/**
 * Implement hook_insert().
 *
 * @XXX New feature to add: make extra node info revision-aware
 */
function g2_insert($node) {
  drupal_write_record('g2_node', $node);
}

/**
 * Implement hook_load().
 *
 * @XXX New feature to add: make extra node info revision-aware
 */
function g2_load($node) {
  $ret = db_fetch_object(db_query('SELECT * FROM {g2_node} WHERE nid = %s', $node->nid));
  return $ret;
}

/**
 * Implement hook_menu().
 *
 * Note: restructured in Drupal 6
 */
function g2_menu() {
  $items = [];

  $items[G2PATHSETTINGS] = [
    'title' => 'G2 glossary',
    'description' => 'Define the various parameters used by the G2 module',
    'file' => 'g2.admin.inc',
    'page callback' => 'drupal_get_form',
    'page arguments' => ['g2_admin_settings'],
    'access arguments' => ['administer site configuration'],
  ];

// AJAX autocomplete callback, so no menu entry
  $items[G2PATHAUTOCOMPLETE] = [
    'page callback' => '_g2_autocomplete',
    'access arguments' => [G2PERMVIEW],
    'type' => MENU_CALLBACK,
  ];

  $items[G2PATHWOTDFEED] = [
    'title' => G2TITLEWOTDFEED,
    'page callback' => '_g2_wotd_feed',
    'access arguments' => [G2PERMVIEW],
    'type' => MENU_CALLBACK,
  ];

// Offers to clear referers for all entries
  $items['g2/wipe'] = [
    'page callback' => 'drupal_get_form',
    'page arguments' => ['g2_referer_wipe_confirm_form'],
    'access arguments' => [G2PERMADMIN],
    'type' => MENU_CALLBACK,
  ];

// Offers to clear referers for a given entry
  $items['g2/wipe/%g2_node'] = [
    'page callback' => 'drupal_get_form',
    'page arguments' => ['g2_referer_wipe_confirm_form', 2],
    'access arguments' => [G2PERMADMIN],
    'type' => MENU_CALLBACK,
  ];

  $items['node/%g2_node/referers'] = [
    'title' => 'Referers',
    'page callback' => 'drupal_get_form',
    'page arguments' => ['_g2_referer_links', 1],
    'access arguments' => [G2PERMADMIN],
    'type' => MENU_LOCAL_TASK,
    'weight' => 2,
  ];

  return $items;
}

/**
 * Implement hook_node_info().
 */
function g2_node_info() {
  $ret = [
    G2NODETYPE => [
      'name' => t('G2 entry'),
      'module' => 'g2',
      'description' => t('A G2 entry is a term (usual sense, not drupal sense) for which a definition and various additional information is provided, notably at the editorial level'),
    ],
  ];
  return $ret;
}

/**
 * Menu loader for g2_node.
 *
 * @param int $us_nid
 *
 * @return object|FALSE|NULL
 */
function g2_node_load($us_nid = 0) {
  // Safety with regard to $us_nid is checked within node_load()
  if (is_numeric($us_nid)) {
    $node = Node::load($us_nid);
  }
  else {
    $node = reset($us_nid);
  }
  if ($node->type != G2::NODE_TYPE) {
    $node = NULL;
  }

  return $node;
}

/**
 * Implement hook_nodeapi().
 *
 * Change the publication date only for the WOTD feed so that even old
 * terms, when chosen for publication, reflect the publication date,
 * instead of the node creation date as is the default.
 *
 * - Do not apply to non-G2 nodes.
 * - Do not apply to non-WOTD feeds.
 *
 * This implementation does not use optional $teaser and $page params.
 */
function g2_nodeapi(&$node, $op) {
  if (($op == 'rss item') && ($node->type == G2NODETYPE)
    && ($_GET['q'] == G2PATHWOTDFEED)
  ) {
    $node->created = variable_get(G2VARWOTDDATE, time());
    $node->name = filter_xss_admin(strtr(variable_get(G2VARWOTDFEEDAUTHOR, '@author'),
      ['@author' => check_plain($node->name)]));
  }
}

/**
 * Implement hook_perm().
 *
 * The extended form used here is drawn from the D7 version.
 */
function g2_perm() {
  $ret = array_keys([
    G2PERMADMIN => [
      'title' => t('Administer G2 entries'),
      'description' => t('Access administrative information on G2 entries. This permission does not grant access to the module settings, which are controlled by the "administer site configuration" permission.'),
    ],
    G2PERMVIEW => [
      'title' => t('View G2 entries'),
      'description' => t('This permission allows viewing G2 entries, subject to additional node access control.'),
    ],
  ]);

  return $ret;
}

/**
 * Implements hook_preprocess_page().
 *
 * - introduce G2 page template suggestion when page is in a G2 context
 */
function g2_preprocess_page(&$vars) {
  if (!empty($GLOBALS['g2-context'])) {
    $vars['template_files'][] = 'page-g2';
    if (!empty($vars['body_classes'])) {
      $vars['body_classes'] .= ' context-g2';
    }
    else {
      $vars['body_classes'] = 'context-g2';
    }
  }
}

/**
 * Submit handler for "wipe referers" button.
 *
 * @param array $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 */
function g2_referer_wipe_button_submit(array $form, FormStateInterface $form_state) {
  drupal_goto('g2/wipe');
}

/**
 * Form builder for the referer wipe confirmation request form.
 *
 * This is the same form for both global wipe and individual node wipe.
 *
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param null|\Drupal\node\NodeInterface $node
 *   The node from which to erase.
 *
 * @return array
 *   A render array for the confirm form.
 */
function g2_referer_wipe_confirm_form(FormStateInterface $form_state, NodeInterface $node = NULL) {
  $form = [];

  if (is_object($node) && isset($node->nid)) {
    $question = t('Are you sure to want to erase the referer information on this G2 entry ?');
    $cancel = 'node/' . $node->nid . '/referers';
    $form['node'] = [
      '#prefix' => '<p><em>',
      '#value' => check_plain($node->title),
      '#suffix' => '</em></p>',
    ];
  }
  else {
    $question = t('Are you sure to want to erase the referer information on all G2 entries ?');
    $cancel = G2PATHSETTINGS;
  }

  $ret = confirm_form($form,
    $question,
    $cancel,
    t('This action cannot be undone.'),
    t('Confirm'),
    t('Cancel')
  );
  return $ret;
}

/**
 * Submit handler for referer wipe confirmation.
 *
 * @param array $form
 * @param array $form_state
 */
function g2_referer_wipe_confirm_form_submit($form, &$form_state) {
  _g2_referer_wipe();
  drupal_set_message(t('Referer information has been erased on all G2 entries'));
  $form_state['redirect'] = G2PATHSETTINGS;
}

/**
 * Implement hook_update().
 */
function g2_update($node) {
  drupal_write_record('g2_node', $node, 'nid');
}

/**
 * Implement hook_user().
 *
 * @todo D6 check when content is actually there
 */
function g2_user($op, &$edit, &$account, $category = NULL) {

  switch ($op) {
    case 'load':
      // Issue #1047248: unused n.changed and n.created columns are here for PGSQL.
      $sq = 'SELECT n.nid, n.title, n.changed, n.created '
        . 'FROM {node} n '
        . "WHERE n.type = '%s' AND n.status  = 1 AND n.uid = %d "
        . 'ORDER BY n.changed DESC, n.created DESC';
      $sq = db_rewrite_sql($sq);
      $q = db_query_range($sq, G2NODETYPE, $account->uid, 0, 10);
      $account->nodes = [];
      while (is_object($o = db_fetch_object($q))) {
        $account->nodes[] = [
          'value' => l($o->title, 'node/' . $o->nid, ['absolute' => TRUE]),
        ];
      }
      break;

    case 'view':
      $nodes = [];
      if (is_array($account->nodes)) {
        foreach ($account->nodes as $node) {
          $nodes[] = $node['value'];
        }
      }
      $account->content['summary']['g2'] = [
        '#type' => 'user_profile_item',
        '#title' => t('Owned G2 definitions'),
        '#value' => theme('item_list', $nodes),
        '#description' => t('10 most recently changed only'),
      ];
      break;

    default:
      //dprint_r($op);
      break;
  }
}

/**
 * Implement hook_view().
 */
function g2_view(&$node, $teaser = FALSE, $page = FALSE) {
  if ($page) {
    $GLOBALS['g2-context'] = TRUE;
  }

  $title = check_plain($node->title);

  if ($page) {
    $bc = drupal_get_breadcrumb();
    $bc[] = l(G2TITLEMAIN, $g2_home = variable_get(G2VARPATHMAIN, G2DEFPATHMAIN));
    $initial = drupal_substr($title, 0, 1);
    $bc[] = l($title[0], G2PATHINITIAL . '/' . $initial);
    unset($initial);
    drupal_set_breadcrumb($bc);
    _g2_override_site_name();
  }

  // Build more link, apply input format, including sanitizing.
  $node = node_prepare($node, $teaser);

  $node->content['body'] = [
    '#value' => theme('g2_body', t('Definition'), $node->body),
    '#weight' => 1,
  ];

  if (!empty($node->teaser)) {
    $node->content['teaser'] = [
      '#value' => theme('g2_teaser', t('In other words'), $node->teaser),
      '#weight' => 0,
    ];
  }

  if (!empty($node->period)) {
    $node->content['period'] = [
      '#value' => theme('g2_period', t('Term time period'), check_plain($node->period)),
      '#weight' => 2,
    ];
  }

  // The following line adds invisible text that will be prepended to
  // the node in case some search routine favors the beginning of the
  // body. It can be turned off in case search engines frown upon this.
  if (variable_get(G2VARHIDDENTITLE, G2DEFHIDDENTITLE)) {
    $node->content['extra_title'] = [
      '#prefix' => '<div style="display: none">',
      '#value' => check_plain($node->title),
      '#suffix' => '</div>',
      '#weight' => -1,
    ];
  }

  // Modify displayed taxonomy according to our settings
  $taxonomy = taxonomy_node_get_terms($node);
  $node->taxonomy = _g2_comb_taxonomy($taxonomy);

  global $base_url;
  $referer = referer_uri();

  // Is referer local ? MUST use ===, otherwise FALSE would match too
  if (!empty($referer) && strpos($referer, $base_url . '/') === 0) {
    // Extract local path, possibly aliased.
    $referer = drupal_substr($referer, drupal_strlen($base_url) + 1);

    // Unalias it.
    $referer = drupal_get_normal_path($referer);

    // Sanitize it.
    $referer = check_plain($referer);

    // Drupal_write_record() is too costly to be used on frequent and
    // non-alterable operations like logging a node view.
    $sq = 'UPDATE {g2_referer} '
      . 'SET incoming = incoming + 1 '
      . "WHERE nid = %d AND referer = '%s'";
    db_query($sq, $node->nid, $referer);
    if (!db_affected_rows()) {
      $sq = 'INSERT INTO {g2_referer} '
        . '  (nid, referer, incoming) '
        . "VALUES (%d, '%s', 1) ";
      db_query($sq, $node->nid, $referer);
    }
  }
  else {
    /**
     * Referer is non-local.
     * Maybe we'll do something some day, but not right now
     */
  }

  // $x = $node; $x->as_teaser = $teaser ? 'teaser' : 'body'; $x->as_page = $page ? 'page' : 'list'; dsm($x);
  return $node;
}

/**
 * Implements hook_views_api().
 */
function g2_views_api() {
  return [
    'api' => '2.0',
    'path' => drupal_get_path('module', 'g2') . '/views',
  ];
}

/**
 * Implements hook_xmlrpc().
 *
 * Note that functions returning node portions return them unfiltered.
 * It is the caller's responsibility to apply filtering depending on
 * its actual use of the data.
 */
function g2_xmlrpc() {
  $mapping = [
    'g2.alphabar' => '_g2_alphabar',
    'g2.api' => '_g2_api',
    'g2.latest' => '_g2_latest',
    'g2.random' => '_g2_random',
    'g2.stats' => '_g2_stats',
    'g2.top' => '_g2_top',
    'g2.wotd' => '_g2_wotd',
  ];

  $enabled = \Drupal::config('g2.settings')->get('rpc.server.enabled');
  if (!$enabled) {
    $mapping = [];
  }

  return $mapping;
}

/**
 * Return a themed g2 node body.
 *
 * Title and body are filtered prior to invoking this theme function
 * within g2_view(), so it performs no filtering on its own.
 *
 * @param title $title
 *   The title for the body container. A g2_view() constant string.
 * @param body $body
 *   The body itself, filtered by node_prepare().
 *
 * @return string
 *   HTML
 */
function theme_g2_body($title, $body) {
  return theme('box', $title, $body);
}

/**
 * Return a themed g2 node time period.
 *
 * Title and period are filtered prior to invoking this theme function
 * within g2_view(), so it performs no filtering on its own.
 *
 * @param string $title
 *   The title for the period container
 * @param string $period
 *   The period itself. filtered by check_plain().
 *
 * @return string HTML
 */
function theme_g2_period($title, $period) {
  return theme('box', $title, "<p>$period</p>");
}

/**
 * Theme a random entry.
 *
 * This is actually a short view for just about any single node, but it
 * is even shorter than node_view($node, TRUE).
 *
 * @param object $node
 *
 * @return string HTML
 */
function theme_g2_random($node = NULL) {
  $ret = l($node->title, 'node/' . $node->nid);
  if ($node->teaser) {
    // Why t() ? Because varying languages have varying takes on spaces before/after semicolons
    $ret .= t(': @teaser', ['@teaser' => $node->teaser]);
  }
  $ret .= _g2_entry_terms($node); // No need to test: also works on missing taxonomy
  $ret .= theme('more_link', url('node/' . $node->nid), t('&nbsp;(+)'));
  return $ret;
}

/**
 * Return a themed g2 node teaser.
 *
 * Teasers normally contain expansions for acronyms/initialisms,
 * or translations for foreign terms
 *
 * Title and teaser are filtered prior to invoking this theme function
 * within g2_view(), so it performs no filtering on its own.
 *
 * @param string $title
 *   The title for the teaser container. A g2_view() constant string.
 * @param string $teaser
 *   The teaser itself, filtered by node_prepare().
 *
 * @return string
 *   HTML
 */
function theme_g2_teaser($title, $teaser) {
  return theme('box', $title, "<p>$teaser</p>");
}

/**
 * Theme a WOTD block.
 *
 * @param \Drupal\node\Entity\Node|null $node
 *   The node for the word of the day. teaser and body are already filtered and
 *   truncated if needed.
 *
 * @return string title / nid / teaser / [body]
 */
function theme_g2_wotd(Node $node = NULL) {
  if (empty($node)) {
    return NULL;
  }

  $link = l($node->title, 'node/' . $node->nid); // l() check_plain's text
  if (isset($node->teaser) and !empty($node->teaser)) {
    // Teaser already filtered by _g2_wotd(), don't filter twice.
    $teaser = '<span id="g2_wotd_teaser">' . strip_tags($node->teaser) . '</span>';
    $ret = t('!link: !teaser', [
      '!link' => $link,
      '!teaser' => $teaser,
    ]);
    unset($teaser);
  }
  else {
    $ret = $link;
  }

  if (!empty($node->body)) {
    // Already filtered by _g2_wotd(), don't filter twice, just strip.
    $body = strip_tags($node->body);
    if ($node->truncated) {
      $body .= '&hellip;';
    }
    $ret .= '<div id="g2_wotd_body">' . $body . '</div>';
  }
  $node->taxonomy = _g2_comb_taxonomy($node->taxonomy);
  $ret .= _g2_entry_terms($node); // No need to test: it won't change anything if a taxonomy has not been returned
  $ret .= theme('more_link', url('node/' . $node->nid), t('&nbsp;(+)'));
  if (variable_get(G2VARWOTDFEEDLINK, G2DEFWOTDFEEDLINK)) {
    $ret .= theme('feed_icon', url(G2PATHWOTDFEED, ['absolute' => TRUE]),
      t('A word a day in your RSS reader'));
  }
  return $ret;
}

/**
 * Implements hook_block_configure().
 */
function Zg2_block_configure($delta) {
  $count_options = [
    '1' => '1',
    '2' => '2',
    '5' => '5',
    '10' => '10',
  ];
  $info = g2_block_info();
  $info = $info[$delta];
  $form['caching'] = [
    '#markup' => t('<p>Caching mode: @mode</p>', ['@mode' => G2\block_cache_decode($info['cache'])]),
  ];

  switch ($delta) {
    case G2\DELTARANDOM:
      $form[G2\VARRANDOMSTORE] = [
        '#type' => 'checkbox',
        '#title' => t('Store latest random entry'),
        '#default_value' => variable_get(G2\VARRANDOMSTORE, G2\DEFRANDOMSTORE),
        '#description' => t('When this setting is TRUE (default value),
      the latest random value is kept in the DB to avoid showing the same pseudo-random
      value on consecutive page displays.
      For small sites, it is usually best to keep it saved.
      For larger sites, unchecking this setting will remove one database write with locking.'),
      ];
      $form[G2\VARRANDOMTERMS] = [
        '#type' => 'checkbox',
        '#title' => t('Return taxonomy terms for the current entry'),
        '#default_value' => variable_get(G2\VARRANDOMTERMS, G2\DEFRANDOMTERMS),
        '#description' => t('The taxonomy terms will be returned by XML-RPC and made available to the theme.
         Default G2 themeing will display them.'),
      ];
      break;

    case G2\DELTATOP:
      $form[G2\VARTOPITEMCOUNT] = [
        '#type' => 'select',
        '#title' => t('Number of items'),
        '#default_value' => variable_get(G2\VARTOPITEMCOUNT, G2\DEFTOPITEMCOUNT),
        '#options' => $count_options,
      ];
      break;

    case G2\DELTAWOTD:
      // Convert nid to "title [<nid>]" even if missing.
      // @see autocomplete()
      $nid = variable_get(G2\VARWOTDENTRY, G2\DEFWOTDENTRY);
      $node = node_load($nid);
      if (empty($node)) {
        $node = new stdClass();
        $node->nid = 0;
        $node->title = NULL;
      }
      $form[G2\VARWOTDENTRY] = [
        '#type' => 'textfield',
        '#title' => t('Entry for the day'),
        '#maxlength' => 60,
        '#autocomplete_path' => G2\PATHAUTOCOMPLETE,
        '#required' => TRUE,
        // !title: we don't filter since this is input, not output,
        // and can contain normally escaped characters, to accommodate
        // entries like "<", "C#" or "AT&T"
        '#default_value' => t('!title [@nid]', [
          '!title' => $node->title,
          '@nid' => $nid,
        ]),
      ];
      $form[G2\VARWOTDBODYSIZE] = [
        '#type' => 'textfield',
        '#title' => t('Number of text characters to be displayed from entry definition body, if one exists'),
        '#size' => 4,
        '#maxlength' => 4,
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDBODYSIZE, G2\DEFWOTDBODYSIZE),
      ];
      $form[G2\VARWOTDAUTOCHANGE] = [
        '#type' => 'checkbox',
        '#title' => t('Auto-change daily'),
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDAUTOCHANGE, G2\DEFWOTDAUTOCHANGE),
        '#description' => t('This setting will only work if cron or poormanscron is used.'),
      ];
      $form[G2\VARWOTDTERMS] = [
        '#type' => 'checkbox',
        '#title' => t('Return taxonomy terms for the current entry'),
        '#default_value' => variable_get(G2\VARWOTDTERMS, G2\DEFWOTDTERMS),
        '#description' => t('The taxonomy terms will be returned by XML-RPC and made available to the theme.
         Default G2 themeing will display them.'),
      ];
      $default_wotd_title = t('Word of the day in the G2 glossary');
      $form[G2\VARWOTDTITLE] = [
        '#type' => 'textfield',
        '#title' => t('Title for the WOTD block'),
        '#description' => t('This title is also the default title for the WOTD feed, if none is defined. It is overridden by the default Drupal block title, if the latter is not empty.'),
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDTITLE, $default_wotd_title),
      ];

      $form['wotd_feed'] = [
        '#type' => 'fieldset',
        '#title' => t('RSS Feed'),
      ];
      $form['wotd_feed'][G2\VARWOTDFEEDLINK] = [
        '#type' => 'checkbox',
        '#title' => t('Display feed link'),
        '#default_value' => variable_get(G2\VARWOTDFEEDLINK, G2\DEFWOTDFEEDLINK),
        '#description' => t('Should the theme display the link to the RSS feed for this block ?'),
      ];
      $form['wotd_feed'][G2\VARWOTDFEEDTITLE] = [
        '#type' => 'textfield',
        '#title' => t('The feed title'),
        '#size' => 60,
        '#maxlength' => 60,
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDFEEDTITLE, variable_get(G2\VARWOTDTITLE, $default_wotd_title)),
        '#description' => t('The title for the feed itself.
         This will typically be used by aggregators to remind users of the feed and link to it.
         If nulled, G2 will reset it to the title of the block.'),
      ];
      $form['wotd_feed'][G2\VARWOTDFEEDAUTHOR] = [
        '#type' => 'textfield',
        '#title' => t('The feed item author'),
        '#size' => 60,
        '#maxlength' => 60,
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDFEEDAUTHOR, G2\DEFWOTDFEEDAUTHOR),
        '#description' => t('The author name to be included in the feed entries.
      In this string @author will be replaced by the actual author information.'),
      ];
      $form['wotd_feed'][G2\VARWOTDFEEDDESCR] = [
        '#type' => 'textfield',
        '#title' => t('The feed description'),
        '#size' => 60,
        '#maxlength' => 60,
        '#required' => TRUE,
        '#default_value' => variable_get(G2\VARWOTDFEEDDESCR, t('A daily definition from the G2 Glossary at !site')),
        '#description' => t('The description for the feed itself.
      This will typically be used by aggregators when describing the feed prior to subscription.
      It may contain !site, which will dynamically be replaced by the site base URL.'),
      ];
      break;

    default:
      break;
  }
  return $form;
}

/**
 * Implements hook_block_info().
 */
function Zg2_block_info() {
  $blocks = [];
  $blocks[G2\DELTARANDOM]['info'] = variable_get('g2_random_info', t('G2 Random'));
  $blocks[G2\DELTATOP]['info'] = variable_get('g2_top_info', t('G2 Top'));
  $blocks[G2\DELTAWOTD]['info'] = variable_get('g2_wotd_info', t('G2 Word of the day'));

  // Else it couldn't be random.
  $blocks[G2\DELTARANDOM]['cache'] = DRUPAL_NO_CACHE;
  // Can contain unpublished nodes.
  $blocks[G2\DELTATOP]['cache'] = DRUPAL_CACHE_PER_ROLE;
  // Not all roles have g2 view permission.
  $blocks[G2\DELTAWOTD]['cache'] = DRUPAL_CACHE_PER_ROLE;
  return $blocks;
}

/**
 * Implements hook_block_save().
 */
function Zg2_block_save($delta, $edit) {
  switch ($delta) {
    case G2\DELTARANDOM:
      variable_set(G2\VARRANDOMSTORE, $edit[G2\VARRANDOMSTORE]);
      variable_set(G2\VARRANDOMTERMS, $edit[G2\VARRANDOMTERMS]);
      break;

    case G2\DELTATOP:
      variable_set(G2\VARTOPITEMCOUNT, $edit[G2\VARTOPITEMCOUNT]);
      break;

    case G2\DELTAWOTD:
      // Convert "some title [<nid>, sticky]" to nid.
      $entry = $edit[G2\VARWOTDENTRY];
      $matches = [];
      $count = preg_match('/.*\[(\d*).*\]$/', $entry, $matches);
      $nid = $count ? $matches[1] : 0;

      variable_set(G2\VARWOTDENTRY, $nid);
      variable_set(G2\VARWOTDBODYSIZE, $edit[G2\VARWOTDBODYSIZE]);
      variable_set(G2\VARWOTDAUTOCHANGE, $edit[G2\VARWOTDAUTOCHANGE]);
      variable_set(G2\VARWOTDDATE, REQUEST_TIME);
      variable_set(G2\VARWOTDTERMS, $edit[G2\VARWOTDTERMS]);
      variable_set(G2\VARWOTDFEEDLINK, $edit[G2\VARWOTDFEEDLINK]);
      variable_set(G2\VARWOTDFEEDTITLE, $edit[G2\VARWOTDFEEDTITLE]);
      variable_set(G2\VARWOTDFEEDDESCR, $edit[G2\VARWOTDFEEDDESCR]);
      variable_set(G2\VARWOTDFEEDAUTHOR, $edit[G2\VARWOTDFEEDAUTHOR]);
      variable_set(G2\VARWOTDTITLE, $edit[G2\VARWOTDTITLE]);
      break;

    default:
      break;
  }
}

/**
 * Implements hook_block_view().
 */
function Zg2_block_view($delta) {
  // watchdog('g2', "hook_block/view/$delta");
  switch ($delta) {
    case G2\DELTARANDOM:
      $block['subject'] = t('Random G2 glossary entry');
      $block['content'] = theme('g2_random', ['node' => G2\random()]);
      break;

    case G2\DELTATOP:
      $max = variable_get(G2\VARTOPITEMCOUNT, G2\DEFTOPITEMCOUNT);
      $block['subject'] = t('@count most popular G2 glossary entries',
        ['@count' => $max]);
      $block['content'] = theme('g2_node_list', ['nodes' => G2\top($max, FALSE, TRUE)]);
      break;

    case G2\DELTAWOTD:
      $block['subject'] = variable_get(G2\VARWOTDTITLE, t('Word of the day in the G2 glossary'));
      $block['content'] = theme('g2_wotd', ['node' => G2\wotd(variable_get(G2\VARWOTDBODYSIZE, G2\DEFWOTDBODYSIZE))]);
      break;

    // Should happen only when using a new code version on an older schema
    // without updating: ignore.
    default:
      $block = NULL;
      break;
  }

  return $block;
}

/**
 * Implements hook_context_plugins().
 *
 * This is a ctools plugins hook.
 */
function Zg2_context_plugins() {
  module_load_include('inc', 'g2', 'context/g2.plugins');
  return _g2_context_plugins();
}


/**
 * Implements hook_context_registry().
 */
function Zg2_context_registry() {
  module_load_include('inc', 'g2', 'context/g2.plugins');
  return _g2_context_registry();
}

/**
 * Implements hook_cron().
 *
 * In G2's case, change the WOTD once a day if this feature is enabled,
 * which is the default case.
 */
function Zg2_cron() {
  if (variable_get(G2\VARWOTDAUTOCHANGE, G2\DEFWOTDAUTOCHANGE)) {
    $date0 = date('z', variable_get(G2\VARWOTDDATE, REQUEST_TIME));
    $date1 = date('z');
    if ($date1 <> $date0) {
      $random = G2\random();
      // watchdog("g2_cron", "d0 = $date0, d1 = $date1, random : "
      // . print_r($random,TRUE) . "</pre>", NULL, WATCHDOG_INFO);
      variable_set(G2\VARWOTDENTRY, $random->nid);
      variable_set(G2\VARWOTDDATE, mktime());
    }
  }
}

/**
 * Implements hook_ctools_plugin_api().
 */
function Zg2_ctools_plugin_api($module, $api) {
  if ($module == 'context' && $api == 'context') {
    $ret = [
      'version' => 3,
      'path' => drupal_get_path('module', 'g2') . '/context',
      // Not until http://drupal.org/node/1242632 is fixed
      // 'file' => 'g2.context_defaults.inc',
    ];
  }
  else {
    $ret = NULL;
  }

  return $ret;
}

/**
 * Implements hook_delete().
 */
function Zg2_delete($node) {
  // dsm($node, __FUNCTION__);
  db_delete('g2_node')
    ->condition('nid', $node->nid)
    ->execute();
}

/**
 * Implements hook_field_extra_fields().
 */
function Zg2_field_extra_fields() {
  $expansion = [
    'label' => t('Expansion'),
    'description' => t('For acronyms/initialisms, this is the expansion of the initials to full words'),
    'weight' => 0,
  ];
  $period = [
    'label' => t('Life period'),
    'description' => t('This is the period of time during which the entity described by the term was actually alive, not the lifetime of the term itself, since any term is immortal to some extent.'),
    'weight' => 1,
  ];
  $extra_title = [
    'label' => 'Extra title',
    'description' => t('The optional CSS-hidden extra title on node displays'),
    'weight' => 99,
  ];

  $extra['node'][G2\NODETYPE] = [
    'form' => [
      'expansion' => $expansion,
      'period' => $period,
      'complement' => [
        'label' => t('Complement'),
        'description' => t('Additional non-versioned editor-only meta-information about the definition'),
        'weight' => 2,
      ],
      'origin' => [
        'label' => t('IP/Origin'),
        'description' => t('Additional non-versioned editor-only Intellectual Property/Origin information about the definition'),
        'weight' => 3,
      ],
    ],
    'display' => [
      'expansion' => $expansion,
      'period' => $period,
      'extra_title' => $extra_title,
    ],
  ];

  return $extra;
}

/**
 * Implements hook_filter_info().
 */
function Zg2_filter_info() {
  $filters = [
    'filter_g2' => [
      'title' => t('G2 Glossary filter'),
      'description' => t('Allows users to link to G2 entries using &lt;dfn&gt; elements.'),
      'prepare callback' => 'G2\filter_prepare',
      'process callback' => 'G2\filter_process',
      'tips callback' => 'G2\filter_tips',
    ],
  ];

  return $filters;
}

/**
 * Implements hook_form().
 *
 * XXX 20110122 use fields, not properties for expansion/period/editor info.
 */
function Zg2_form(&$node, $form_state) {

  $admin = user_access('bypass node access')
    || user_access('edit any g2_entry content')
    || (user_access('edit own g2_entry content') && $user->uid == $node->uid);

  $type = node_type_get_type($node);

// Pre-fill title information on URL-based node creation.
  if (!isset($node->title)) {
    $node->title = check_plain(drupal_substr($_GET['q'],
      drupal_strlen(G2\PATHNODEADD) + 1));
  }

  $form = [];

  $form['content'] = [
    '#type' => 'fieldset',
    '#title' => t('Contents'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    '#weight' => -10,
  ];
  $form['content']['title'] = [
    '#type' => 'textfield',
    '#title' => check_plain($type->title_label),
    '#required' => TRUE,
    '#default_value' => $node->title,
    '#weight' => -5,
    '#description' => t('Plain text: no markup allowed.'),
  ];

  $form['content']['expansion'] = [
    '#type' => 'textfield',
    '#title' => t('Entry expansion (for acronyms/initialisms)'),
    '#required' => FALSE,
    '#default_value' => isset($node->expansion) ? $node->expansion : NULL,
    '#description' => t('Plain text: no markup allowed.'),
  ];

  $form['content']['period'] = [
    '#type' => 'textfield',
    '#title' => t('Life period of this entry'),
    '#required' => FALSE,
    '#description' => t('This is the period of time during which the entity described by the term was actually alive, not the lifetime of the term itself, since any term is immortal to some extent. Plain text, no markup allowed.'),
    '#default_value' => isset($node->period) ? $node->period : NULL,
  ];

  // Hide published-only secondary information in a vertical tab.
  $form['publishing'] = [
    '#type' => 'fieldset',
    '#title' => t('Editor-only information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#description' => t('Information in this box is not published in view mode, only during node edition.'),
    '#group' => 'additional_settings',
    '#weight' => -5,
    '#access' => $admin,
    '#attached' => [
      'js' => [drupal_get_path('module', 'g2') . '/g2.js'],
    ],
  ];
  $form['publishing']['complement'] = [
    '#type' => 'textarea',
    '#title' => t('Complement'),
    '#rows' => 10,
    '#required' => FALSE,
    '#description' => t('Information not pertaining to origin of document: comments, notes...'),
    '#default_value' => isset($node->complement) ? $node->complement : NULL,
    '#access' => $admin,
  ];
  $form['publishing']['origin'] = [
    '#type' => 'textarea',
    '#title' => t('Origin/I.P.'),
    '#rows' => 10,
    '#required' => FALSE,
    '#description' => t('Informations about the origin/IP licensing of the definition'),
    '#default_value' => isset($node->origin) ? $node->origin : NULL,
    '#access' => $admin,
  ];

  return $form;
}

/**
 * Implements hook_insert().
 *
 * XXX New feature to add: make extra node info revision-aware.
 */
function Zg2_insert($node) {
  drupal_write_record('g2_node', $node);
}

/**
 * Implements hook_load().
 *
 * Access control was performed earlier by core: no need to do it again here.
 *
 * XXX New feature to add: make extra node info revision-aware.
 */
function Zg2_load($nodes) {
  $q = db_select('g2_node', 'gn');
  $result = $q->fields('gn')
    ->condition('gn.nid', array_keys($nodes), 'IN')
    ->execute();

  foreach ($result as $row) {
    foreach ($row as $property => $col) {
      $nodes[$row->nid]->$property = $col;
    }
  }
}

/**
 * Implements hook_menu().
 */
function Zg2_menu() {
  $items = [];

  $items[G2\PATHSETTINGS] = [
    'title' => 'G2 glossary',
    'description' => 'Define the various parameters used by the G2 module',
    'page callback' => 'drupal_get_form',
    'page arguments' => ['G2\admin_settings'],
    'access arguments' => ['administer site configuration'],
  ];

  // AJAX autocomplete callback, so no menu entry.
  $items[G2\PATHAUTOCOMPLETE] = [
    'page callback' => 'G2\autocomplete',
    'access arguments' => [G2\PERMVIEW],
    'type' => MENU_CALLBACK,
  ];

  $items[G2\PATHWOTDFEED] = [
    'title' => G2\TITLEWOTDFEED,
    'page callback' => 'G2\wotd_feed',
    'access arguments' => [G2\PERMVIEW],
    'type' => MENU_CALLBACK,
  ];

  // Offers to clear referers for all entries.
  $items['g2/wipe'] = [
    'page callback' => 'drupal_get_form',
    'page arguments' => ['G2\referer_wipe_confirm_form'],
    'access arguments' => [G2\PERMADMIN],
    'type' => MENU_CALLBACK,
  ];

  // Offers to clear referers for a given entry.
  $items['g2/wipe/%g2_nid'] = [
    'page callback' => 'drupal_get_form',
    'page arguments' => ['G2\referer_wipe_confirm_form', 2],
    'access arguments' => [G2\PERMADMIN],
    'type' => MENU_CALLBACK,
  ];

  $items['node/%g2_nid/referers'] = [
    'title' => 'Referers',
    'page callback' => 'drupal_get_form',
    'page arguments' => ['G2\referer_links', 1],
    'access arguments' => [G2\PERMADMIN],
    'type' => MENU_LOCAL_TASK,
    'weight' => 2,
  ];

  return $items;
}

/**
 * Menu loader for g2_node.
 *
 * @param int $us_nid
 *   Safety with regard to $us_nid is checked within node_load().
 *
 * @return object|FALSE|NULL
 *   - loaded object if accessible G2 node
 *   - NULL if accessible object is not a G2 node
 *   - FALSE otherwise
 */
function Zg2_nid_load($us_nid = 0) {
  $node = node_load($us_nid);
  if ($node->type != G2\NODETYPE) {
    $node = NULL;
  }
  return $node;
}

/**
 * Implements hook_node_access().
 */
function Zg2_node_access($node, $op, $account) {
  switch ($op) {
    case 'create':
    case 'delete':
    case 'update':
      $ret = user_access(G2\PERMADMIN, $account);
      break;

    case 'view':
      $ret = user_access(G2\PERMVIEW, $account);
      break;

    default:
      $uri = entity_uri('node', $node);
      watchdog('g2', 'Node access for invalid op %op', ['%op' => $op],
        WATCHDOG_NOTICE,
        l($node->title, $uri['path'], $uri['options']));
      $ret = FALSE;
  }

  return $ret;
}

/**
 * Implements hook_node_info().
 */
function Zg2_node_info() {
  $ret = [
    G2\NODETYPE => [
      'name' => t('G2 entry'),
      'base' => 'g2',
      'description' => t('A G2 entry is a term (usual sense, not drupal sense) for which a definition and various additional information is provided, notably at the editorial level'),
      'help' => t('The title should be either a acronym/initialism or a normal word. If it is an acronym/initialism, use the expansion field to decode it, not the definition field.'),
      'has_title' => TRUE,
      'title_label' => t('Term to define'),
    ],
  ];
  return $ret;
}

/**
 * Implements hook_node_view().
 *
 * Change the publication date only for the WOTD feed so that even old
 * terms, when chosen for publication, reflect the publication date,
 * instead of the node creation date as is the default.
 *
 * - Do not apply to non-G2 nodes.
 * - Do not apply to non-WOTD feeds.
 */
function Zg2_node_view($node, $view_mode, $langcode) {
  if ($view_mode == 'rss' && $node->type == G2\NODETYPE && ($_GET['q'] == G2\PATHWOTDFEED)) {
    $node->created = variable_get(G2\VARWOTDDATE, REQUEST_TIME);
    $node->name = filter_xss_admin(strtr(variable_get(G2\VARWOTDFEEDAUTHOR, '@author'),
      ['@author' => check_plain($node->name)]));
  }
}

/**
 * Implements hook_permission().
 */
function Zg2_permission() {
  $ret = [
    G2\PERMADMIN => [
      'title' => t('Administer G2 entries'),
      'description' => t('Access administrative information on G2 entries. This permission does not grant access to the module settings, which are controlled by the "administer site configuration" permission.'),
      'restrict access' => TRUE,
    ],
    G2\PERMVIEW => [
      'title' => t('View G2 entries'),
      'description' => t('This permission allows viewing G2 entries, subject to additional node access control.'),
    ],
  ];
  return $ret;
}

/**
 * Implements hook_preprocess_page().
 *
 * - introduce G2 page template suggestion when page is in a G2 context
 */
function Zg2_preprocess_page(&$vars) {
  if ($plugin = context_get_plugin('reaction', 'g2_template')) {
    $plugin->execute($vars);
  }
}

/**
 * Implements hook_update().
 */
function Zg2_update($node) {
  // dsm($node, __FUNCTION__);
  drupal_write_record('g2_node', $node, 'nid');
}

/**
 * Implements hook_user_load().
 */
function Zg2_user_load($users) {
  $q = db_select('node', 'n');
  $result = $q->fields('n', ['nid', 'title', 'uid', 'type'])
    ->condition('n.type', G2\NODETYPE)
    ->condition('n.status', 1)
    ->condition('n.uid', array_keys($users), 'IN')
    ->orderBy('n.changed', 'DESC')
    ->orderBy('n.created', 'DESC')
    ->addTag('node_access')
    ->range(0, 10)
    ->execute();
  foreach ($result as $row) {
    $uri = entity_uri('node', $row);
    $uri['options']['absolute'] = TRUE;
    $users[$row->uid]->nodes[] = [
      'value' => l($row->title, $uri['path'], $uri['options']),
    ];
  }
}

/**
 * Implements hook_user_view().
 */
function Zg2_user_view($account, $view_mode, $langcode) {
  if (isset($account->nodes) && count($account->nodes) >= 1) {
    $nodes = [];
    foreach ($account->nodes as $node) {
      $nodes[] = $node['value'];
    }
    $account->content['summary']['g2'] = [
      '#type' => 'user_profile_item',
      '#title' => t('Recent G2 definitions'),
      '#markup' => theme('item_list', ['items' => $nodes]),
    ];
  }
}

/**
 * Implements hook_view().
 *
 * @param object $node
 *   The node for which content is to be built.
 * @param string $view_mode
 *   The view_mode used to chose what to build.
 *
 * @return object
 *   The node with updated fields.
 */
function Zg2_view($node, $view_mode) {
  $title = check_plain($node->title);

  if (node_is_page($node)) {
    $bc = drupal_get_breadcrumb();
    $bc[] = l(G2\TITLEMAIN, $g2_home = variable_get(G2\VARPATHMAIN, G2\DEFPATHMAIN));
    $initial = drupal_substr($title, 0, 1);
    $bc[] = l($title[0], $g2_home . '/initial/' . $initial);
    unset($initial);
    drupal_set_breadcrumb($bc);
    G2\override_site_name();

    // Only log referrers on full page views.
    if (variable_get(G2\VARLOGREFERRERS, G2\DEFLOGREFERRERS)) {
      G2\log_referrers($node);
    }

// Activate context.
    if ($plugin = context_get_plugin('condition', 'g2')) {
      $plugin->execute('g2_node');
    }
  }

  /*
  // Build more link, apply input format, including sanitizing.
  $node = node_prepare($node, $teaser);
  */

  if (!empty($node->expansion)) {
    $node->content['g2_expansion'] = [
      '#markup' => theme('g2_field', [
        'name' => 'expansion',
        'title' => t('In other words'),
        'data' => $node->expansion,
      ]),
    ];
  }

  if (!empty($node->period)) {
    $node->content['g2_period'] = [
      '#markup' => theme('g2_field', [
        'name' => 'period',
        'title' => t('Term time period'),
        'data' => $node->period,
      ]),
      '#weight' => 2,
    ];
  }

  // The following line adds invisible text that will be prepended to
  // the node in case some search routine favors the beginning of the
  // body. It can be turned off in case search engines frown upon this.
  if (variable_get(G2\VARHIDDENTITLE, G2\DEFHIDDENTITLE)) {
    $node->content['g2_extra_title'] = [
      '#markup' => '<div class="g2-extra-title">'
        . check_plain($node->title)
        . '</div>',
      '#weight' => -1,
    ];
  }

  return $node;
}

/**
 * Implements hook_view_api().
 */
function Zg2_views_api() {
  return [
    'api' => '3.0',
    'path' => drupal_get_path('module', 'g2') . '/views',
  ];
}

/**
 * Return a themed g2 node pseudo-field, like expansion or period
 *
 * These are not filtered prior to invoking this theme function
 * within g2_view() (unlike D4.x->D6), so function performs filter_xss'ing.
 *
 * @param array $variables
 *   - g2-name: the name of the pseudo-field
 *   - g2-title: the title for the pseudo-field
 *   - g2-data: the contents of the pseudo-field
 *
 * @return string
 *   HTML: the themed pseudo-field.
 */
function Ztheme_g2_field($variables) {
  // Set in code, not by user, so assumed safe.
  $title = $variables['title'];

  $name = 'g2-' . $variables['name'];

  // Set by user, so unsafe.
  $data = filter_xss($variables['data']);

  $ret = <<<EOT
<div class="field field-name-body field-type-text-with-summary field-label-above $name">
<div class="field-label">$title:</div>
<div class="field-item even">
<p>$data</p>
</div><!-- field-item -->
</div><!-- field ... -->
EOT;

  return $ret;
}

/**
 * Theme a random entry.
 *
 * This is actually a short view for just about any single node, but it
 * is even shorter than node_view($node, TRUE).
 *
 * TODO 20110122: replace with just a node rendered with a specific view_mode
 *
 * @return string
 *   HTML: the themed entry.
 */
function Ztheme_g2_random($variables) {
  $node = $variables['node'];
  $uri = entity_uri('node', $node);
  $ret = l($node->title, $uri['path'], $uri['options']);
  if (!empty($node->expansion)) {
    // Why t() ? Because varying languages have varying takes on spaces before /
    // after semicolons.
    $ret .= t(': @expansion', ['@expansion' => $node->expansion]);
  }
  // No longer hard coded: use a view_mode instead.
  // No need to test: also works on missing taxonomy
  // $ret .= G2\entry_terms($node);
  $ret .= theme('more_link', [
      'url' => $uri['path'],
      // TODO check evolution of http://drupal.org/node/1036190.
      'options' => $uri['options'],
      'title' => t('&nbsp;(+)'),
    ]
  );
  return $ret;
}

/**
 * Theme a WOTD block.
 *
 * TODO 20110122: replace with just a node rendered with a specific view_mode
 *
 * @param object $variables
 *   The node for the word of the day. teaser and body are already filtered and
 *   truncated if needed.
 *
 * @return null|string
 *   title / nid / teaser / [body]
 */
function Ztheme_g2_wotd($variables) {
  $node = $variables['node'];
  if (empty($node)) {
    return NULL;
  }
  $uri = entity_uri('node', $node);

  $link = l($node->title, $uri['path'], $uri['options']);
  if (isset($node->expansion) and !empty($node->expansion)) {
    // Teaser already filtered by G2\wotd(), don't filter twice.
    // TODO 20110122 make sure this is true.
    $teaser = '<span id="g2_wotd_expansion">' . strip_tags($node->expansion) . '</span>';
    $ret = t('!link: !teaser', [
      '!link' => $link,
      '!teaser' => $teaser,
    ]);
    unset($teaser);
  }
  else {
    $ret = $link;
  }

// No longer needed: use a view_mode instead
  /*
    if (!empty($node->body)) {
    // already filtered by G2\wotd(), don't filter twice, just strip.
    $body = strip_tags($node->body);
    if ($node->truncated) {
      $body .= '&hellip;';
    }
    $ret .= '<div id="g2_wotd_body">' . $body . '</div>';
  }
  */

  // No need to test: it won't change anything unless taxonomy has been returned
  // $ret .= G2\entry_terms($node);
  $ret .= theme('more_link', [
      'url' => $uri['path'],
      // TODO check evolution of http://drupal.org/node/1036190
      'options' => $uri['options'],
      'title' => t('&nbsp;(+)'),
    ]
  );
  if (variable_get(G2\VARWOTDFEEDLINK, G2\DEFWOTDFEEDLINK)) {
    $ret .= theme('feed_icon', [
      'url' => url(G2\PATHWOTDFEED, ['absolute' => TRUE]),
      // TODO: find a better title.
      'title' => t('Glossary feed'),
    ]);
  }
  return $ret;
}
