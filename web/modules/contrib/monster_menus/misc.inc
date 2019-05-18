<?php

/**
 * @file
 * Miscellaneous MM functions
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTreeInterface;
use Drupal\monster_menus\Routing\OutboundRouteProcessor;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Get a standard "Access Denied" message
 *
 * @param $whole_page
 *   (optional) If TRUE, this is the only content on the page, so show top-
 *   level navigation and send the 403 header.
 * @return string|int|array
 *   The message
 */
function mm_access_denied($whole_page = TRUE) {
  if ($whole_page) {
    throw new AccessDeniedHttpException();
  }

  $path403 = '';
  // First, try the original 403 location, saved by the securesite module.
  if (mm_module_exists('securesite')) {
    $path403 = \Drupal::config('securesite.settings')->get('securesite_403');
  }

  // Failing that, read the Drupal one
  if (empty($path403)) {
    $path403 = \Drupal::config('system.site')->get('page.403');
    if (empty($path403)) return ['#markup' => t('<h2>Password Required</h2>')];
  }

  $request = Request::create($path403);
  return \Drupal::service('http_kernel')->handle($request, HttpKernelInterface::SUB_REQUEST, FALSE);
}

/**
 * Generate a node containing the standard "Access Denied" message
 *
 * @param $node
 *   The node object, whose teaser or body is modified to contain the message
 * @param $teaser
 *   TRUE if the node's teaser should contain the message, otherwise the body is
 *   set
 */
function mm_node_load_fail(NodeInterface &$node, $teaser = FALSE) {
  $node->content = array();
  if ($teaser) {
    $node->content['teaser'] = mm_access_denied(FALSE);
    $node->content['body']['#markup'] = '';
  }
  else {
    $node->content['teaser']['#markup'] = '';
    $node->content['body'] = mm_access_denied(FALSE);
  }
  $node->no_attribution = TRUE;
  $node->setTitle('');
}

/**
 * Redirect the user to a URL, while checking for possible recursion
 *
 * @param Url $url
 *   URL to redirect to
 * @param array $query
 *   Optional query fragment
 * @param string $hash
 *   Optional anchor to appear in the URL after '#'
 * @return RedirectResponse
 *   A Response object
 * @throws BadRequestHttpException
 */
function mm_goto(Url $url, $query = [], $hash = NULL) {
  // Force $url->toString() to return the URL without a base path prefix.
  $url->setOption('base_url', '');
  $u = $url_string = trim($url->toString(), '/');
  $dummy = array();
  mm_module_invoke_all_array('url_outbound_alter', array(&$u, &$dummy, $url_string));
  $current_path = mm_get_current_path();
  if ($url_string == $current_path || $u == $current_path || mm_home_path() . "/$u" == $current_path) {
    \Drupal::logger('mm')->error('Recursive redirect: page=%page', array('%page' => $current_path));
    throw new BadRequestHttpException(t('This page tried to send you into an endless loop. Please contact the administrator, and let him or her know how you got here. Press your browser\'s Back button to return to the page you came from.'));
  }
  $url->setOptions(['query' => $query, 'fragment' => $hash]);
  return new RedirectResponse($url->toString());
}

/**
 * Parse GET parameters in URL
 *
 * @param &$mmtids
 *   Array to receive the list of tree IDs
 * @param &$oarg_list
 *   Optional array to receive the parameters following the tree IDs, un-parsed
 * @param &$this_mmtid
 *   Optional variable to receive the last tree ID (that of the current page)
 * @param $url
 *   URL to parse; defaults to the current page URL
 * @return string
 *   The first GET parameter, usually 'mm'
 */
function mm_parse_args(&$mmtids, &$oarg_list = NULL, &$this_mmtid = NULL, $url = NULL) {
  // Note: Don't try to cache this function, because doing so breaks redirects
  // after moving a page.
  if (is_null($url)) $url = mm_get_current_path();

  $this_mmtid = NULL;
  $mmtids = explode('/', ltrim($url, '/'));
  $oarg_list = array();
  if (($out = array_shift($mmtids)) == 'mm') {       // skip 'mm'
    for ($i = 0; $i < count($mmtids); $i++) {
      $option = array();
      if ($i == count($mmtids) - 1) {
        // Include Constants::MM_GET_PARENTS, to speed up the call to
        // mm_content_get_parents() below.
        $option = Constants::MM_GET_PARENTS;
        // Handle the case of "mm/NNN?foo=bar"
        if (preg_match('{(^[\d\-]+)(\?.*)$}', $mmtids[$i], $matches)) {
          $mmtids[$i] = $matches[1];
          $mmtids[] = $matches[2];
        }
      }
      if (!is_numeric($mmtids[$i]) || !mm_content_get($mmtids[$i], $option)) {
        $oarg_list = array_splice($mmtids, $i);
        break;
      }
    }

    if (count($mmtids)) {
      $this_mmtid = $mmtids[count($mmtids) - 1];
      $parents = mm_content_get_parents($this_mmtid);
      array_shift($parents);    // skip root node
      array_splice($mmtids, 0, -1, $parents);  // insert parents
    }
  }
  else {
    $oarg_list = $mmtids;
    $mmtids = array();
  }
  return $out;
}

/**
 * Get the internal URI of the homepage
 *
 * @return string
 *   The URI
 */
function mm_home_path() {
  return 'mm/' . mm_home_mmtid();
}

/**
 * Get the MM Tree ID of the homepage
 *
 * @return int
 *   The MM Tree ID
 */
function mm_home_mmtid($reset = FALSE) {
  static $cache;

  if ($reset) {
    $cache = NULL;
  }
  if (empty($cache)) {
    $cache = Constants::MM_HOME_MMTID_DEFAULT;
    $list = mm_get_setting('pages.home_mmtid');

    if (!empty($list)) {
      $conf_path = DrupalKernel::findSitePath(Request::createFromGlobals());
      if (isset($list[$conf_path])) {
        $cache = $list[$conf_path];
      }
    }
  }
  return $cache;
}

/**
 * Determine if a given page is a homepage for this site or a multisite.
 *
 * @return bool
 *   TRUE if the page is a homepage.
 */
function mm_is_any_home_mmtid($mmtid) {
  return in_array($mmtid, mm_get_setting('pages.home_mmtid'));
}

function _mm_show_bin_contents($mmtid) {
  $kid = mm_content_get(array('parent' => $mmtid), array(), 1);
  if (empty($kid) || !\Drupal::entityTypeManager()->getStorage('node_type')->load('subpglist')) {
    return [];
  }
  $settings = [
    'type' => 'subpglist',
    'no_attribution' => TRUE,
    'comment' => FALSE,
    'subpglist_hidden_title' => '',
    // Use the default prefix/suffix values stored in the content type and
    // don't filter them. This assumes anyone who has control over the
    // content type knows what they are doing.
    'subpglist_no_filter' => '',
  ];
  $node = Node::create($settings);
  $node->setTitle(t('Pages in this recycle bin:'));
  $node->in_preview = TRUE;
  return node_view($node);
}

/**
 * Return a list of node types for which a flag is TRUE in the
 * hook_mm_node_info()
 *
 * @param $option
 *   The flag to test. Should be one of the MM_NODE_INFO_* constants.
 * @param $item
 *   Tree object for which node info is being requested. May be NULL if no
 *   specific page is being requested.
 * @return array
 *   An array of node types
 */
function mm_get_node_info($option = NULL, $item = NULL) {
  $list = &drupal_static(__FUNCTION__);
  if (!is_array($list)) {
    $list = array();
    foreach (mm_module_invoke_all('mm_node_info', $item) as $node_type => $data) {
      foreach ($data as $field => $value) {
        if (is_array($value) ? array_sum($value) : intval($value)) {
          $list[$field][] = $node_type;
        }
      }
    }
    \Drupal::moduleHandler()->alter('mm_node_info', $list, $item);
  }
  if (empty($option)) return $list;
  return isset($list[$option]) ? $list[$option] : array();
}

/**
 * Redirect the user to an MM URL based on a Drupal node
 *
 * @param int $nid
 *   Drupal node ID to redirect to
 * @param string $hash
 *   Optional anchor to appear in the URL after '#'
 * @return RedirectResponse|bool
 */
function mm_redirect_to_node($nid, $hash = NULL) {
  $mmtids = mm_content_get_by_nid($nid);
  if (!empty($mmtids)) {
    return mm_redirect_to_mmtid($mmtids[0], $hash);
  }
  return FALSE;
}

/**
 * Redirect the user to an MM URL based on an MM tree ID
 *
 * @param int $mmtid
 *   Tree ID to redirect to
 * @param string $hash
 *   Optional anchor to appear in the URL after '#'
 * @return RedirectResponse
 */
function mm_redirect_to_mmtid($mmtid, $hash = NULL) {
  return mm_goto(mm_content_get_mmtid_url($mmtid), NULL, $hash);
}

/**
 * Redirect form processing to a particular MM page or unset redirection.
 *
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state object
 * @param null $mmtid
 *   If NULL, disable redirection. Otherwise, the MM Tree ID of the page to
 *   redirect to.
 */
function mm_set_form_redirect_to_mmtid(FormStateInterface $form_state, $mmtid = NULL) {
  if (empty($mmtid)) {
    $form_state->disableRedirect();
  }
  else {
    $form_state->setRedirect('entity.mm_tree.canonical', ['mm_tree' => $mmtid]);
  }
}

/**
 * Retry a query when it deadlocks.
 *
 * It turns out to be fairly common for MySQL to deadlock when more than one
 * process is updating the same table at the same time. According to the
 * documentation, the proper way to handle this is to retry the query.
 *
 * @param StatementInterface|string $query
 *   A query object, from db_select(), db_delete(), etc. or a query string.
 * @param $args
 *   An array of values to substitute into the query, when $query is a string.
 * @param $options
 *   An array of options to control how the query operates, when $query is a
 *   string.
 * @return StatementInterface|null
 *   Any results from the query.
 * @throws \PDOException
 *   Any exception other than the deadlock
 */
function mm_retry_query($query, array $args = array(), array $options = array()) {
  $tries = 0;
  for (;;) {
    try {
      if (is_object($query)) {
        return $query->execute();
      }
      return Database::getConnection()->query($query, $args, $options);
    }
    catch (\PDOException $e) {
      if (++$tries < 70 && strpos($e->getMessage(), 'Deadlock found when trying to get lock') !== FALSE) {
        // Sleep for .1 sec. at first, then .5 sec after the first 10 tries
        // (31 sec. total).
        usleep($tries <= 10 ? 100000 : 500000);
      }
      else {
        throw $e;
      }
    }
  }
}

/**
 * Update the table containing all results of virtual group queries.
 */
function mm_regenerate_vgroup() {
  $db = Database::getConnection();
  $schema = $db->schema();

  // Intentionally don't use mm_request_time() here, because several minutes may
  // have elapsed between when it was set and the time this code is run.
  $now = time();
  if ($running = \Drupal::state()->get('monster_menus.vgroup_regen_semaphore')) {
    if ($now - $running < 24 * 60 * 60) {
      \Drupal::logger('mm')->error('mm_regenerate_vgroup() is already running.', array());
      return t('mm_regenerate_vgroup() is already running.');
    }
    \Drupal::logger('mm')->error('mm_regenerate_vgroup() has been running for more than a day, or terminated unexpectedly. Ignoring semaphore.', array());
  }

  \Drupal::state()->set('monster_menus.vgroup_regen_semaphore', $now);

  // Delete entries for groups that no longer exist. Unfortunately, we have to
  // do this as two separate queries, because there's no way to do a multi-
  // table delete in Drupal, and MySQL can't do a sub-select during DELETE that
  // refers to the table being deleted from.
  $txn = $db->startTransaction();
  try {
    // SELECT g.vgid FROM {mm_virtual_group} g
    //   LEFT JOIN {mm_vgroup_query} q ON g.vgid = q.vgid
    //   WHERE q.vgid IS NULL
    $inner = $db->select('mm_virtual_group', 'g');
    $inner->addField('g', 'vgid');
    $inner->addJoin('LEFT OUTER', 'mm_vgroup_query', 'q', 'g.vgid = q.vgid');
    $inner->isNull('q.vgid');
    if ($vgids = $inner->execute()->fetchCol()) {
      // DELETE FROM mm_virtual_group WHERE vgid IN (:vgids)
      mm_retry_query($db->delete('mm_virtual_group')
        ->condition('vgid', $vgids, 'IN'));
    }
  }
  catch (\Exception $e) {
    $txn->rollback();
    \Drupal::state()->delete('monster_menus.vgroup_regen_semaphore');

    // Repeat the exception, but with the location below.
    throw new \Exception($e->getMessage());
  }
  // Commit.
  unset($txn);

  $vgids = $email_errors = array();
  // Split the queries up into chunks of mm_vgroup_regen_chunk, so that the SQL
  // buffer size isn't exceeded.
  $chunksize = mm_get_setting('vgroup.regen_chunk');
  $chunks_per_run = mm_get_setting('vgroup.regen_chunks_per_run');

  for ($chunk = 0; $chunk < $chunks_per_run; $chunk++) {
    $i = $chunksize * $chunk;
    $list = array();
    // dirty==MM_VGROUP_DIRTY_FAILED means there was a previous sanity error, so
    // ignore it now.
    // SELECT vg.*, g.gid FROM {mm_vgroup_query} q
    //   INNER JOIN {mm_group} g ON g.vgid = q.vgid
    //   WHERE q.dirty IN(MM_VGROUP_DIRTY_NEXT_CRON, MM_VGROUP_DIRTY_REDO)
    // LIMIT $i, $chunksize
    $query = $db->select('mm_vgroup_query', 'q');
    $query->join('mm_group', 'g', 'g.vgid = q.vgid');
    $result = mm_retry_query($query->fields('q')
      ->fields('g', array('gid'))
      ->condition('q.dirty', array(Constants::MM_VGROUP_DIRTY_NEXT_CRON, Constants::MM_VGROUP_DIRTY_REDO), 'IN')
      ->range($i, $chunksize));
    $nrows = 0;
    $chunk_vgids = array();
    $token_service = \Drupal::token();
    foreach ($result as $r) {
      $qfrom = $token_service->replace($r->qfrom, array('mm_tree' => mm_content_get($r->gid, Constants::MM_GET_FLAGS)), array('clear' => TRUE));
      $list[] = "(SELECT $r->vgid, $r->field $qfrom)";
      $vgids[] = $r->vgid;
      $chunk_vgids[] = $r->vgid;
      $nrows++;
    }

    if (!$nrows) break;

    if (!isset($created)) {
      // We can't use a real temporary table because MySQL has a limitation
      // which prevents temp tables from appearing twice within the same query.
      try {
        $schema->dropTable('mm_virtual_group_temp');
        $schema->createTable('mm_virtual_group_temp', drupal_get_module_schema('monster_menus', 'mm_virtual_group'));
      }
      catch (\Exception $e) {
        \Drupal::logger('mm')->error('Could not create table mm_virtual_group_temp', array());
        \Drupal::state()->delete('monster_menus.vgroup_regen_semaphore');
        return t('Could not create table mm_virtual_group_temp');
      }

      $created = TRUE;
    }

    // Unfortunately, there's no way to do this using db_select() and db_insert().
    $db->query('INSERT INTO {mm_virtual_group_temp} (vgid, uid) ' . join(' UNION ', $list));
    // Delete uids that no longer exist in users table
    // DELETE FROM {mm_virtual_group_temp} WHERE
    //   (SELECT COUNT(*) = 0 FROM {users} WHERE uid = mm_virtual_group_temp.uid)
    $count = $db->select('users');
    $count->addExpression('COUNT(*) = 0');   // countQuery() won't work here
    $count->where('uid = {mm_virtual_group_temp}.uid');
    $db->delete('mm_virtual_group_temp')
      ->condition($count)
      ->execute();

    $result = mm_retry_query('SELECT vgid1 AS vgid, gid, orig_count, IFNULL(temp_count, 0) AS temp_count ' .
      'FROM (' .
        'SELECT * FROM (' .
          'SELECT vgid AS vgid1, COUNT(*) AS orig_count ' .
            'FROM {mm_virtual_group} ' .
            'WHERE vgid IN(:vgids1[]) ' .
            'GROUP BY vgid) ' .
          'AS t1 ' .
        'LEFT JOIN (' .
          'SELECT vgid AS vgid2, COUNT(*) AS temp_count ' .
            'FROM {mm_virtual_group_temp} ' .
            'WHERE vgid IN(:vgids2[]) ' .
            'GROUP BY vgid) ' .
          'AS t2 ' .
        'ON vgid2 = vgid1 ' .
        'WHERE IFNULL(temp_count, 0) < orig_count AND (orig_count - IFNULL(temp_count, 0)) / orig_count > :sanity AND (temp_count IS NULL OR temp_count >= 10)) ' .
      'AS insane ' .
      'INNER JOIN {mm_vgroup_query} vg ON vg.vgid = insane.vgid1 ' .
      'LEFT JOIN {mm_group} g ON g.vgid = insane.vgid1 ' .
      'WHERE vg.dirty <> :dirty',
      array(
        ':vgids1[]' => $chunk_vgids,
        ':vgids2[]' => $chunk_vgids,
        ':sanity' => Constants::MM_VGROUP_COUNT_SANITY,
        ':dirty' => Constants::MM_VGROUP_DIRTY_REDO,
      )
    );
    foreach ($result as $r) {
      $tree = mm_content_get($r->gid);
      $msg = t('The size of the virtual group with vgid=@vgid, gid=@gid, name=@name went down by more than @pct%. It went from @orig to @new users. To ignore this condition and regenerate the virtual group anyway, set its "dirty" field to @redo.', array('@vgid' => $r->vgid, '@gid' => $r->gid, '@name' => $tree->name, '@pct' => Constants::MM_VGROUP_COUNT_SANITY*100, '@orig' => $r->orig_count, '@new' => $r->temp_count, '@redo' => Constants::MM_VGROUP_DIRTY_REDO));
      $email_errors[$r->vgid] = $msg;
      \Drupal::logger('mm')->error($msg, array());
      // Set dirty to MM_VGROUP_DIRTY_FAILED so that the same error is not
      // logged repeatedly
      mm_retry_query($db->update('mm_vgroup_query')
        ->fields(array('dirty' => Constants::MM_VGROUP_DIRTY_FAILED))
        ->condition('vgid', $r->vgid));
      // Don't copy data from temp to real table for this vgid
      $vgid_index = array_search($r->vgid, $vgids);
      if ($vgid_index !== FALSE) {
        array_splice($vgids, $vgid_index, 1);
      }
    }

    if ($nrows < $chunksize) break;
  }

  if ($vgids) {
    // The anonymous user can never be in any groups. Also delete any data for
    // vgroups that are insane.
    // DELETE FROM {mm_virtual_group_temp} WHERE uid = 0 OR vgid NOT IN (:vgids)
    $db->delete('mm_virtual_group_temp')
      ->condition((new Condition('OR'))->condition('uid', 0)->condition('vgid', $vgids, 'NOT IN'))
      ->execute();

    // Update the preview column for mm_content_get_users_in_group().
    $result = mm_retry_query($db->select('mm_virtual_group_temp', 'v')
      ->fields('v', array('vgid'))
      ->groupBy('v.vgid'));
    foreach ($result as $r) {
      $db->query('SET @i=0');
      $query =
        'UPDATE {mm_virtual_group_temp} t ' .
        'INNER JOIN (' .
          'SELECT v.uid, (@i:=@i+1) AS ind FROM {mm_virtual_group_temp} v ' .
            'INNER JOIN {users_field_data} u ON v.vgid = :vgid1 AND u.uid = v.uid ' .
          'ORDER BY u.name' .
        ') AS j ' .
        'ON j.uid = t.uid AND t.vgid = :vgid2 SET preview = IF(j.ind <= 32767, j.ind, NULL)';
      mm_module_invoke_all_array('mm_regenerate_vgroup_preview_alter', array(&$query));
      $db->query($query, array(':vgid1' => $r->vgid, ':vgid2' => $r->vgid));
    }

    // Start a transaction
    $txn = $db->startTransaction();

    try {
      // DELETE FROM {mm_virtual_group} WHERE vgid IN (:vgids)
      mm_retry_query($db->delete('mm_virtual_group')
        ->condition('vgid', $vgids, 'IN'));

      // INSERT INTO {mm_virtual_group}
      //   (SELECT t.vgid, t.uid, t.preview FROM {mm_virtual_group_temp} t)
      $select = $db->select('mm_virtual_group_temp', 't');
      $select->fields('t', array('vgid', 'uid', 'preview'));
      mm_retry_query($db->insert('mm_virtual_group')
        ->from($select));

      // UPDATE {mm_vgroup_query} SET dirty = <MM_VGROUP_DIRTY_NOT>
      //   WHERE vgid IN(:vgids)
      mm_retry_query($db->update('mm_vgroup_query')
        ->fields(array('dirty' => Constants::MM_VGROUP_DIRTY_NOT))
        ->condition('vgid', $vgids, 'IN'));
    }
    catch (\Exception $e) {
      $txn->rollback();
      watchdog_exception('mm', $e);
      \Drupal::state()->delete('monster_menus.vgroup_regen_semaphore');
      // Repeat the exception, but with the location below.
      throw new \Exception($e->getMessage());
    }

    // Commit.
    unset($txn);
  }

  if ($email_errors) {
    $to = mm_get_setting('vgroup.errors_email');
    if (empty($to)) {
      $to = ini_get('sendmail_from');
    }

    $params = array(
      'errors' => $email_errors,
      'in' => join(',', array_keys($email_errors)),
    );
    \Drupal::service('plugin.manager.mail')->mail('monster_menus', 'mm_regenerate_vgroup', $to, \Drupal::languageManager()->getDefaultLanguage()->getId(), $params);
  }

  if (isset($created)) $schema->dropTable('mm_virtual_group_temp');
  $db->query('OPTIMIZE TABLE {mm_virtual_group}');
  \Drupal::state()->delete('monster_menus.vgroup_regen_semaphore');
  return t('Virtual groups have been regenerated.');
}

/**
 * Mark the pre-defined group "All logged-in users" dirty, so it will be rebuilt
 * during the next cron run.
 */
function mm_mark_all_logged_in_vgroup_dirty() {
  static $done;

  // Only do this once per page load, to avoid excessive updates if multiple
  // users are inserted/deleted.
  if (empty($done)) {
    Database::getConnection()->update('mm_vgroup_query')
      ->fields(array('dirty' => 1))
      ->condition('qfrom', 'FROM {users} WHERE uid > 0')
      ->execute();
    $done = TRUE;
  }
}

/**
 * Store some data to be added at a future point to the footer region of the
 * page.
 *
 * @param $content
 *   The content to add; a render array is preferred
 * @return array
 *   An array containing all data added so far
 */
function mm_add_page_footer($content = NULL) {
  static $data = array();

  if (!empty($content)) {
    if (is_string($content)) $content = array('#markup' => $content);
    $data[] = $content;
  }
  return $data;
}

/**
 * @defgroup mm_hooks Monster Menus Hooks
 * @{
 * Allow modules to interact with Monster Menus.
 */

/**
 * Get a list of all enabled modules and MM sub-modules that implement a hook.
 *
 * @param $hook
 *   The name of the hook to query
 * @return array
 *   An array of module names
 */
function mm_module_implements($hook) {
  static $cache = array();

  if (isset($cache[$hook])) {
    return $cache[$hook];
  }

  $list = \Drupal::moduleHandler()->getImplementations($hook);
  foreach (mm_node_types() as $desc) {
    if (function_exists($desc['base'] . '_' . $hook)) {
      $list[] = $desc['base'];
    }
  }
  $cache[$hook] = $list;
  return $list;
}

/**
 * Invoke a hook in all enabled modules and MM sub-modules that implement it.
 *
 * @param $hook
 *   The name of the hook to invoke.
 * @param ...
 *   Arguments to pass to the hook.
 * @return array
 *   An array of return values of the hook implementations. If modules return
 *   arrays from their implementations, those are merged into one array.
 */
function mm_module_invoke_all() {
  $args = func_get_args();
  $hook = array_shift($args);
  $return = array();
  foreach (mm_module_implements($hook) as $module) {
    $function = $module . '_' . $hook;
    $result = call_user_func_array($function, $args);
    if (isset($result) && is_array($result)) {
      $return = NestedArray::mergeDeepArray([$return, $result]);
    }
    elseif (isset($result)) {
      $return[] = $result;
    }
  }

  return $return;
}

/**
 * Invoke a hook in all enabled modules and MM sub-modules that implement it.
 * Unlike mm_module_invoke_all(), any references are preserved.
 *
 * @param $hook
 *   The name of the hook to invoke.
 * @param $args
 *   Arguments to pass to the hook. Any references in the array are preserved.
 * @return array
 *   An array of return values of the hook implementations. If modules return
 *   arrays from their implementations, those are merged into one array.
 */
function mm_module_invoke_all_array($hook, $args) {
  $return = array();
  foreach (mm_module_implements($hook) as $module) {
    $function = $module . '_' . $hook;
    $result = call_user_func_array($function, $args);
    if (isset($result) && is_array($result)) {
      $return = NestedArray::mergeDeepArray([$return, $result]);
    }
    elseif (isset($result)) {
      $return[] = $result;
    }
  }

  return $return;
}

function mm_module_exists($module) {
  return \Drupal::moduleHandler()->moduleExists($module);
}

/**
 * @} End of "defgroup mm_hooks".
 */

// ****************************************************************************
// * Private functions start here
// ****************************************************************************

function _mm_render_pages($mmtids, &$page_title, $oarg_list, &$err, $no_attribution = FALSE, $allow_rss = TRUE, $block_id = 0) {
  $_mm_page_subscribe_item = &drupal_static('_mm_page_subscribe_item');

  $err = $page_title = '';
  $output = array();
  $no_read = $ok = 0;
  $this_mmtid = $mmtids[count($mmtids) - 1];
  $db = Database::getConnection();

  // Check for mm_showpage callbacks, specified in hook_mm_showpage_routing()
  $showpage_no_nodes = FALSE;
  $router = _mm_showpage_router();
  if ($router) {
    $temp_path = trim(mm_content_get_mmtid_url($this_mmtid, ['base_url' => ''])->toString(), '/');
    $temp_path = join('/', array_merge(array($temp_path), $oarg_list));
    $temp_args = explode('/', $temp_path);
    $mm_region_contents = &drupal_static('mm_region_contents', array());

    foreach ($router as $key => $item) {
      if (preg_match($key, $temp_path) && (!isset($item['block id']) || $item['block id'] == $block_id) && _mm_showpage_callback($item, 'access', $temp_args, $oarg_list, $this_mmtid, $block_id)) {
        $showpage_output = _mm_showpage_callback($item, 'page', $temp_args, $oarg_list, $this_mmtid, $block_id);
        if (is_array($showpage_output)) {
          if (isset($showpage_output['by_region'])) {
            // Save all content in regions other than 'content' for later.
            foreach ($showpage_output['by_region'] as $region => $content) {
              if ($region != 'content') {
                $mm_region_contents[$region][] = $content;
              }
            }
            // Process just the 'content' region now.
            if (isset($showpage_output['by_region']['content'])) {
              if (!is_array($showpage_output['by_region']['content'])) {
                $showpage_output = array('output_post' => $showpage_output['by_region']['content'], 'no_nodes' => !empty($showpage_output['no_nodes']));
              }
              else {
                $showpage_output = $showpage_output['by_region']['content'];
              }
            }
            else {
              $showpage_output = array('no_nodes' => !empty($showpage_output['no_nodes']));
            }
          }
          if (isset($showpage_output['output_pre'])) {
            if (!isset($output['output_pre'])) {
              $output['output_pre']['#weight'] = -1000;
            }
            $output['output_pre'][] = is_array($showpage_output['output_pre']) ? $showpage_output['output_pre'] : array('#type' => 'item', '#markup' => $showpage_output['output_pre']);
          }
          if (isset($showpage_output['output_post'])) {
            if (!isset($output['output_post'])) {
              $output['output_post']['#weight'] = 1000;
            }
            $output['output_post'][] = is_array($showpage_output['output_post']) ? $showpage_output['output_post'] : array('#type' => 'item', '#markup' => $showpage_output['output_post']);
          }
          if (isset($showpage_output['no_nodes'])) {
            $showpage_no_nodes |= $showpage_output['no_nodes'];
          }
        }
        elseif (!empty($showpage_output)) {
          if (!isset($output['output_post'])) {
            $output['output_post']['#weight'] = 1000;
          }
          $output['output_post'][] = array('#type' => 'item', '#markup' => $showpage_output);
          $showpage_no_nodes = FALSE;
        }
      }
    }
  }

  $showpage_show_nodes = !$showpage_no_nodes;
  $nodes_per_page = mm_content_resolve_cascaded_setting('nodes_per_page', $this_mmtid, $npp_at, $npp_parent);
  if (empty($nodes_per_page) && $nodes_per_page !== 0 || $nodes_per_page == -2 && $block_id) {
    $nodes_per_page = Constants::MM_DEFAULT_NODES_PER_PAGE;
  }

  $perms = mm_content_user_can($this_mmtid);
  if (!$perms[Constants::MM_PERMS_READ]) {
    $no_read++;
  }
  elseif ((count($mmtids) == 1 || count($mmtids) == 2 && mm_get_setting('user_homepages.virtual')) && $mmtids[0] == mm_content_users_mmtid()) {
    if ($oarg_list) {
      if (!mm_get_setting('user_homepages.enable') || count($oarg_list) != 1 || !($usr = user_load_by_name($oarg_list[0])) || !$usr->status) {
        throw new NotFoundHttpException();
      }
      $err = 'missing homepage';
      return array();
    }

    if (\Drupal::currentUser()->hasPermission('access user profiles')) {
      $page_title = t('User Search');
      $output[] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $page_title,
        '#attributes' => [],
        'user_list' => [
          '#prefix' => '<div class="content">',
          'form' => \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\ListUsersForm'),
          '#suffix' => '</div>',
        ],
      ];
    }
    else {
      $output[] = ['#markup' => ''];
    }
    $ok++;
  }
  elseif ($showpage_show_nodes) {
    $item = mm_content_get($this_mmtid, Constants::MM_GET_ARCHIVE);
    if (!_mm_render_nodes_on_page($item, $perms, $nodes_per_page, $oarg_list, $no_attribution, $output, $ok, $no_read, $pager_elem, $archive_tree, $archive_date_int, $rss_link)) {
      throw new NotFoundHttpException();
    }

    if ($ok && isset($oarg_list[0]) && $oarg_list[0] == 'feed') {
      if (mm_module_exists('views')) {
        if ($view = Views::getView('mm_rss_feed')) {
          $result = $db->queryRange(
            mm_content_get_accessible_nodes_by_mmtid_query($this_mmtid, $count_sql),
            0, \Drupal::config('system.rss')->get('items.limit'));
          $nids = array();
          foreach ($result as $row) {
            if ($row->scheduled) {
              $nids[] = $row->nid;
            }
          }
          $view->setDisplay('feed_1');
          $display = $view->getDisplay();
          $display->setOption('title', Xss::filterAdmin(\Drupal::config('system.site')->get('name') . ': ' . $item->name));
          $view->override_url = mm_content_get_mmtid_url($this_mmtid);
          $view->setArguments([implode('+', $nids)]);
          return ['view' => $view];
        }
        else {
          \Drupal::logger('mm')->error('An RSS feed was requested, however the mm_rss_feed view is not enabled.');
          throw new NotFoundHttpException();
        }
      }
      else {
        \Drupal::logger('mm')->error('An RSS feed was requested, however the views module is not enabled.');
        throw new NotFoundHttpException();
      }
    }

    // not a feed
    if ($block_id == 0 && empty($_mm_page_subscribe_item)) {
      $_mm_page_subscribe_item = $item;
    }

    if ($ok) {
      if ($allow_rss && ($item->rss || !mm_get_setting('pages.enable_rss'))) {
        $output['#attached']['html_head_link'][][] = [
          'rel' => 'alternate',
          'type' => 'application/rss+xml',
          'title' => t('RSS'),
          'href' => !empty($rss_link) ? $rss_link : Url::fromRoute('monster_menus.show_page_feed', ['mm_tree' => $this_mmtid], array('absolute' => TRUE))->toString(),
        ];
      }

      if (isset($item->main_mmtid) || isset($item->archive_mmtid)) {
        if ($this_mmtid == $item->archive_mmtid) {
          $output[] = [
            [
              '#theme' => 'mm_archive_header',
              '#frequency' => $item->frequency,
              '#date' => $archive_date_int,
            ],
            '#weight' => -1,
          ];
        }
        $output[] = [
          [
            '#theme' => 'mm_archive',
            '#list' => $archive_tree,
            '#frequency' => $item->frequency,
            '#this_mmtid' => $this_mmtid,
            '#main_mmtid' => $item->main_mmtid,
            '#archive_mmtid' => $item->archive_mmtid,
            '#date' => $archive_date_int,
          ],
          '#weight' => 100000,
        ];
      }
      elseif (isset($pager_elem)) {
        $output[] = array(
          '#type' => 'pager',
          '#tags' => NULL,
          '#element' => $pager_elem,
          '#weight' => (count($output) + 1) / 10000.0,
        );
      }
    }
  }
  elseif ($block_id == 0 && empty($_mm_page_subscribe_item)) {
    // Allow permalink to work even on pages with nodes suppressed by mm_showpage_routing
    $_mm_page_subscribe_item = mm_content_get($this_mmtid, Constants::MM_GET_ARCHIVE);
  }

  if ($output || $ok) {
    if ($nodes_per_page == -2) {
      mm_content_get_accessible_nodes_by_mmtid_query($this_mmtid, $count_sql);
      $total_nodes = $db->query($count_sql)->fetchColumn();
      $total_pages = ceil($total_nodes / Constants::MM_LAZY_LOAD_NUMBER_OF_NODES);
      $output[] = array('#markup' => '<input type="hidden" value="0" class="mm-lazy-load-max-page">', '#weight' => 1);
      mm_static($output, 'lazy_load_node', $this_mmtid, $total_pages);
    }
  }

  if ($output || $ok) {
    return array('mm_nodes' => $output);
  }

  $err = $no_read ? 'no read' : 'no content';
  return array();
}

// display a list of pages assigned to a tree entry
function _mm_render_nodes_on_page($item, $perms, $nodes_per_page, $oarg_list, $no_attribution, &$output, &$ok, &$no_read, &$pager_elem, &$archive_tree, &$archive_date_int, &$rss_link) {
  $_mm_mmtid_of_node = &drupal_static('_mm_mmtid_of_node');
  $rss_link = NULL;
  $archive_date_int = 0;
  $archive_tree = array();

  $result = NULL;
  if (isset($item->main_mmtid) || isset($item->archive_mmtid)) {
    // This is an archive page, or the main page for which there is an archive
    if ($item->mmtid > 0) {
      $mmtid = isset($item->main_mmtid) ? $item->main_mmtid : $item->mmtid;
      if (!mm_content_user_can($mmtid, Constants::MM_PERMS_READ)) {
        $no_read++;
        return TRUE;
      }
      $q = mm_content_get_accessible_nodes_by_mmtid_query($mmtid, $count_sql);
      $result = Database::getConnection()->query($q);
    }
  }
  elseif (count($oarg_list) && !$output) {
    // if the remaining parameters in the URL can't be accounted for, it's a dead link
    $other_mmtids = array_diff($oarg_list, mm_content_reserved_aliases());
    if (count($other_mmtids)) {
      return FALSE;
    }
  }

  if (is_null($result)) {
    if (!mm_content_user_can($item->mmtid, Constants::MM_PERMS_READ)) {
      $no_read++;
      return TRUE;
    }

    $omit_nodes = '';
    if (!$perms[Constants::MM_PERMS_IS_RECYCLED]) {
      $omit_node_types = mm_get_node_info(Constants::MM_NODE_INFO_NO_RENDER, $item);
      if ($omit_node_types) {
        $omit_nodes = " AND n.type NOT IN('" . join("', '", $omit_node_types) . "')";
      }
    }

    $pager_elem = $nodes_per_page > 0 ? PagerSelectExtender::$maxElement++ : NULL;
    $result = mm_content_get_accessible_nodes_by_mmtid($item->mmtid, $nodes_per_page, $pager_elem, '', '', $omit_nodes . ' AND r.region IS NULL');
  }

  if (isset($item->archive_mmtid)) {
    $archive_count = 0;
    if (($get_date = \Drupal::request()->get('_date', '')) && preg_match('/([12]\d\d\d)-(0[1-9]|1[0-2])-([0123]\d)/', $get_date, $matches)) {
      $archive_date = array(
        'year' => intval($matches[1]),
        'mon' =>  intval($matches[2]),
        'mday' => intval($matches[3]));
    }
  }

  $nids = $scheduled = array();
  foreach ($result as $n) {
    $ok++;
    $_mm_mmtid_of_node[$n->nid] = $item->mmtid;
    $scheduled[$n->nid] = !empty($n->scheduled);
    if (empty($oarg_list) || $oarg_list[0] != 'feed') {
      // This is an archive page, or the main page for which there is an archive,
      // and the node is not stuck on the main page, and it's always visible to everyone
      if (isset($item->archive_mmtid) && ($item->archive_mmtid == $item->mmtid || !$n->stuck && $n->scheduled && $n->status == 1)) {
        // skip this node if viewing the main page of an archive and we've seen main_nodes # of non-sticky, always-appearing nodes
        $archive_show = FALSE;
        if (++$archive_count > $item->main_nodes || $item->archive_mmtid == $item->mmtid && !$n->stuck && $n->scheduled && $n->status == 1) {
          $date = getdate($n->created);
          switch ($item->frequency) {
            case 'year':
              $rounded = mktime(0, 0, 0, 1, 1, $date['year']);
              $archive_tree[$date['year']] = $rounded;
              $archive_show = isset($archive_date) && $archive_date['year'] == $date['year'];
              break;

            case 'month':
              $rounded = mktime(0, 0, 0, $date['mon'], 1, $date['year']);
              $archive_tree[$date['year']][$date['mon']] = $rounded;
              $archive_show = isset($archive_date) && $archive_date['year'] == $date['year'] && $archive_date['mon'] == $date['mon'];
              break;

            case 'week':
              $rounded = mktime(0, 0, 0, $date['mon'], $date['mday']-$date['wday'], $date['year']);
              $date = getdate($rounded);
              $archive_tree[$date['year']][$date['mon']][$date['mday']] = $rounded;
              $archive_show = isset($archive_date) && $archive_date['year'] == $date['year'] && $archive_date['mon'] == $date['mon'] && $archive_date['mday'] == $date['mday'];
              break;

            case 'day':
              $rounded = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
              $archive_tree[$date['year']][$date['mon']][$date['mday']] = $rounded;
              $archive_show = isset($archive_date) && $archive_date['year'] == $date['year'] && $archive_date['mon'] == $date['mon'] && $archive_date['mday'] == $date['mday'];
              break;
          }
          if (!isset($archive_date)) {
            // not in URL, so default to most recent node's date, rounded down
            $archive_date = getdate($rounded);
            $archive_show = TRUE;
          }
          if ($archive_show) $archive_date_int = $rounded;
        }

        // skip this node if viewing an archive page and it's sticky
        if ($item->archive_mmtid == $item->mmtid && ($n->stuck || !$n->scheduled || !$archive_show)) continue;
        // skip this node if viewing the main page and we've already seen main_nodes # of nodes
        if ($item->main_mmtid == $item->mmtid && $archive_count > $item->main_nodes) continue;
      }

      $nids[] = $n->nid;
    }
  }

  _mm_render_nodes($nids, $scheduled, $item->previews ? 'teaser' : 'full', $no_attribution, $output, $rss_link);
  return TRUE;
}

function _mm_render_nodes($nids, $scheduled, $view_mode, $no_attribution, &$output, &$rss_link) {
  /** @var NodeInterface $node */
  foreach (Node::loadMultiple($nids) as $node) {
    $message = '';
    if (!$node->isPublished()) {
      $message = t('This piece of content is not yet published. It can only be seen by people who can edit it.');
    }
    elseif (empty($scheduled[$node->id()])) {
      $message = t('This piece of content can only be seen by people who can edit it, due to its publishing schedule.');
    }

    if ($no_attribution) {
      $node->no_attribution = TRUE;
    }
    \Drupal::moduleHandler()->alter('mm_node_show', $node, $view_mode);
    $body = node_view($node, $view_mode);
    // $node->no_display is a custom field that can be set by a hook_view module, such as rss_page_view
    if (empty($node->no_display)) {
      if ($message) {
        // Save old messages for restore later.
        $messenger = \Drupal::messenger();
        $old_messages = $messenger->deleteAll();
        $messenger->addMessage($message);
        $body = array(
          '#prefix' => '<div class="preview">',
          'message' => StatusMessages::renderMessages(),
          'body' => $body,
          '#suffix' => '</div>',
        );
        // Restore original messages.
        foreach ($old_messages as $type => $messages) {
          foreach ($messages as $message) {
            $messenger->addMessage($message, $type);
          }
        }
      }
      $output[] = array(
        '#prefix' => '<a name="node-' . $node->id() . '"></a>',
        'body' => $body,
        '#weight' => (count($output) + 1) / 10000.0,
        '#cache' => ['tags' => $node->getCacheTagsToInvalidate()],
      );
    }

    $rss_link = is_null($rss_link) && !empty($node->rss_link) ? $node->rss_link : FALSE;
  }
}

function _mm_resolve_archive(&$mmtid) {
  $mmtid = intval($mmtid);
  if ($mmtid) {
    $tree = mm_content_get($mmtid, Constants::MM_GET_ARCHIVE);
    if (isset($tree->main_mmtid) && $tree->archive_mmtid == $mmtid) {
      if (!mm_content_user_can($mmtid, Constants::MM_PERMS_READ) || !mm_content_user_can($tree->main_mmtid, Constants::MM_PERMS_READ)) {
        mm_access_denied();
        return FALSE;
      }
      return $tree->main_mmtid;
    }
    return $mmtid;
  }
  return FALSE;
}

function _mm_showpage_router($reset = FALSE) {
  $router = &drupal_static(__FUNCTION__);

  if (!isset($router) || $reset) {
    if (!$reset && ($cache = \Drupal::cache()->get('mm_showpage')) && isset($cache->data)) {
      $router = $cache->data;
    }
    else {
      $callbacks = array();
      foreach (mm_module_implements('mm_showpage_routing') as $module) {
        $router_items = call_user_func($module . '_mm_showpage_routing');
        if (isset($router_items) && is_array($router_items)) {
          foreach (array_keys($router_items) as $path) {
            if (!isset($router_items[$path]['module'])) {
              $router_items[$path]['module'] = $module;
            }
          }
          $callbacks = array_merge($callbacks, $router_items);
        }
      }

      $router = $sort = array();
      foreach ($callbacks as $path => $item) {
        list($fit,) = _mm_showpage_router_fit($path, isset($item['partial path']) ? $item['partial path'] : '');
        $ending = !empty($item['partial path']) ? '(?:$|/)}' : '$}';
        $path = '{^' . str_replace(array('%', '\\*'), array('[^/]+', '[^/]*'), preg_quote($path)) . $ending;
        $sort[$path] = $fit;

        if (!isset($item['access callback']) && isset($item['access arguments'])) {
          // Default callback.
          $item['access callback'] = 'mm_content_user_can';
        }
        if (empty($item['page callback'])) {
          $item['access callback'] = FALSE;
        }
        elseif (!isset($item['access callback'])) {
          $item['access callback'] = 'mm_content_user_can';
          $item['access arguments'] = array('_mmtid_', Constants::MM_PERMS_READ);
        }
        $item += array(
          'access arguments' => array(),
          'access callback' => '',
          'page arguments' => array(),
          'page callback' => '',
          'file' => '',
        );
        $router[$path] = $item;
      }
      array_multisort($sort, SORT_NUMERIC, SORT_DESC, $router);

      \Drupal::cache()->set('mm_showpage', $router);
    }
  }

  return $router;
}

function _mm_showpage_callback($item, $type, $args, $oargs, $this_mmtid, $block_id) {
  $callback = $item["$type callback"];
  if (is_bool($callback)) {
    return $callback;
  }

  if (!empty($item['file'])) {
    $file = DRUPAL_ROOT . '/' . drupal_get_path('module', $item['module']) . '/' . $item['file'];
    require_once $file;
  }

  $callback = trim($callback);
  try {
    $callback = \Drupal::service('controller_resolver')->getControllerFromDefinition($callback);
  }
  catch (\Exception $e) {
    return FALSE;
  }

  $arguments = $item["$type arguments"];
  $all = array();
  foreach ($arguments as $k => $v) {
    if (is_int($v)) {
      $arguments[$k] = isset($args[$v]) ? $args[$v] : '';
    }
    elseif ($v === '_mmtid_') {
      $arguments[$k] = $this_mmtid;
    }
    elseif ($v === '_block_id_') {
      $arguments[$k] = $block_id;
    }
    elseif ($v === '_all_') {
      array_unshift($all, $k);
    }
    elseif ($v === '_oargs_') {
      array_unshift($oargs, $k);
    }
  }

  foreach ($all as $k) {
    array_splice($arguments, $k, 1, $args);
  }

  return call_user_func_array($callback, $arguments);
}

function _mm_showpage_router_fit($path, $partial_path) {
  $fit = 0;
  $parts = explode('/', $path);
  $number_parts = count($parts);
  foreach ($parts as $k => $part) {
    if ($part != '%') {
      $fit |= 1 << ($number_parts - 1 - $k);
    }
  }

  if (!$fit) {
    // If there is no %, it fits maximally.
    $fit = (1 << $number_parts) - 1;
  }
  $fit = ($fit << 1) + ($partial_path ? 0 : 1);

  return array($fit, $number_parts);
}

function _mm_report_error($message, $vars, &$stats) {
  if (is_array($stats)) {
    $stats['errors'][] = array('message' => $message, 'vars' => $vars);
    if (isset($stats['suppress_errors'])) {
      return;
    }
  }
  \Drupal::messenger()->addStatus(t($message, $vars));
  \Drupal::logger('mm')->error($message, $vars);
}

function _mm_report_stat($is_group, $mmtid, $message, $vars, &$stats, $watchdog_notice = FALSE) {
  if (is_numeric($mmtid)) {
    // This might be called during install before the menu route exists.
    try {
      $vars['@mmtid'] = Link::fromTextAndUrl($mmtid, mm_content_get_mmtid_url($mmtid))->toString();
    }
    catch (\Exception $e) {
      $vars['@mmtid'] = $mmtid;
    }
  }
  if (is_array($stats)) {
    $vars['@thing'] = $is_group ? 'group' : 'page';
    $stats[$is_group ? 'groups' : 'pages'][$mmtid][] = array('message' => $message, 'vars' => $vars);
  }
  if ($watchdog_notice) {
    \Drupal::logger('mm')->notice($message, $vars);
  }
}

function mm_var_export_html($var) {
  return '<pre>' . Html::escape(var_export($var, TRUE)) . '</pre>';
}

function mm_default_setting($setting, $default) {
  $result = mm_get_setting($setting);
  if (is_null($result)) {
    return $default;
  }
  return $result;
}

function mm_add_js_setting(&$array, $key, $settings) {
  $array['#attached']['drupalSettings']['MM'][$key] = $settings;
}

function mm_add_library(&$array, $library) {
  $array['#attached']['library'][] = "monster_menus/$library";
}

function mm_get_setting($setting, $default = NULL) {
  static $config;

  if (empty($config)) {
    $config = \Drupal::config('monster_menus.settings');
  }
  $ret = $config->get($setting);
  return is_null($ret) ? $default : $ret;
}

function mm_format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
  return \Drupal::service('date.formatter')->format($timestamp, $type, $format, $timezone, $langcode);
}

function mm_set_current_path($path) {
  $request = \Drupal::service('request_stack');
  $current_request = $request->getCurrentRequest();
  $subrequest = Request::create($path, 'GET', $current_request->query->all(), $current_request->cookies->all(), array(), $current_request->server->all());
  return \Drupal::service('http_kernel')->handle($subrequest, HttpKernelInterface::SUB_REQUEST);
}

function mm_get_current_path() {
  return trim(\Drupal::service('path.current')->getPath(), '/');
}

function mm_json_response($list, $headers = array()) {
  $headers[] = ['Content-Type' => 'application/json'];
  return new JsonResponse($list, 200, $headers);
}

/**
 * Determine if the current user can create a particular type of node.
 *
 * @param string $type
 *   The machine name of the node type.
 * @param AccountInterface $account
 *   The user account being queried, or NULL to default to the current user.
 * @return bool
 *   TRUE if the user has access.
 */
function mm_node_access_create($type, AccountInterface $account = NULL) {
  return \Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess($type, $account);
}

/**
 * Return an HTML link for a given menu route. It is better to get a render
 * array using Link::createFromRoute()->toRenderable() when possible.
 *
 * @param string $text
 *   The text of the link.
 * @param string $route_name
 *   The name of the route
 * @param array $route_parameters
 *   (optional) An associative array of parameter names and values.
 * @param array $options
 *   (optional) An associative array of additional options, with the following
 *   elements:
 *   - 'query': An array of query key/value-pairs (without any URL-encoding)
 *     to append to the URL. Merged with the parameters array.
 *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
 *     Do not include the leading '#' character.
 *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
 *     absolute link (beginning with http:). Useful for links that will be
 *     displayed outside the site, such as in an RSS feed.
 *   - attributes: An associative array of HTML attributes to apply to the
 *     anchor tag. If element 'class' is included, it must be an array; 'title'
 *     must be a string; other elements are more flexible, as they just need to
 *     work as an argument for the constructor of the class
 *     Drupal\Core\Template\Attribute($options['attributes']).
 *   - 'language': An optional language object used to look up the alias
 *     for the URL. If $options['language'] is omitted, it defaults to the
 *     current language for the language type LanguageInterface::TYPE_URL.
 *   - 'https': Whether this URL should point to a secure location. If not
 *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
 *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP.
 * @return string
 *   The HTML of the link.
 */
function mm_route_link_html($text, $route_name, $route_parameters = array(), $options = array()) {
  return Link::createFromRoute($text, $route_name, $route_parameters, $options)->toString();
}

function mm_get_args($path = NULL) {
  return explode('/', trim($path ? $path : \Drupal::request()->getPathInfo(), '/'));
}

/**
 * Get the list of blocks created by MM.
 *
 * @param mixed[] $filters
 *   Optional list of filters to apply
 * @return Drupal\block\Entity\Block[]
 */
function mm_get_mm_blocks($filters = []) {
  $list = \Drupal::entityTypeManager()->getStorage('block')->loadByProperties($filters + ['status' => TRUE, 'settings.provider' => 'monster_menus']);
  // Translate the keys using the delta set during creation.
  $result = [];
  /** @var Drupal\block\Entity\Block $block */
  foreach ($list as $key => $block) {
    $result[$block->get('settings')['delta']] = $block;
  }
  return $result;
}

/**
 * Get the current page associated with the request. Defaults to the request
 * itself, unless the request is for a contextual menu, in which case an
 * attempt is made to use the referer. This value is not secure, but that
 * doesn't matter, since access checks are handled elsewhere.
 *
 * @return string
 */
function _mm_active_menu_item_get_current_page() {
  $path = \Drupal::request()->getPathInfo();
  if ($path === \Drupal::state()->get('monster_menus.contextual_render_path') && ($referer = \Drupal::request()->headers->get('referer'))) {
    $referer_path = parse_url($referer, PHP_URL_PATH);
    $base_slash = base_path();
    $base_len = strlen($base_slash);
    if (!strncmp($referer_path, $base_slash, $base_len)) {
      return '/' . substr($referer_path, $base_len);
    }
  }
  return $path;
}

function mm_active_menu_item($path = NULL) {
  $_mm_page_args = &drupal_static('_mm_page_args');
  $_mm_mmtid_of_node = &drupal_static('_mm_mmtid_of_node');

  if (!is_scalar($path)) $path = NULL;

  $cache = &drupal_static(__FUNCTION__, array());
  if (!isset($cache[$path])) {
    $cache[$path] = (object) array();
    $mm_path = is_null($path) ? _mm_active_menu_item_get_current_page() : $path;
    $arg = mm_get_args(\Drupal::service('monster_menus.path_processor_inbound')->processInboundPath($mm_path, $mm_path, FALSE));
    if ($arg[0] == 'node') {
      if (isset($arg[1])) {
        $nid = $arg[1];
      }
    }
    elseif ($arg[0] == 'comment') {
      // Unfortunately we can't use routes here.
      if (isset($arg[2]) && $arg[2] == 'reply') {
        $nid = $arg[1];
      }
      else if (isset($arg[2]) && $arg[1] == 'reply') {
        if ($arg[2] == 'node' && isset($arg[3])) {
          $nid = $arg[3];
        }
      }
      elseif (!isset($arg[2]) || in_array($arg[2], array('delete', 'edit', 'approve'))) {
        if (intval($arg[1]) && ($row = \Drupal::service('entity.manager')->getStorage('comment')->load(intval($arg[1])))) {
          $nid = $row->getTypedData()->get('entity_id')->getValue()[0]['target_id'];
        }
      }
    }

    if (isset($nid)) {
      $nid = intval($nid);
      if ($nid) {
        $mmtids = mm_content_get_by_nid($nid);
        if (!empty($mmtids)) {
          $found_nid = $nid;
          $cache[$path]->mmtid = $mmtids[0];
          if (!$path) {
            $_mm_page_args = 'mm/' . $mmtids[0];
          }
        }
      }
      else if ($from_query = OutboundRouteProcessor::getMmtidFromQuery()) {
        $_mm_page_args = 'mm/' . $from_query;
      }
    }
    else if ($arg[0] == 'mm' || is_numeric($arg[0])) {
      if ($arg[0] == 'mm') {
        array_shift($arg);
      }
      // Remove multiple /NN/NN numbers, saving only the furthest.
      while (count($arg) > 1 && is_numeric($arg[1])) {
        array_shift($arg);
      }
      $resolved = FALSE;
      if (isset($arg[1]) && $arg[1] == 'node' && isset($arg[2]) && is_numeric($arg[2])) {
        $query = Database::getConnection()->select('node', 'n')
          ->fields('n', array('nid'));
        $query->condition('n.nid', $arg[2]);
        $found_nid = $query->execute()
          ->fetchField();
        if ($found_nid && $found_nid == $arg[2]) {
          $resolved = TRUE;
          if (_mm_resolve_archive($arg[0]) && !$path) {
            $_mm_page_args = "mm/$arg[0]";
          }
          $_mm_mmtid_of_node[$found_nid] = $arg[0];
        }
        else {
          unset($found_nid);
        }
      }

      // If $resolved==TRUE, _mm_resolve_archive() was already called.
      if (!isset($found_nid) && ($resolved || _mm_resolve_archive($arg[0]))) {
        $cache[$path]->mmtid = $arg[0];
        if (empty($path)) {
          $_mm_page_args = "mm/$arg[0]";
        }
      }
    }
    $cache[$path]->nid = isset($found_nid) ? $found_nid : NULL;
  }
  return $cache[$path];
}

/**
 * Entity URI callback.
 */
function mm_tree_uri(MMTreeInterface $mm_tree) {
  return new Url(
    'entity.mm_tree.canonical',
    array('mm_tree' => $mm_tree->id())
  );
}

/**
 * Drupal 8 forces MySQL/MariaDB into ONLY_FULL_GROUP_BY mode. This means that
 * the SQL interpreter complains when selected columns are not present in a
 * GROUP BY clause. This is supposed to prevent the possibility of returning
 * indeterminate data, however MM does this intentionally in many places.
 * Therefore, this function will return a string which contains all (or most) of
 * the columns in a particular table with MAX() around them, to avoid the error.
 * The end result of the query is the same, and there is little overhead.
 *
 * @param $table
 *   The name of the table being queried.
 * @param array $except
 *   Optional array of columns to exclude.
 * @param string $prefix
 *   Optional prefix for each column name, such as "alias.".
 * @return string
 *   The comma-separated list of columns.
 */
function mm_all_db_columns($table, array $except = [], $prefix = '') {
  $out = [];
  foreach ($except as $col) {
    $out[] = "$prefix$col";
  }
  foreach (array_diff(mm_get_table_columns($table), $except) as $col) {
    $out[] = "MAX($prefix$col) AS $col";
  }
  return implode(', ', $out);
}

/**
 * Get a list of column names for a given database table used by MM.
 *
 * @param string $table
 *   The name of the table (or entity) being queried.
 * @return array
 *   The list of column names.
 */
function mm_get_table_columns($table) {
  static $cache;

  if (!isset($cache[$table])) {
    if ($non_entity = drupal_get_module_schema('monster_menus', $table)) {
      $cache[$table] = array_keys($non_entity['fields']);
    }
    else {
      $base = preg_replace('/_revision$/', '', $table);
      $cache[$table] = \Drupal::service('entity.manager')->getStorage($base)->getTableMapping()->getFieldNames($table);
    }
  }
  return $cache[$table];
}

/**
 * Filter an associative array, discarding entries not part of a database
 * table's field definition.
 *
 * @param array $data
 *   The array to filter
 * @param $table
 *   The name of the database table (or entity)
 * @return array
 *   The filtered data
 */
function mm_filter_fields(array $data, $table) {
  return array_intersect_key($data, array_fill_keys(mm_get_table_columns($table), 0));
}

function mm_autocomplete_desc() {
  $array = ['#theme' => 'mm_autocomplete_desc'];
  return \Drupal::service('renderer')->render($array);
}

function mm_empty_anchor($title, array $attributes = []) {
  $attributes['href'] = '#';
  $attr = new Attribute($attributes);
  return "<a$attr>" . Html::escape($title) . '</a>';
}

function mm_page_wrapper($title, $body, $attributes, $wrap_body_with_div = FALSE, $do_system_attachments = TRUE) {
  if (!is_array($body)) {
    $body = ['#markup' => $body];
  }
  if ($wrap_body_with_div) {
    $body['#prefix'] = '<div class="mm-page-wrapper">';
    $body['#suffix'] = '</div>';
  }
  $arr = [
    '#type' => 'html',
    '#attributes' => $attributes,
    'page' => [
      '#type' => 'markup',
      '#title' => $title,
      'content' => $body,
    ],
  ];
  if ($do_system_attachments) {
    system_page_attachments($arr['page']);
  }
  $resp = new HtmlResponse((string) \Drupal::service('renderer')->renderRoot($arr));
  $resp->setAttachments($arr['#attached']);
  return $resp;
}

function mm_request_time() {
  return \Drupal::time()->getRequestTime();
}