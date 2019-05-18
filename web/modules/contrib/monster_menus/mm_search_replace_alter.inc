<?php

/**
 * @file
 * Form data alteration code for mm_search_replace_inc
 */

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\monster_menus\Constants;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\monster_menus\Form\EditContentForm;

/**
 * Query segments can include substitutions:
 *   ={php code}
 *     Execute the PHP code contained within the braces. Multiple statements can
 *     be separated by ';'. Use 'return' to set the value to substitute for the
 *     expression. This return value is then parsed recursively for any further
 *     substitutions.
 *
 *   ${form variable name}
 *     Substitute the value with the given name, as POSTed to the form by the
 *     user. When the query expects an integer, be sure to use 'intval()', to
 *     avoid possible code injection.
 *
 *     Other, special variables that are available when a particular field is
 *     being added during the search query build process:
 *      - ${table} name of the table containing the field
 *      - ${val}   raw value from the search form; be careful with this!
 *      - ${ival}  the value as an integer
 *      - ${qval}  the value properly 'quoted'
 *
 *   ${'form variable name'}
 *     Substitute the value with the given name, as above, but enclose the
 *     result in single quotes and escape any single quotes in the string. This
 *     sanitizes form input so that it can be included in SQL queries or
 *     evaluated PHP code.
 *
 *   |{text}
 *     Split the current field value on commas, re-evaluating the text for each
 *     sub-value, and then join the results with ','. During each iteration, the
 *     ${*val} variables are set to the sub-value(see below). The result is
 *     usually surrounded with IN().
 *
 *     For example, if the query segment contained:
 *       SELECT * FROM foo WHERE col IN(|{${qval}})
 *     and the value started with 'v1,v2,v3', the result would be:
 *       SELECT * FROM foo WHERE col IN('v1', 'v2', 'v3')
 *
 *   {table name}
 *     Regular, Drupal-style escaping of a SQL table's name
 */
global $_mmsr_node_queries, $_mmsr_query_defaults;

$_mmsr_node_queries = array(
  'title' => array(
    array(),
    array(
      'title' => '{node_field_data}.title ={return $query_segments["title"][intval(${search-title-0})]}'
    ),
  ),
  'body' => array(
    array('node_revision__body' => '{node_revision__body}.revision_id = {node}.vid'),
    array(
      'body' => '{node_revision__body}.body_value ={return $query_segments["body"][intval(${search-body-0})]}'
    ),
  ),
  'revision_log' => array(
    array('node_revision' => '{node_revision}.nid = {node}.nid AND {node_revision}.vid = {node}.vid'),
    array(
      'revision_log' => '{node_revision}.revision_log ={return $query_segments["revision_log"][intval(${search-revision_log-0})]}'
    ),
  ),
  'owner-choose' => array(
    array(),
    array(
      'owner' => '{node_field_data}.uid ={return $query_segments["owner-choose"][intval(${search-owner-choose-0})]}'
    ),
  ),
  'groups_w' => array(
    array(),
    array(
      'groups_w' => '={return $query_segments["groups_w"][intval(${search-groups_w-0})]}'
    ),
  ),
  'date' => array(
    array(),
    array(
      'date' => '={return static::searchDate("date", "{node}.created")}'
    ),
  ),
  'changed' => array(
    array(),
    array(
      'changed' => '={return static::searchDate("changed", "{node_field_data}.changed")}'
    ),
  ),
  'publish_on' => array(
    array('mm_node_schedule' => '{mm_node_schedule}.nid = {node}.nid'),
    array(
      'publish_on' => '={return static::searchDate("publish_on", "{mm_node_schedule}.publish_on")}'
    ),
  ),
  'unpublish_on' => array(
    array('mm_node_schedule' => '{mm_node_schedule}.nid = {node}.nid'),
    array(
      'unpublish_on' => '={return static::searchDate("unpublish_on", "{mm_node_schedule}.unpublish_on")}'
    ),
  ),
);

$_mmsr_query_defaults = array(
  's' => array(
    'contains'                              => '={return ${qval}=="" ? "IS NULL" : "LIKE CONCAT(\'%\',${qval},\'%\')"}',
    'is'                                    => '= ${qval}',
    'begins with'                           => '={return ${qval}=="" ? "IS NULL" : "LIKE CONCAT(${qval},\'%\')"}',
    'ends with'                             => '={return ${qval}=="" ? "IS NULL" : "LIKE CONCAT(\'%\',${qval})"}',
    'matches the regular expression'        => '={return ${qval}=="" ? "IS NULL" : "REGEXP \'".${qval}."\'"}',
    'does not contain'                      => '={return ${qval}=="" ? "IS NOT NULL" : "NOT LIKE CONCAT(\'%\',${qval},\'%\')"}',
    'is not'                                => '<> ${qval}',
    'does not begin with'                   => '={return ${qval}=="" ? "IS NOT NULL" : "NOT LIKE CONCAT(${qval},\'%\')"}',
    'does not end with'                     => '={return ${qval}=="" ? "IS NOT NULL" : "NOT LIKE CONCAT(\'%\',${qval})"}',
    'does not match the regular expression' => '={return ${qval}=="" ? "IS NOT NULL" : "NOT REGEXP \'".${qval}."\'"}'),
  'i' => array(
    'is'                                    => '= ${ival}',
    'is not'                                => '<> ${ival}'),
  'date' => array(
    'is exactly'                            => '=',
    'is newer than'                         => '>',
    'is older than'                         => '<',
    'is not'                                => '<>'),
);

function mm_search_replace_alter_node(&$form) {
  global $_mmsr_query_defaults;

  defined('DATE_FORMAT_DATETIME') or define('DATE_FORMAT_DATETIME', 'Y-m-d H:i:s');
  $date_desc = t('Format: %time', array('%time' => mm_format_date(mm_request_time(), 'custom', DATE_FORMAT_DATETIME)));

  _mm_ui_form_array_merge($form, 'settings_perms', array(
    '#type' => 'details',
    '#open' => TRUE,
    '#title' => t('Who can edit or delete this content'),
  ));

  _mm_ui_userlist_setup(array(), $form['settings_perms'], 'owner', t('Owner:'), TRUE, '', '');

  $form['settings_perms']['groups_w'] = array(
    '#type' => 'mm_grouplist',
    '#mm_list_popup_start' => mm_content_groups_mmtid(),
    '#mm_list_other_name' => 'others_w',
  );

  _mm_ui_userlist_setup(array(), $form['settings_perms'], 'users_w', t('Individual users:'), FALSE, '', 'others_w');

  $form['settings_perms']['others_w'] = array(
    '#type' => 'checkbox',
    '#title' => t('Everyone'),
    '#attributes' => array('class' => 'settings-perms-others'),
  );

  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('the title'),
  );

  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => t('the body'),
  );

  $form['revision_log'] = array(
    '#type' => 'textarea',
    '#title' => t('the revision log message'),
  );

  $form['is_recycled'] = array(
    '#type' => 'checkbox',
  );

  $form['changed'] = array(
    '#type' => 'datetime',
    '#title' => t('the modification date/time'),
    '#mm-search-opt' => $_mmsr_query_defaults['date'],
    '#mm-search-description' => $date_desc,
    '#default_value' => NULL,
  );

  if (mm_module_exists('taxonomy')) {
    $vocabs = array();
    foreach (Vocabulary::loadMultiple() as $vid => $vocab) {
      if ($vocab->bundle() == 'taxonomy_vocabulary') {
        $vocabs[$vid] = $vocab->get('name');
      }
    }

    foreach (\Drupal::service('entity.manager')->getFieldMap()['node'] as $field_name => $info) {
      if ($info['type'] == 'entity_reference') {
        $field_info = FieldStorageConfig::loadByName('node', $field_name);
        if ($field_info && $field_info->getSetting('target_type') == 'taxonomy_term') {
          $node = Node::create(['type' => reset($info['bundles'])]);
          $definition = $node->get($field_name);
          $ids = (object) [
            'entity_type' => 'node',
            'bundle' => $node->getType(),
            'entity_id' => NULL,
          ];
          $temp_form = ['#entity' => _field_create_entity_from_ids($ids)];
          $items = $temp_form['#entity']->get($definition->getName());
          $form_state = new FormState();
          if (($element = $items->defaultValuesForm($temp_form, $form_state)) && isset($element['widget'][0]['target_id'])) {
            $vid = $element['widget'][0]['target_id']['#selection_settings']['target_bundles']['tags'];
            if (isset($vocabs[$vid])) {
              $key = 'taxonomy-' . $vid;
              $form[$key] = $element['widget'][0]['target_id'];
              $form[$key]['#field_parents'] = [];
              $form[$key] += [
                '#mm-search' => t('the taxonomy "@taxon"', array('@taxon' => $vocabs[$vid])),
                '#mm-search-opt' => array(
                  'contains the term' => '= ${qval}',
                  'does not contain the term' => 'IS NULL OR {taxonomy_term_field_data}.name <> ${qval}',
                ),
                '#mm-search-query' => array(
                  $key => '{taxonomy_term_field_data}.name ={return $query_segments["' . $key . '"][intval(${search-' . $key . '-0})]}',
                ),
                '#mm-search-joins' => array(
                  'taxonomy_index' => '{taxonomy_index}.nid = {node}.nid',
                  'taxonomy_term_field_data' => '{taxonomy_term_field_data}.tid = {taxonomy_index}.tid AND {taxonomy_term_field_data}.vid = \'' . $vid . "'",
                )
              ];
              unset($vocabs[$vid]);
            }
          }
        }
      }
    }
  }

  // ---------------- Begin alterations for node form elements ----------------
  $alter = array(
    // -------------------------------------------------------------------
    array(&$form['created']['widget'][0]['value'], '#mm-search', t('the creation date/time')),
    array(&$form['created']['widget'][0]['value'], '#mm-search-opt', $_mmsr_query_defaults['date']),
    array(&$form['created']['widget'][0]['value'], '#mm-search-description', $date_desc),
    // -------------------------------------------------------------------
    array(&$form['publish_on'], '#mm-search', t('the "publish on" date/time')),
    array(&$form['publish_on'], '#mm-search-opt', $_mmsr_query_defaults['date']),
    array(&$form['publish_on'], '#mm-search-description', $date_desc),
    // -------------------------------------------------------------------
    array(&$form['unpublish_on'], '#mm-search', t('the "unpublish on" date/time')),
    array(&$form['unpublish_on'], '#mm-search-opt', $_mmsr_query_defaults['date']),
    array(&$form['unpublish_on'], '#mm-search-description', $date_desc),
    // -------------------------------------------------------------------
    array(&$form['is_recycled'], '#mm-search', array((string) t('the content is in a recycle bin') => 'unused', (string) t('the content is not in a recycle bin') => 'unused')),
    array(&$form['is_recycled'], '#mm-search-query', array(
      'is_recycled-0' => '{mm_recycle}.id IS NOT NULL',
      'is_recycled-1' => '{mm_recycle}.id IS NULL')),
    array(&$form['is_recycled'], '#mm-search-joins', array('mm_recycle' => "{mm_recycle}.type = 'node' AND {mm_recycle}.id = {node}.nid")),
    // -------------------------------------------------------------------
    array(&$form['settings_perms']['owner-choose'], '#mm-search', t('the author/owner')),
    array(&$form['settings_perms']['owner-choose'], '#mm-search-opt', array(
      'is' => '={return ${qval}=="" ? "IS NULL" : "= ${ival}"}',
      'is not' => '={return ${qval}=="" ? "IS NOT NULL" : "<> ${ival}"}')),
    array(&$form['settings_perms']['owner'], '#required', NULL),
    array(&$form['settings_perms']['owner'], '#title', ' '),
    array(&$form['settings_perms']['owner'], '#mm-search-weight', 9),
    array(&$form['settings_perms']['owner'], '#mm_list_submit_on_add', FALSE),
    // -------------------------------------------------------------------
    array(&$form['settings_perms']['groups_w'], '#mm-search', t('who can edit or delete this content')),
    array(&$form['settings_perms']['groups_w'], '#mm-search-opt', array(
      'contains' => '={
        list($a) = static::splitMMList(${qval}, "${users_w}");
        $o = array();
        if ($a[0]) $o[] = "{mm_node_write}.gid IN($a[0])";
        if ($a[1]) $o[] = "{mm_group}.uid IN($a[1])";
        return $o ?
          "SELECT COUNT(*) > 0 FROM {mm_node_write} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_node_write}.nid = {node}.nid AND (" . join(" OR ", $o) . ")" :
          "FALSE";}',
      'exactly matches' => '={
        list($a) = static::splitMMList(${qval}, "${users_w}");
        return "SELECT IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_group}.uid ORDER BY {mm_group}.uid) FROM {mm_node_write} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_group}.gid < 0 AND {mm_node_write}.nid = {node}.nid), \'\') = \'$a[1]\' AND IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_node_write}.gid ORDER BY {mm_node_write}.gid) FROM {mm_node_write} INNER JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_group}.gid > 0 AND {mm_node_write}.nid = {node}.nid), \'\') = \'$a[0]\'"}',
      'does not contain' => '={
        list($a) = static::splitMMList(${qval}, "${users_w}");
        $o = array();
        if ($a[0]) $o[] = "{mm_node_write}.gid IN($a[0])";
        if ($a[1]) $o[] = "{mm_group}.uid IN($a[1])";
        return $o ?
          "SELECT COUNT(*) = 0 FROM {mm_node_write} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_node_write}.nid = {node}.nid AND (" . join(" OR ", $o) . ")" :
          "TRUE";}',
      'does not match' => '={
        list($a) = static::splitMMList(${qval}, "${users_w}");
        return "SELECT IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_group}.uid ORDER BY {mm_group}.uid) FROM {mm_node_write} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_group}.gid < 0 AND {mm_node_write}.nid = {node}.nid), \'\') != \'$a[1]\' OR IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_node_write}.gid ORDER BY {mm_node_write}.gid) FROM {mm_node_write} INNER JOIN {mm_group} ON {mm_group}.gid = {mm_node_write}.gid WHERE {mm_group}.gid > 0 AND {mm_node_write}.nid = {node}.nid), \'\') != \'$a[0]\'"}')),
  );
  // ----------------- End alterations for node form elements -----------------

  foreach ($alter as $a) {
    if (isset($a[0])) {
      if (is_null($a[2])) {
        unset($a[0][$a[1]]);
      }
      else {
        $a[0][$a[1]] = $a[2];
      }
    }
  }
}

function mm_search_replace_alter_mm(&$form, $is_group) {
  $x = mm_ui_strings($is_group);
  $opt1 = array(
    (string) t('contains') => 's',
    (string) t('is') => 's',
    (string) t('begins with') => 's',
    (string) t('ends with') => 's',
    (string) t('matches the regular expression') => 's',
    (string) t('does not contain') => 's',
    (string) t('is not') => 's',
    (string) t('does not begin with') => 's',
    (string) t('does not end with') => 's',
    (string) t('does not match the regular expression') => 's');
  $waur = array(
    '[w]' => Constants::MM_PERMS_WRITE,
    '[a]' => Constants::MM_PERMS_SUB,
    '[u]' => Constants::MM_PERMS_APPLY,
    '[r]' => Constants::MM_PERMS_READ,
  );
  if ($is_group) unset($waur['[u]']);

  $labels = EditContentForm::getPermsLabels(FALSE, $is_group, $x);
  foreach ($waur as $short) {
    $form['settings_perms'][$short] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => str_replace('&#8203;', '', (string) $labels[$short][0]),
    );

    $form['settings_perms'][$short]["groups_$short"] = array(
      '#type' => 'mm_grouplist',
      '#mm_list_popup_start' => mm_content_groups_mmtid(),
      '#mm_list_other_name' => "others_$short",
    );

    _mm_ui_userlist_setup(array(), $form['settings_perms'][$short], "users_$short", t('Individual users:'), FALSE, '', "others_$short");

    $form['settings_perms'][$short]["others_$short"] = array(
      '#type' => 'checkbox',
      '#title' => t('Everyone'),
      '#attributes' => array('class' => array('settings-perms-others')),
    );
  }

  $form['settings_perms2'] = array(
    '#type' => 'fieldset',
    '#title' => $form['settings_perms']['#title'],
  );
  _mm_ui_userlist_setup(array(''), $form['settings_perms2'], 'settings_perms2', t('User:'), TRUE, '');
  $form['settings_perms2']['settings_perms2']['#mm_list_submit_on_add'] = FALSE;

  $form['flags']['flags_value'] = array(
    '#type' => 'textfield',
  );
  $form['flags']['flags_name'] = array(
    '#type' => 'select',
    '#options' => array(),
    '#weight' => -11,
  );
  foreach (Element::children($form['flags']) as $child) {
    if (in_array($form['flags'][$child]['#type'], ['fieldset', 'details']) && $child != 'free_flags') {
      $fieldset = $form['flags'][$child]['#title'];
      foreach (Element::children($form['flags'][$child]) as $child2) {
        if (!empty($form['flags'][$child][$child2]['#input'])) {
          $title = $form['flags'][$child][$child2]['#title'];
          $form['flags']['flags_name']['#options'][$fieldset][$title] = $title;
          unset($form['flags'][$child][$child2]);
        }
      }
      unset($form['flags'][$child]);
    }
  }

  $form['flags2'] = array(
    '#type' => 'details',
    '#open' => TRUE,
    '#title' => $form['flags']['#title'],
    'free_flags_name' => array(
      '#type' => 'textfield',
      '#title' => t('named'),
      '#mm-search-weight' => -2,
    ),
    'free_flags' => array(
      '#mm-search-weight' => -1,
    ),
    'free_flags_value' => array(
      '#type' => 'textfield',
      '#mm-search-weight' => 0,
    ),
  );
  unset($form['flags']['free_flags']);

  $form['recycled'] = array(
    '#type' => 'details',
    '#open' => TRUE,
    '#title' => t('Recycle status'),
    'is_recycled2' => array('#type' => 'checkbox'),
  );

  // ------------- Begin alterations for group/page form elements -------------
  $alter = array(
    array(0,  &$form['settings_general']['name'], '#mm-search', t('the page name')),
    array(1,  &$form['settings_general']['name'], '#mm-search', t('the group name')),
    array(-1, &$form['settings_general']['name'], '#mm-search-opt', $opt1),
    array(-1, &$form['settings_general']['name'], '#mm-search-query', array(
      'name' => '{mm_tree}.name ={return $query_segments["name"][intval(${search-name-0})]}')),
    array(-1, &$form['settings_general']['name'], '#required', FALSE),
    // -------------------------------------------------------------------
    array(1,  &$form['members']['members-choose'], '#mm-search', t('the list of members')),
    array(1,  &$form['members']['members-choose'], '#mm-search-opt', array(
      (string) t('contains') => '={
        list($a, $c) = static::splitMMList("${members}");
        return $a[0] ? "SELECT COUNT(*) = $c FROM {mm_group} g LEFT JOIN {mm_virtual_group} v ON v.vgid = g.vgid WHERE g.gid = {mm_tree}.mmtid AND (g.uid IN($a[0]) OR v.uid IN($a[0]))" : "FALSE";}',
      (string) t('exactly matches') => '={
        list($a, $c) = static::splitMMList("${members}");
        return "SELECT IFNULL((SELECT IF(v.vgid>0, GROUP_CONCAT(DISTINCT v.uid ORDER BY v.uid), GROUP_CONCAT(DISTINCT g.uid ORDER BY g.uid)) FROM {mm_group} g LEFT JOIN {mm_virtual_group} v ON v.vgid = g.vgid WHERE g.gid = {mm_tree}.mmtid GROUP BY g.gid, v.vgid), \'\') = \'$a[0]\'";}',
      (string) t('does not contain') => '={
        list($a, $c) = static::splitMMList("${members}");
        return $a[0] ? "SELECT COUNT(*) != $c FROM {mm_group} g LEFT JOIN {mm_virtual_group} v ON v.vgid = g.vgid WHERE g.gid = {mm_tree}.mmtid AND (g.uid IN($a[0]) OR v.uid IN($a[0]))" : "TRUE";}',
      (string) t('does not match') => '={
        list($a, $c) = static::splitMMList("${members}");
        return "SELECT IFNULL((SELECT IF(v.vgid>0, GROUP_CONCAT(DISTINCT v.uid ORDER BY v.uid), GROUP_CONCAT(DISTINCT g.uid ORDER BY g.uid)) FROM {mm_group} g LEFT JOIN {mm_virtual_group} v ON v.vgid = g.vgid WHERE g.gid = {mm_tree}.mmtid GROUP BY g.gid, v.vgid), \'\') != \'$a[0]\'";}',
    )),
    array(1,  &$form['members']['members-choose'], '#mm-search-query', array(
      'members' => '={return $query_segments["members-choose"][intval(${search-members-choose-0})]}')),
    array(1,  &$form['members']['members-choose']['#attributes'], 'class', NULL),
    // -------------------------------------------------------------------
    array(-1, &$form['settings_general']['alias'], '#mm-search', t('the URL name')),
    array(-1, &$form['settings_general']['alias'], '#mm-search-opt', $opt1),
    array(-1, &$form['settings_general']['alias'], '#mm-search-query', array(
      'alias' => '{mm_tree}.alias ={return $query_segments["alias"][intval(${search-alias-0})]}')),
    array(-1, &$form['settings_general']['alias'], '#required', FALSE),
    // -------------------------------------------------------------------
    array(-1, &$form['flags']['flags_value'], '#mm-search', t('the pre-defined flag')),
    array(-1, &$form['flags']['flags_value'], '#mm-search-opt-list', array(
      &$form['flags']['flags_name'],
      array_merge(
        $opt1,
        array(
          (string) t('is set') => 'IS NOT NULL',
          (string) t('is not set') => 'IS NULL'
        )
      ),
    )),
    array(-1, &$form['flags']['flags_value'], '#mm-search-query', array(
      'flags_value' => '={$a = intval(${search-flags_value-1});
        if ($a < 10 && ${\'flags_value\'} == "") return "FALSE";
        if ($a == 10) return "SELECT COUNT(*)>0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-flags_value-0\'}";
        if ($a == 11) return "SELECT COUNT(*)=0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-flags_value-0\'}";
        return "SELECT COUNT(*)>0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-flags_value-0\'} AND {mm_tree_flags}.data " . $query_segments["flags_value-1"][$a]}')),
    array(-1, &$form['flags']['flags_value'], '#mm-search-attr', array('onchange' => "return MMSR_onchange_flags(this, 'flags_value');")),
    // -------------------------------------------------------------------
    array(-1, &$form['flags2']['free_flags_value'], '#mm-search', t('the free-form flag')),
    array(-1, &$form['flags2']['free_flags_value'], '#mm-search-opt-list', array(
      &$form['flags2']['free_flags_name'],
      array_merge(
        $opt1,
        array(
          (string) t('is set') => 'IS NOT NULL',
          (string) t('is not set') => 'IS NULL'
        )
      ),
    )),
    array(-1, &$form['flags2']['free_flags_value'], '#mm-search-query', array(
      'free_flags_value' => '={$a = intval(${search-free_flags_value-1});
        if (${\'search-free_flags_value-0\'} == "" || $a < 10 && ${\'free_flags_value\'} == "") return "FALSE";
        if ($a == 10) return "SELECT COUNT(*)>0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-free_flags_value-0\'}";
        if ($a == 11) return "SELECT COUNT(*)=0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-free_flags_value-0\'}";
        return "SELECT COUNT(*)>0 FROM {mm_tree_flags} WHERE {mm_tree_flags}.mmtid = {mm_tree}.mmtid AND {mm_tree_flags}.flag = ${\'search-free_flags_value-0\'} AND {mm_tree_flags}.data " . $query_segments["free_flags_value-1"][$a]}')),
    array(-1, &$form['flags2']['free_flags_value'], '#mm-search-attr', array('onchange' => "return MMSR_onchange_flags(this, 'free_flags_value');")),
    // -------------------------------------------------------------------
    array(-1, &$form['settings_perms'], '#mm-search', t('permission')),
    array(-1, &$form['settings_perms'], '#mm-search-opt', array(
      $waur, array(
        (string) t('contains') => '={
          $mode = substr("${search-settings_perms-0}", 0, 1);
          $a = static::splitMMListPerms($mode, $others);
          if ($others) return "\'$mode\' IN({mm_tree}.default_mode)";
          $o = array();
          if ($a[0]) $o[]="{mm_tree_access}.gid IN($a[0])";
          if ($a[1]) $o[]="{mm_group}.uid IN($a[1])";
          return $o ? "SELECT COUNT(*) > 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'$mode\' AND (" . join(" OR ", $o) . ")" : "FALSE";}',
        (string) t('exactly matches') => '={
          $mode = substr("${search-settings_perms-0}", 0, 1);
          $a = static::splitMMListPerms($mode, $others);
          if ($others) $others = " AND \'$mode\' IN({mm_tree}.default_mode)";
          return "SELECT IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_group}.uid ORDER BY {mm_group}.uid) FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_group}.gid < 0 AND {mm_tree_access}.mode=\'$mode\' AND {mm_tree_access}.mmtid = {mm_tree}.mmtid), \'\') = \'$a[1]\' AND IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_tree_access}.gid ORDER BY {mm_tree_access}.gid) FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE IFNULL({mm_group}.gid,1) > 0 AND {mm_tree_access}.mode=\'$mode\' AND {mm_tree_access}.mmtid = {mm_tree}.mmtid), \'\') = \'$a[0]\'$others"}',
        (string) t('does not contain') => '={
          $mode = substr("${search-settings_perms-0}", 0, 1);
          $a = static::splitMMListPerms($mode, $others);
          if ($others) return "\'$mode\' NOT IN({mm_tree}.default_mode)";
          $o = array();
          if ($a[0]) $o[]="{mm_tree_access}.gid IN($a[0])";
          if ($a[1]) $o[]="{mm_group}.uid IN($a[1])";
          return $o ? "SELECT COUNT(*) = 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'$mode\' AND (" . join(" OR ", $o) . ")" : "TRUE";}',
        (string) t('does not match') => '={
          $mode = substr("${search-settings_perms-0}", 0, 1);
          $a = static::splitMMListPerms($mode, $others);
          if ($others) $others = " OR \'$mode\' NOT IN({mm_tree}.default_mode)";
          return "SELECT IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_group}.uid ORDER BY {mm_group}.uid) FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_group}.gid < 0 AND {mm_tree_access}.mode=\'$mode\' AND {mm_tree_access}.mmtid = {mm_tree}.mmtid), \'\') != \'$a[1]\' OR IFNULL((SELECT GROUP_CONCAT(DISTINCT {mm_tree_access}.gid ORDER BY {mm_tree_access}.gid) FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE IFNULL({mm_group}.gid,1) > 0 AND {mm_tree_access}.mode=\'$mode\' AND {mm_tree_access}.mmtid = {mm_tree}.mmtid), \'\') != \'$a[0]\'$others"}',
      ))),
    array(-1, &$form['settings_perms'], '#mm-search-query', array(
      'search-settings_perms-0' => '={return $query_segments["settings_perms-1"][intval(${search-settings_perms-1})]}')),
    array(-1, &$form['settings_perms'], 'owner', NULL),
    array(-1, &$form['settings_perms'], 'owner-choose', NULL),
    // -------------------------------------------------------------------
    array(-1, &$form['settings_perms2']['settings_perms2-choose'], '#title', ''),
    array(-1, &$form['settings_perms2']['settings_perms2-choose'], '#mm_list_submit_on_add', FALSE),
    array(-1, &$form['settings_perms2'], '#mm-search', t('the user')),
    array(-1, &$form['settings_perms2'], '#mm-search-opt', array(
      (string) t('is the owner', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : "{mm_tree}.uid = $a[0]";}',
      (string) t('can delete the @thing or change its settings', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'w\' IN({mm_tree}.default_mode) OR (SELECT COUNT(*) > 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'w\' AND {mm_group}.uid=$a[0])" : "\'w\' IN({mm_tree}.default_mode)");}',
      (string) t('can append @subthings to the @thing', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'a\' IN({mm_tree}.default_mode) OR (SELECT COUNT(*) > 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'a\' AND {mm_group}.uid=$a[0])" : "\'a\' IN({mm_tree}.default_mode)");}',
      (string) t('can add content to the page') => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'u\' IN({mm_tree}.default_mode) OR (SELECT COUNT(*) > 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'u\' AND {mm_group}.uid=$a[0])" : "\'u\' IN({mm_tree}.default_mode)");}',
      ($is_group ? (string) t('can see the members of the group') : (string) t('can read the page')) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'r\' IN({mm_tree}.default_mode) OR (SELECT COUNT(*) > 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'r\' AND {mm_group}.uid=$a[0])" : "\'r\' IN({mm_tree}.default_mode)");}',
      (string) t('is not the owner', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "TRUE" : "{mm_tree}.uid <> $a[0]";}',
      (string) t('cannot delete the @thing or change its settings', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'w\' NOT IN({mm_tree}.default_mode) AND (SELECT COUNT(*) = 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'w\' AND {mm_group}.uid=$a[0])" : "\'w\' NOT IN({mm_tree}.default_mode)");}',
      (string) t('cannot append @subthings to the @thing', $x) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'a\' NOT IN({mm_tree}.default_mode) AND (SELECT COUNT(*) = 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'a\' AND {mm_group}.uid=$a[0])" : "\'a\' NOT IN({mm_tree}.default_mode)");}',
      (string) t('cannot add content to the page') => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'u\' NOT IN({mm_tree}.default_mode) AND (SELECT COUNT(*) = 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'u\' AND {mm_group}.uid=$a[0])" : "\'u\' NOT IN({mm_tree}.default_mode)");}',
      ($is_group ? (string) t('cannot see the members of the group') : (string) t('cannot read the page')) => '={
        list($a) = static::splitMMList("${settings_perms2}");
        return $a[0] == "" ? "FALSE" : ($a[0] ? "\'r\' NOT IN({mm_tree}.default_mode) AND (SELECT COUNT(*) = 0 FROM {mm_tree_access} LEFT JOIN {mm_group} ON {mm_group}.gid = {mm_tree_access}.gid WHERE {mm_tree_access}.mmtid = {mm_tree}.mmtid AND {mm_tree_access}.mode=\'r\' AND {mm_group}.uid=$a[0])" : "\'r\' NOT IN({mm_tree}.default_mode)");}'
      )),
    array(-1, &$form['settings_perms2'], '#mm-search-query', array(
      'settings_perms2' => '={return $query_segments["settings_perms2"][intval(${search-settings_perms2-0})]}')),
    array(-1, &$form['settings_perms2'], '#mm-search-weight', 10),
    // -------------------------------------------------------------------
    array(0,  &$form['appearance']['theme'], '#mm-search', t('the theme setting')),
    array(0,  &$form['appearance']['theme'], '#mm-search-opt', array(
      'is' => 's',
      'is not' => 's')),
    array(0,  &$form['appearance']['theme'], '#mm-search-query', array(
      'theme' => '{mm_tree}.theme ={return $query_segments["theme"][intval(${search-theme-0})]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['appearance']['allowed_themes']['#attributes'], 'multiple', NULL),
    array(0,  &$form['appearance']['allowed_themes']['#attributes'], 'name', NULL),
    array(0,  &$form['appearance']['allowed_themes'], '#multiple', FALSE),
    array(0,  &$form['appearance']['allowed_themes'], '#mm-search', t('the allowed themes list')),
    array(0,  &$form['appearance']['allowed_themes'], '#mm-search-opt', array(
      'contains any of' => '={return ${qval}=="" ? "FALSE" : "SELECT COUNT(*) FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_themes\' AND {mm_cascaded_settings}.data IN(|{${qval}})"}',
      'is' => 's',
      'does not contain any' => '={return ${qval}=="" ? "" : "SELECT COUNT(*)=0 FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_themes\' AND {mm_cascaded_settings}.data IN(|{${qval}})"}',
      'is set and not equal to' => '<> ${qval}')),
    array(0,  &$form['appearance']['allowed_themes'], '#mm-search-query', array(
      'allowed_themes' => '={$iv = intval(${search-allowed_themes-0}); return ($iv==0 || $iv==2 ? "" : "(SELECT GROUP_CONCAT({mm_cascaded_settings}.data ORDER BY {mm_cascaded_settings}.data ASC) FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_themes\') ") . $query_segments["allowed_themes"][$iv]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['menu']['hide_menu'], '#mm-search', array((string) t('the @thing is visible', $x) => 'unused', (string) t('the @thing is not visible', $x) => 'unused')),
    array(0,  &$form['menu']['hide_menu'], '#mm-search-query', array(
      'hide_menu-0' => '{mm_tree}.hidden = 0',
      'hide_menu-1' => '{mm_tree}.hidden = 1')),
    array(0,  &$form['menu']['hide_menu'], '#mm-search-joins', array()),
    // -------------------------------------------------------------------
    array(0,  &$form['menu']['menu_start'], '#mm-search', t('the location on screen')),
    array(0,  &$form['menu']['menu_start'], '#mm-search-opt', array(
      'is' => '{mm_tree_block}.bid ={return ${ival} < 0 ? "IS NULL" : "= ${qval}"}',
      'is not' => '{mm_tree_block}.bid ={return ${ival} < 0 ? "IS NOT NULL" : "<> ${qval} OR mm_tree_block.bid IS NULL"}')),
    array(0,  &$form['menu']['menu_start'], '#mm-search-query', array(
      'menu_start' => '={return $query_segments["menu_start"][intval(${search-menu_start-0})]}')),
    array(0,  &$form['menu']['menu_start'], '#mm-search-joins', array(
      'mm_tree_block' => '{mm_tree_block}.mmtid = {mm_tree}.mmtid')),
    // -------------------------------------------------------------------
    array(0,  &$form['menu']['max_depth'], '#mm-search', t('the max. number of child levels')),
    array(0,  &$form['menu']['max_depth'], '#mm-search-opt', array(
      'is' => '{mm_tree_block}.max_depth ={return ${ival} < 0 ? "IS NULL OR mm_tree_block.max_depth < 0" : "= ${ival}"}',
      'is not' => '{mm_tree_block}.max_depth ={return ${ival} < 0 ? ">= 0" : "<> ${ival} OR mm_tree_block.max_depth IS NULL"}')),
    array(0,  &$form['menu']['max_depth'], '#mm-search-query', array(
      'max_depth' => '={return $query_segments["max_depth"][intval(${search-max_depth-0})]}')),
    array(0,  &$form['menu']['max_depth'], '#mm-search-joins', array(
      'mm_tree_block' => '{mm_tree_block}.mmtid = {mm_tree}.mmtid')),
    // -------------------------------------------------------------------
    array(0,  &$form['menu']['max_parents'], '#mm-search', t('the max. number of parent levels')),
    array(0,  &$form['menu']['max_parents'], '#mm-search-opt', array(
      'is' => '{mm_tree_block}.max_parents ={return ${ival} < 0 ? "IS NULL OR mm_tree_block.max_parents < 0" : "= ${ival}"}',
      'is not' => '{mm_tree_block}.max_parents ={return ${ival} < 0 ? ">= 0" : "<> ${ival} OR mm_tree_block.max_parents IS NULL"}')),
    array(0,  &$form['menu']['max_parents'], '#mm-search-query', array(
      'max_parents' => '={return $query_segments["max_parents"][intval(${search-max_parents-0})]}')),
    array(0,  &$form['menu']['max_parents'], '#mm-search-joins', array(
      'mm_tree_block' => '{mm_tree_block}.mmtid = {mm_tree}.mmtid')),
    // -------------------------------------------------------------------
    array(0,  &$form['menu']['allow_reorder'], '#mm-search', t('allow menu reordering')),
    array(0,  &$form['menu']['allow_reorder'], '#mm-search-opt', array(
      'is' => '{mm_cascaded_settings}.data ={return ${ival} < 0 ? "IS NULL" : "= ${ival}"}',
      'is not' => '{mm_cascaded_settings}.data ={return ${ival} < 0 ? ">= 0" : "<> ${ival} OR {mm_cascaded_settings}.data IS NULL"}')),
    array(0,  &$form['menu']['allow_reorder'], '#mm-search-query', array(
      'allow_reorder' => '={return $query_segments["allow_reorder"][intval(${search-allow_reorder-0})]}')),
    array(0,  &$form['menu']['allow_reorder'], '#mm-search-joins', array(
      'mm_cascaded_settings' => '{mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allow_reorder\'')),
    // -------------------------------------------------------------------
    array(0,  &$form['appearance']['previews'], '#mm-search', t('show only previews')),
    array(0,  &$form['appearance']['previews'], '#mm-search-opt-check', array(
      '' => '{mm_tree}.previews = 0',
      'is enabled' => '{mm_tree}.previews <> 0')),
    array(0,  &$form['appearance']['previews'], '#mm-search-query', array(
      'search-previews-0' => '={return $query_segments["previews"][intval(${search-previews-0})]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['appearance']['rss'], '#mm-search', t('the rss feed')),
    array(0,  &$form['appearance']['rss'], '#mm-search-opt-check', array(
      '' => '{mm_tree}.rss = 0',
      'is enabled' => '{mm_tree}.rss <> 0')),
    array(0,  &$form['appearance']['rss'], '#mm-search-query', array(
      'search-rss-0' => '={return $query_segments["rss"][intval(${search-rss-0})]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['appearance']['nodes_per_page'], '#mm-search', t('the pieces of content displayed')),
    array(0,  &$form['appearance']['nodes_per_page'], '#mm-search-opt', array(
      'is' => '{mm_cascaded_settings}.data ={return ${qval} == "" ? "IS NULL" : "= ${qval}"}',
      'is not' => '{mm_cascaded_settings}.data ={return ${qval} == "" ? "IS NOT NULL" : "<> ${qval} OR {mm_cascaded_settings}.data IS NULL"}')),
    array(0,  &$form['appearance']['nodes_per_page'], '#mm-search-query', array(
      'nodes_per_page' => '={return $query_segments["nodes_per_page"][intval(${search-nodes_per_page-0})]}')),
    array(0,  &$form['appearance']['nodes_per_page'], '#mm-search-joins', array(
      'mm_cascaded_settings' => '{mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'nodes_per_page\'')),
    // -------------------------------------------------------------------
    array(0,  &$form['defaults']['node_info'], '#mm-search', t('the default attribution style')),
    array(0,  &$form['defaults']['node_info'], '#mm-search-opt', array(
      'is' => 'i',
      'is not' => 'i')),
    array(0,  &$form['defaults']['node_info'], '#mm-search-query', array('node_info' => '{mm_tree}.node_info ={return $query_segments["node_info"][intval(${search-node_info-0})]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['defaults']['comments_readable'], '#mm-search', t('who can read comments by default')),
    array(0,  &$form['defaults']['comments_readable'], '#mm-search-opt', array(
      'is' => '{mm_cascaded_settings}.data ={return ${ival} < 0 ? "IS NULL" : "= ${qval}"}',
      'is not' => '{mm_cascaded_settings}.data ={return ${ival} < 0 ? ">= 0" : "<> ${qval} OR {mm_cascaded_settings}.data IS NULL"}')),
    array(0,  &$form['defaults']['comments_readable'], '#mm-search-query', array('comments_readable' => '={return $query_segments["comments_readable"][intval(${search-comments_readable-0})]}')),
    array(0,  &$form['defaults']['comments_readable'], '#mm-search-joins', array(
      'mm_cascaded_settings' => '{mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'comments_readable\'')),
    // -------------------------------------------------------------------
    array(0,  &$form['defaults']['comment'], '#mm-search', t('who can add comments by default')),
    array(0,  &$form['defaults']['comment'], '#mm-search-opt', array(
      'is' => 'i',
      'is not' => 'i')),
    array(0,  &$form['defaults']['comment'], '#mm-search-query', array('comment' => '{mm_tree}.comment ={return $query_segments["comment"][intval(${search-comment-0})]}')),
    // -------------------------------------------------------------------
    array(0,  &$form['settings_node_types']['allowed_node_types'], '#mm-search', t('the allowed content types list')),
    array(0,  &$form['settings_node_types']['allowed_node_types'], '#mm-search-opt', array(
      'contains any of' => '={return ${qval}=="" ? "FALSE" : "SELECT COUNT(*) FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_node_types\' AND {mm_cascaded_settings}.data IN(|{${qval}})"}',
      'is' => 's',
      'is "inherit from parent"' => 'IS NULL',
      'does not contain any of' => '={return ${qval}=="" ? "" : "SELECT COUNT(*)=0 FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_node_types\' AND {mm_cascaded_settings}.data IN(|{${qval}})"}',
      'is set and not equal to' => '<> ${qval}',
      'is not "inherit from parent"' => 'IS NOT NULL')),
    array(0,  &$form['settings_node_types']['allowed_node_types'], '#mm-search-query', array(
      'allowed_node_types' => '={$iv = intval(${search-allowed_node_types-0}); return ($iv==0 || $iv==3 ? "" : "(SELECT GROUP_CONCAT({mm_cascaded_settings}.data ORDER BY {mm_cascaded_settings}.data ASC) FROM {mm_cascaded_settings} WHERE {mm_cascaded_settings}.mmtid = {mm_tree}.mmtid AND {mm_cascaded_settings}.name = \'allowed_node_types\') ") . $query_segments["allowed_node_types"][$iv]}')),
    array(0,  &$form['settings_node_types']['allowed_node_types'], '#mm-search-attr', array('onchange' => 'return MMSR_onchange_node_types(this);')),
    array(0,  &$form['settings_node_types']['allowed_node_types'], '#suffix', NULL),
    array(0,  &$form['settings_node_types'], '#title', t('Allowed content types')),
    // -------------------------------------------------------------------
    array(0,  &$form['settings_archive']['archive'], '#mm-search', t('use an archive')),
    array(0,  &$form['settings_archive']['archive'], '#mm-search-opt-check', array(
      array(
        'is disabled' => '',
        'is enabled' => ''),
      'and the frequency' => array(
        '(ignore)' => '',
        'is' => '',
        'is not' => ''),
      array(
        'daily' => 'day',
        'weekly' => 'week',
        'monthly' => 'month',
        'yearly' => 'year'),
      'and the number of pieces of content shown on the main page' => array(
        '(ignore)' => '',
        'is' => '=',
        'is more than' => '>',
        'is less than' => '<',
        'is not' => '<>'),
      isset($form['settings_archive']['inner']['main_nodes']['#options']) ? $form['settings_archive']['inner']['main_nodes']['#options'] : array(),
      )),
    array(0,  &$form['settings_archive']['archive'], '#mm-search-query', array(
      'search-archive-0' => '={if (!intval(${search-archive-0})) return "{mm_archive}.main_mmtid IS NULL";
      $q = "{mm_archive}.main_mmtid IS NOT NULL";
      if (intval(${search-archive-1}) > 0 && ($v = $query_segments["archive-2"][intval(${search-archive-2})]) != "")
          $q .= intval(${search-archive-1}) == 2 ? " AND {mm_archive}.frequency <> \'$v\'" : " AND {mm_archive}.frequency = \'$v\'";
      if ($op = $query_segments["archive-3"][intval(${search-archive-3})])
        $q .= " AND {mm_archive}.main_nodes $op " . $query_segments["archive-4"][intval(${search-archive-4})];
      return $q}')),
    array(0,  &$form['settings_archive']['archive'], '#mm-search-joins', array(
      'mm_archive' => '{mm_archive}.main_mmtid = {mm_tree}.mmtid')),
    array(0,  &$form['settings_archive']['archive'], '#mm-search-attr', array('class' => array('inline'))),
  // -------------------------------------------------------------------
    array(0,  &$form['recycled']['is_recycled2'], '#mm-search', array((string) t('the page is in a recycle bin') => 'unused', (string) t('the page is not in a recycle bin') => 'unused')),
    array(0,  &$form['recycled']['is_recycled2'], '#mm-search-query', array(
      'is_recycled2-0' => '{mm_recycle}.id IS NOT NULL',
      'is_recycled2-1' => '{mm_recycle}.id IS NULL')),
    array(0,  &$form['recycled']['is_recycled2'], '#mm-search-joins', array('mm_recycle' => "{mm_recycle}.type = 'cat' AND {mm_recycle}.id = {mm_tree}.mmtid")),
  // -------------- End alterations for group/page form elements --------------
  );

  foreach ($alter as $a) {
    if (($a[0] < 0 || $a[0] == $is_group) && isset($a[1])) {
      if (is_null($a[3])) {
        unset($a[1][$a[2]]);
      }
      else {
        $a[1][$a[2]] = $a[3];
      }
    }
  }

  if ($is_group) {
    $form['settings_perms_g'] = $form['settings_perms'];
    unset($form['settings_perms']);
    $form['settings_perms_g']['#groups'] = [];    // Prevent recursion.
    array_walk_recursive($form['settings_perms_g'], function (&$item) {
      if (is_string($item)) {
        $item = preg_replace('/\bsettings_perms\b/', 'settings_perms_g', $item);
      }
    });
    $form['settings_perms_g']['#mm-search-query']['search-settings_perms_g-0'] = $form['settings_perms_g']['#mm-search-query']['search-settings_perms-0'];

    unset($form['settings_perms2']['#mm-search-opt'][(string) t('can add content to the page')]);
    unset($form['settings_perms2']['#mm-search-opt'][(string) t('cannot add content to the page')]);
    $form['settings_perms2_g'] = $form['settings_perms2'];
    unset($form['settings_perms2']);
    array_walk_recursive($form['settings_perms2_g'], function(&$item) {
      if (is_string($item)) {
        $item = preg_replace('/\bsettings_perms2\b/', 'settings_perms2_g', $item);
      }
    });
    $form['settings_perms2_g']['#mm-search-query']['settings_perms2_g'] = $form['settings_perms2_g']['#mm-search-query']['settings_perms2'];
    $form['settings_perms2_g']['settings_perms2_g'] = $form['settings_perms2_g']['settings_perms2'];
    unset($form['settings_perms2_g']['settings_perms2']);
    $form['settings_perms2_g']['settings_perms2_g-choose'] = $form['settings_perms2_g']['settings_perms2-choose'];
    unset($form['settings_perms2_g']['settings_perms2-choose']);
  }
  else {
    if (isset($form['menu']['menu_start']['#options'])) {
      foreach ($form['menu']['menu_start']['#options'] as $k => $v) {
        $form['menu']['menu_start']['#options'][$k] = (string) $v;
      }
    }
  }
}
