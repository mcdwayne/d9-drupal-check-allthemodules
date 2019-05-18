<?php

/**
 * @file
 * Search for nodes/MM categories with certain attributes, and perform certain
 * actions on them
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\UserSession;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Controller\DefaultController;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

class SearchReplaceForm extends FormBase {

  const MMSR_REGEXP = '/
    ([$|=]) \{
      ( (
        (?> (?: \{[^{}]*?\} | [^$|={}]++ | [$|=][^{] )+ ) |
        (?R)
      )* )
    \}/xs';

  public static function getForm($mmtid) {
    $data = (object) array();
    if ($temp_mmtid = \Drupal::request()->query->getInt('mmtid', 0)) {
      $mmtid = $temp_mmtid;
    }
    // In case of error, don't save session as wrong user.
    $accountSwitcher = \Drupal::service('account_switcher');
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $output = \Drupal::formBuilder()->getForm(__CLASS__, $mmtid, $data);
    // Re-enable session saving.
    $accountSwitcher->switchBack();
    return $output;
  }

  public static function getResults() {
    if (!empty($_SESSION['mmsr-mmtid'])) {
      $links[0]['query'] = array('mmtid' => $_SESSION['mmsr-mmtid']);
    }

    $count_sql = static::getResultQuery($_SESSION['mmsr-data'], $_SESSION['mmsr-query']['queries'], TRUE, $result_sql, $header);
    $db = Database::getConnection();
    $total = $db->query($count_sql)->fetchField();
    $num_per_page = 25;

    $page = pager_default_initialize($total, $num_per_page);
    $offset = $num_per_page * $page;
    $result = $db->queryRange($result_sql, $offset, $num_per_page);

    $rows = array();
    foreach ($result as $row) {
      if (isset($row->nid)) {
        $rows[] = array(
          'data' =>
            array(
              Link::fromTextAndUrl(trim($row->title) == '' ? t('(unknown)') : $row->title, Url::fromRoute('entity.node.canonical', ['node' => $row->nid]))
                ->toString(),
              DefaultController::getNodeTypeLabel($row->type),
              mm_format_date($row->changed, 'short'),
              mm_format_date($row->created, 'short'),
              mm_content_uid2name($row->uid, 'fmlu', $row, $hover)
            )
        );
      }
      else {
        $rows[] = array(
          'data' =>
            array(
              Link::fromTextAndUrl(trim($row->pgname) == '' ? t('(unknown)') : $row->pgname, mm_content_get_mmtid_url($row->mmtid)),
              mm_content_uid2name($row->uid, 'fmlu', $row, $hover)
            )
        );
      }
    }

    if (!$rows) {
      $rows[] = array(array('data' => t('No matches'), 'colspan' => count($header)));
    }

    return [
      [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ],
      [
        '#type' => 'pager',
      ],
    ];
  }

  public static function getResultsCSV() {
    $headers = [
      'Content-type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename=mm_search_result_' . date('YmdHis') . '.csv',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ];

    static::getResultQuery($_SESSION['mmsr-data'], $_SESSION['mmsr-query']['queries'], TRUE, $result_sql, $header);
    $result = Database::getConnection()->query($result_sql);

    $hdrs = array();
    $t_page = t('Page')->render();
    $t_title = t('Title')->render();
    foreach ($header as $row) {
      $h = $row['data']->render();
      $hdrs[] = $h;
      if ($h == $t_page || $h == $t_title) {
        $hdrs[] = t('URL');
      }
    }
    ob_start();
    $fp = fopen('php://output', 'w');
    fputcsv($fp, $hdrs);
    $t_unknown = t('(unknown)')->render();

    foreach ($result as $row) {
      if (isset($row->nid)) {
        $title = trim($row->title);
        fputcsv($fp, array(
          $title == '' ? $t_unknown : $title,
          Url::fromRoute('entity.node.canonical', ['node' => $row->nid], array('absolute' => TRUE))
            ->toString(),
          DefaultController::getNodeTypeLabel($row->type),
          mm_format_date($row->changed, 'short'),
          mm_format_date($row->created, 'short'),
          mm_content_uid2name($row->uid, 'fmlu', $row, $hover)
        ));
      }
      else {
        $title = trim($row->pgname);
        fputcsv($fp, array(
          $title == '' ? $t_unknown : $title,
          mm_content_get_mmtid_url($row->mmtid, array('absolute' => TRUE))->toString(),
          mm_content_uid2name($row->uid, 'fmlu', $row, $hover)
        ));
      }
    }

    fclose($fp);
    return new Response(ob_get_clean(), 200, $headers);
  }

  private static function walkPage($item, $key, &$data) {
    static $last_fieldset;

    if (is_array($item) && $key != '#groups') {
      if (isset($item['#type']) && ($item['#type'] == 'fieldset' || $item['#type'] == 'details')) {
        $last_fieldset = (string) $item['#title'];
      }
      elseif (isset($item['#type']) && $item['#type'] == 'textarea') {
        static::disableWysiwyg($item);
      }
      if (isset($item['#mm-search'])) {
        if (is_array($item['#mm-search'])) {
          $i = 0;
          foreach ($item['#mm-search'] as $k => $v) {
            $this_key = "$key-$i";
            if (isset($last_fieldset)) {
              $data->form['search-page-wheres']['#options'][$last_fieldset][$this_key] = $k;
            }
            else {
              $data->form['search-page-wheres']['#options'][$this_key] = $k;
            }
            static::getFormOpts($data, $item, $this_key, $v);
            $i++;
          }
        }
        else {
          if (isset($last_fieldset)) {
            $data->form['search-page-wheres']['#options'][$last_fieldset][$key] = $item['#mm-search'];
          }
          else {
            $data->form['search-page-wheres']['#options'][$key] = $item['#mm-search'];
          }

          if (isset($item['#mm-search-opt-check'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-check'], FALSE, '#mm-search-opt-check');
          }
          if (isset($item['#mm-search-opt-optgroup'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-optgroup'], FALSE, '#mm-search-opt-optgroup');
          }
          if (isset($item['#mm-search-opt-list'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-list'], FALSE, '#mm-search-opt-list');
          }
          if (isset($item['#mm-search-opt'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt']);
          }
        }
      }
      elseif (is_array($item) && !isset($item['#mm-search-processed']) && (!isset($item['#type']) || $item['#type'] != 'vertical_tabs')) {
        $item['#mm-search-processed'] = TRUE;
        array_walk($item, 'self::walkPage', $data);
      }
    }
  }

  private static function walkGroup($item, $key, &$data) {
    static $last_fieldset;

    if (is_array($item) && $key != '#groups') {
      if (isset($item['#type']) && ($item['#type'] == 'fieldset' || $item['#type'] == 'details')) {
        $last_fieldset = (string) $item['#title'];
      }
      elseif (isset($item['#type']) && $item['#type'] == 'textarea') {
        static::disableWysiwyg($item);
      }
      if (isset($item['#mm-search'])) {
        if (is_array($item['#mm-search'])) {
          $i = 0;
          foreach ($item['#mm-search'] as $k => $v) {
            $this_key = "$key-$i";
            if (isset($last_fieldset)) {
              $data->form['search-group-wheres']['#options'][$last_fieldset][$this_key] = $k;
            }
            else {
              $data->form['search-group-wheres']['#options'][$this_key] = $k;
            }
            static::getFormOpts($data, $item, $this_key, $v, TRUE);
            $i++;
          }
        }
        else {
          if (isset($last_fieldset)) {
            $data->form['search-group-wheres']['#options'][$last_fieldset][$key] = $item['#mm-search'];
          }
          else {
            $data->form['search-group-wheres']['#options'][$key] = $item['#mm-search'];
          }

          if (isset($item['#mm-search-opt-check'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-check'], TRUE, '#mm-search-opt-check');
          }
          if (isset($item['#mm-search-opt-optgroup'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-optgroup'], TRUE, '#mm-search-opt-optgroup');
          }
          if (isset($item['#mm-search-opt-list'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt-list'], TRUE, '#mm-search-opt-list');
          }
          if (isset($item['#mm-search-opt'])) {
            static::getFormOpts($data, $item, $key, $item['#mm-search-opt'], TRUE);
          }
        }
      }
      elseif (isset($item['#type']) && $item['#type'] == 'mm_userlist') {
        if (is_array($data->form["search-$key-choose"])) {
          $data->form["search-$key-choose"][$key] = $item;
        }
      }
      elseif (is_array($item) && !isset($item['#mm-search-processed']) && (!isset($item['#type']) || $item['#type'] != 'vertical_tabs')) {
        $item['#mm-search-processed'] = TRUE;
        array_walk($item, 'self::walkGroup', $data);
      }
    }
  }

  private static function walkNode($item, $key, &$data) {
    global $_mmsr_query_defaults, $_mmsr_node_queries;

    if (is_array($item) && $key != '#groups') {
      $item['#weight'] = 0;
      $item['#required'] = FALSE;
      $item['#description'] = isset($item['#mm-search-description']) ? $item['#mm-search-description'] : NULL;
      $mm_search_opt = NULL;
      if (isset($item['#type'])) {
        if ($item['#type'] == 'container') {
          // Flatten containers
          if (isset($item['#language']) && isset($item[$item['#language']])) {
            $item = $item[$item['#language']];
          }
        }

        if (isset($item['#type'])) {
          switch ($item['#type']) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'textarea':
              static::disableWysiwyg($item);
            // no break

            case 'textfield':
            case 'datetime':
              // FIXME: https://www.drupal.org/node/2294629  Field widgets generate lighter default $form[$field_name] structures
              if (isset($item['#field_name'])) {
                $field_name = $item['#field_name'];
                $col_name = $item['#columns'][0];
                // FIXME: missing entity type as first parameter
                $info = FieldStorageConfig::loadByName($field_name);
                if ($info && isset($info['storage']['details']['sql'])) {
                  if (isset($info['foreign_keys'][$col_name])) {
                    $data_table = $info['foreign_keys'][$col_name]['table'];
                    $data_field = $info['foreign_keys'][$col_name]['columns'][$col_name];
                  }
                  else {
                    return;
                  }

                  $mm_search = t('the field "@title"', array('@title' => $item['#title']));
                  $mm_search_opt = array(
                    'contains the value' => '= ${qval}',
                    'does not contain the value' => 'IS NULL OR {' . $data_table . '}.name <> ${qval}',
                  );
                  $field_table = mm_ui_mmlist_key0($info['storage']['details']['sql'][EntityStorageInterface::FIELD_LOAD_CURRENT]);
                  $field_name = $info['storage']['details']['sql'][EntityStorageInterface::FIELD_LOAD_CURRENT][$field_table][$col_name];
                  $_mmsr_node_queries[$key] = array(
                    array(
                      $field_table => '{' . $field_table . '}.entity_id = {node}.nid',
                      $data_table => '{' . $data_table . '}.' . $data_field . ' = {' . $field_table . '}.' . $field_name,
                    ),
                    array(
                      $key => '{' . $data_table . '}.name ={return $query_segments[' . $key . '][intval(${search-' . $key . '-0})]}',
                    ),
                  );
                  unset($item['#description']);
                }
              }
              else {
                $mm_search = mb_strtolower($item['#title']);
                $mm_search_opt = $_mmsr_query_defaults['s'];
              }

              break;
          }
        }
      }

      if (isset($item['#mm-search'])) {
        $mm_search = $item['#mm-search'];
      }
      if (isset($item['#mm-search-opt'])) {
        $mm_search_opt = $item['#mm-search-opt'];
      }

      if ($key === 'users_w-choose' || $key === 'users_w') {
        $data->form['search-groups_w'][$key] = $item;
      }
      elseif (isset($mm_search)) {
        if (is_array($mm_search)) {
          $i = 0;
          foreach ($mm_search as $k => $v) {
            $this_key = "$key-$i";
            $data->form['search-node-wheres']['#options'][$this_key] = $k;
            static::getFormOpts($data, $item, $this_key, $v);
            $i++;
          }
        }
        else {
          $data->form['search-node-wheres']['#options'][$key] = $mm_search;
          static::getFormOpts($data, $item, $key, $mm_search_opt);
        }
      }
      elseif (isset($item['#type']) && $item['#type'] == 'mm_userlist') {
        if (is_array($data->form["search-$key-choose"])) {
          $data->form["search-$key-choose"][$key] = $item;
        }
      }
      elseif (is_array($item) && !isset($item['#mm-search-processed']) && (!isset($item['#type']) || $item['#type'] != 'vertical_tabs')) {
        $item['#mm-search-processed'] = TRUE;
        array_walk($item, 'self::walkNode', $data);
      }
    }
  }

  private static function disableWysiwyg(&$item) {
    $item['#rows'] = 3;
    $item['#wysiwyg'] = FALSE;
  }

  private static function getFormOpts(&$data, $item, $key, $opt, $is_group = FALSE, $type = 'select') {
    global $_mmsr_query_defaults;
    $segs = $is_group ? 'grp_segs' : 'segs';
    $do_form = !isset($data->form["search-$key"]);

    if ($do_form) {
      $data->form["search-$key"] = array(
        '#prefix' => "<div id=\"search-$key\" class=\"hidden\">",
        '#suffix' => '</div>',
      );
    }
    $used_subpanels_outer = FALSE;
    if (isset($opt[0]) && is_array($opt[0]) && $type != '#mm-search-opt-optgroup' && $type != '#mm-search-opt-list') {
      $i = 0;
      $weight = -10;
      foreach ($opt as $title => $o) {
        $k = "$key-$i";
        $options = array_keys($o);
        $sp_options = array();
        $used_subpanels = FALSE;
        foreach ($options as $o2) {
          if (preg_match('/^\[(.*?)\]$/', $o2, $matches)) {
            $subpanel = $matches[1];
            $sp_options[$subpanel] = isset($item[$subpanel]['#title']) ? $item[$subpanel]['#title'] : '';
            $item[$subpanel]['#prefix'] = '<div class="subpanel" name="' . "search-$k-$subpanel" . '">';
            $item[$subpanel]['#suffix'] = '</div>';
            if ($do_form) {
              $data->form["search-$key"]["search-$k-$subpanel"] = $item[$subpanel];
            }
            unset($item[$subpanel]);
            $used_subpanels = $used_subpanels_outer = TRUE;
          }
        }

        if ($do_form) {
          $data->form["search-$key"]["search-$k"] = array(
            '#type' => 'select',
            '#title' => is_numeric($title) ? NULL : $title,
            '#options' => $used_subpanels ? $sp_options : $options,
            '#weight' => isset($item['#mm-search-weight']) ? $item['#mm-search-weight'] : $weight++,
            '#attributes' => (isset($item['#mm-search-attr']) ? $item['#mm-search-attr'] : ($used_subpanels ? array('class' => array('subpanel-select')) : NULL)),
          );
        }
        $data->query[$segs][$k] = array_values($o);
        $i++;
      }
      if (isset($item['#mm-search-query'])) {
        $data->query['queries'][$key][0] = isset($item['#mm-search-joins']) ? $item['#mm-search-joins'] : '';
        $data->query['queries'][$key][1] = $item['#mm-search-query'];
      }
    }
    elseif (is_array($opt)) {
      if ($type == '#mm-search-opt-list') {   // arbitrary list of other form elements and/or selects
        $weight = -10;
        foreach ($opt as $k => $v) {
          if (isset($v['#type'])) {
            if ($do_form) {
              $data->form["search-$key"]["search-$key-$k"] = $v;
            }
          }
          else {
            if ($do_form) {
              $data->form["search-$key"]["search-$key-$k"] = array(
                '#type' => 'select',
                '#title' => is_numeric($k) ? NULL : $k,
                '#options' => array_keys($v),
                '#weight' => isset($item['#mm-search-weight']) ? $item['#mm-search-weight'] : $weight++,
                '#attributes' => (isset($item['#mm-search-attr']) ? $item['#mm-search-attr'] : $item['#attributes']),
              );
            }

            foreach ($v as $k2 => $v2) {
              if (isset($_mmsr_query_defaults[$v2][$k2])) {
                $data->query[$segs]["$key-$k"][] = $_mmsr_query_defaults[$v2][$k2];
              }
              else {
                $data->query[$segs]["$key-$k"][] = $v2;
              }
            }
          }
        }
      }
      else {
        if ($type == '#mm-search-opt-optgroup') {   // categorized select list (<optgroup>)
          $keys = array();
          foreach ($opt as $cat => $v) {
            foreach ($v as $k2 => $v2) {
              foreach ($v2 as $k3 => $v3) {
                $keys[$cat][$k2] = $k3;
                if (isset($_mmsr_query_defaults[$v3][$k2])) {
                  $data->query[$segs][$key][] = $_mmsr_query_defaults[$v3][$k2];
                }
                else {
                  $data->query[$segs][$key][] = $v3;
                }
              }
            }
          }
        }
        else {
          $keys = array_keys($opt);
          foreach ($opt as $k => $v) {
            if (isset($_mmsr_query_defaults[$v][$k])) {
              $data->query[$segs][$key][] = $_mmsr_query_defaults[$v][$k];
            }
            else {
              $data->query[$segs][$key][] = $v;
            }
          }
        }

        if ($do_form) {
          $data->form["search-$key"]["search-$key-0"] = array(
            '#title' => $type == '#mm-search-opt-check' ? $keys[1] : NULL,
            '#type' => $type == '#mm-search-opt-check' ? 'checkbox' : 'select',
            '#options' => $keys,
            '#weight' => isset($item['#mm-search-weight']) ? $item['#mm-search-weight'] : -1,
            '#attributes' => (isset($item['#mm-search-attr']) ? $item['#mm-search-attr'] : (isset($item['#attributes']) ? $item['#attributes'] : array())),
            '#value' => NULL,
          );
        }
      }
      if (isset($item['#mm-search-query'])) {
        unset($item['#description']);
        $data->query['queries'][$key][0] = isset($item['#mm-search-joins']) ? $item['#mm-search-joins'] : '';
        $data->query['queries'][$key][1] = $item['#mm-search-query'];
      }
    }
    else {
      if (isset($item['#mm-search-query'])) {
        $data->query['queries'][$key][0] = $item['#mm-search-joins'];
        $data->query['queries'][$key][1][$key] = $item['#mm-search-query'][$key];
      }
      return;
    }

    unset($item['#title']);
    //  unset($item['#description']);
    //  debug_add_dump($key, $item);
    if (!$used_subpanels_outer && $type != '#mm-search-opt-check') {
      $data->form["search-$key"][$key] = $item;
    }
  }

  private static function getResultQuery($data, $query_info, $results, &$result_query, &$header) {
    $_mmsr_query_segments = &drupal_static('_mmsr_query_segments');

    $row = 0;
    $visited = array('search-logic' => TRUE);
    $args = array();
    foreach (explode('&', $data) as $arg) {
      list($key, $val) = explode('=', $arg, 2);
      // Multiple SELECTs have '[]' in the name.
      $key = preg_replace('/\[\]$/', '', $key);
      $val = urldecode($val);
      if (!isset($visited[$key]) || !$visited[$key]) {
        $visited[$key] = TRUE;
      }
      else {
        $row++;
        $visited = array('search-logic' => TRUE);
      }
      $args[$row][$key] = $val;
    }
    $joins = array();
    $wheres = $logic = $result_groupby = $sort_order = $count_query = '';
    $wlist2 = array();
    $cat_key = 'search-page-cat';
    $depth = -1;
    foreach ($args as $row) {
      $wlist = $vars = array();
      $qlist = array();
      foreach ($row as $key => $val) {// debug_add_dump("$key=$val");
        if ($key == 'search-type') {
          $_mmsr_query_segments = $_SESSION['mmsr-query']['segs'];
          switch ($val) {
            case 0:   // contents
              $cat_key = '';
              //no break

            case 2:   // contents on pages
              $count_query = 'SELECT COUNT(DISTINCT {node}.nid) FROM {node} INNER JOIN {node_field_data} ON {node_field_data}.vid = {node}.vid';
              if ($results) {
                $joins['node_revision'] = '{node_revision}.nid = {node_field_data}.nid AND {node_revision}.vid = {node_field_data}.vid';
                $header = array(
                  array('data' => t('Title'), 'field' => '{node_field_data}.title'),
                  array('data' => t('Type'), 'field' => '{node_field_data}.type'),
                  array('data' => t('Modified'), 'field' => '{node_field_data}.changed', 'sort' => 'DESC'),
                  array('data' => t('Created'), 'field' => '{node_field_data}.created'),
                );
                if (mm_module_exists('amherstprofile')) {
                  $joins['eduprofile'] = '{eduprofile}.uid = {node_field_data}.uid';
                  $header[] = array('data' => t('Owner'), 'field' => '{eduprofile}.lastname');
                  $result_query = 'SELECT {node_field_data}.title, {node_field_data}.nid, {node_field_data}.type, {node_field_data}.changed, {node_field_data}.created, {eduprofile}.pref_fml, {eduprofile}.pref_lfm, {eduprofile}.lastname, {eduprofile}.firstname, {eduprofile}.username AS name, {eduprofile}.middlename, {eduprofile}.hover, {node_field_data}.uid FROM {node_field_data}';
                }
                else {
                  $joins['users_field_data'] = '{users_field_data}.uid = {node_field_data}.uid';
                  $header[] = array('data' => t('Owner'), 'field' => '{users_field_data}.name');
                  $result_query = 'SELECT MAX({node_field_data}.title) AS title, {node_field_data}.nid, MAX({node_field_data}.type) AS type, MAX({node_field_data}.changed) AS changed, MAX({node_field_data}.created) AS created, MAX({node_field_data}.uid) AS uid, MAX({users_field_data}.name) AS name FROM {node} INNER JOIN {node_field_data} ON {node_field_data}.vid = {node}.vid';
                }
                $result_groupby = ' GROUP BY {node_field_data}.nid ';
              }
              $joins['mm_node2tree'] = '{mm_node2tree}.nid = {node_field_data}.nid';
              $joins['mm_tree'] = '{mm_tree}.mmtid = {mm_node2tree}.mmtid';
              break;

            case 3:   // groups
              $cat_key = 'search-group-cat';
              $_mmsr_query_segments = $_SESSION['mmsr-query']['grp_segs'];
            // no break

            case 1:   // pages
              $count_query = 'SELECT COUNT(DISTINCT {mm_tree}.mmtid) FROM {mm_tree}';
              if ($results) {
                $header = array(
                  array('data' => t('Page'), 'field' => '{mm_tree}.name'),
                );
                if (mm_module_exists('amherstprofile')) {
                  $joins['eduprofile'] = '{eduprofile}.uid = {mm_tree}.uid';
                  $result_query = 'SELECT {mm_tree}.name AS pgname, {mm_tree}.mmtid, {eduprofile}.pref_fml, {eduprofile}.pref_lfm, {eduprofile}.lastname, {eduprofile}.firstname, {eduprofile}.username AS name, {eduprofile}.middlename, {eduprofile}.hover, {mm_tree}.uid FROM {mm_tree}';
                  $header[] = array('data' => t('Owner'), 'field' => '{eduprofile}.lastname');
                }
                else {
                  $joins['users_field_data'] = '{users_field_data}.uid = {mm_tree}.uid';
                  $result_query = 'SELECT MAX({mm_tree}.name) AS pgname, {mm_tree}.mmtid, MAX({users_field_data}.name) AS name, MAX({mm_tree}.uid) AS uid FROM {mm_tree}';
                  $header[] = array('data' => t('Owner'), 'field' => '{users_field_data}.name');
                }
                $result_groupby = ' GROUP BY {mm_tree}.mmtid ';
              }
              break;
          } // switch
        }
        elseif ($key == 'search-node-type' && $val) {
          $w = static::parse('{node}.type=${qval}', 'node', $val);
          if ($w != '') {
            $wlist2[] = $w;
          }
        }
        elseif ($key == $cat_key) {
          if ($v = intval($val)) {
            if ($depth) {
              $joins['mm_tree_parents'] = '{mm_tree_parents}.mmtid = {mm_tree}.mmtid';
              if ($depth == -1) {
                $w = static::parse('({mm_tree}.mmtid = ${ival} OR {mm_tree_parents}.parent = ${ival})', 'mm_node2tree', $v);
              }
              else if ($depth == 1) {
                $w = static::parse('({mm_tree}.mmtid = ${ival} OR {mm_tree}.parent = ${ival})', 'mm_node2tree', $v);
              }
              else {
                $w = static::parse('({mm_tree}.mmtid = ${ival} OR {mm_tree_parents}.parent = ${ival} AND (SELECT depth FROM {mm_tree_parents} WHERE mmtid = {mm_tree}.mmtid AND parent = {mm_tree}.parent) - {mm_tree_parents}.depth < ' . $depth . ')', 'mm_node2tree', $v);
              }
            }
            else {
              $w = static::parse('{mm_tree}.mmtid = ${ival}', 'mm_node2tree', $v);
            }

            if ($w != '') {
              $wlist2[] = $w;
            }
            $_SESSION['mmsr-mmtid'] = $v;
          }
        }
        elseif ($key == 'search-logic') {
          if ($wheres) {
            $logic = $val == 'and' ? ' AND ' : ' OR ';
          }
        }
        elseif ($key == 'search-page-wheres' || $key == 'search-group-wheres' || $key == 'search-node-wheres') {
          if (isset($query_info[$val])) {
            $qlist[] = &$query_info[$val];
          }
        }
        elseif ($key == 'search-page-depth' || $key == 'search-group-depth') {
          $depth = $val;
        }
        else {
          $vars[$key] = $val;
        }
      } // foreach

      foreach ($qlist as $q) {
        if (isset($q[0]) && is_array($q[0])) {
          foreach ($q[0] as $table => $join_seg) {
            if (!isset($joins[$table])) {
              $joins[$table] = static::parse($join_seg, $table, '', $vars);
            }
          }
        }

        $w = '';
        foreach ($q[1] as $varname => $seg) {
          if (isset($vars[$varname])) {
            $w .= static::parse($seg, '', $vars[$varname], $vars);
          }
          elseif (isset($vars[$varname . '[date]'])) {
            $w .= static::parse($seg, '', $vars[$varname . '[date]'], $vars);
          }
          elseif (strpos($seg, '$') === FALSE) {
            $w .= static::parse($seg, '', '', $vars);
          }
        }

        if ($w != '') {
          $wlist[] = "($w)";
        }
      }

      if ($wlist) {
        $wheres .= $logic . join(' AND ', $wlist);
      }
    }   // foreach

    if ($wlist2) {
      if ($wheres) {
        $wheres = '(' . $wheres . ') AND ';
      }
      $wheres .= join(' AND ', $wlist2);
    }

    $query_joins = '';
    foreach ($joins as $table => $on) {
      $query_joins .= ' LEFT JOIN {' . $table . '} ON ' . $on;
    }

    if ($wheres) {
      $query_joins .= ' WHERE ' . $wheres;
    }
    $result_query .= $query_joins;
    if ($results) {
      $result_query .= $result_groupby;
      foreach ($header as $h) {
        $get = \Drupal::request()->query;
        if ($get->getAlnum('order', FALSE) === $h['data']->render()) {
          $sort_field = $h['field'];
          $sort = $get->getAlpha('sort');
          $sort_order = $sort == 'desc' || $sort == 'asc' ? strtoupper($sort) : (isset($h['sort']) ? $h['sort'] : '');
          break;
        }
        elseif (!isset($sort_field)) {
          $sort_field = $h['field'];
          $sort_order = isset($h['sort']) ? $h['sort'] : '';
        }
      }

      if (isset($sort_field)) {
        $result_query .= " ORDER BY MAX($sort_field) $sort_order";
      }
    }
    return $count_query . $query_joins;
  }

  private static function setVariables($val) {
    $_mmsr_vars = &drupal_static('_mmsr_vars');

    $_mmsr_vars['val'] = $val;
    $_mmsr_vars['ival'] = intval($val);
    $_mmsr_vars['qval'] = Database::getConnection()->quote($val);
  }

  private static function parse($seg, $table, $val, $vars2 = NULL) {
    $_mmsr_vars = &drupal_static('_mmsr_vars');

    $_mmsr_vars = !empty($table) ? array('table' => '{' . $table . '}') : array();
    static::setVariables($val);
    if (is_array($vars2)) {
      $_mmsr_vars = array_merge($_mmsr_vars, $vars2);
    }

    return trim(preg_replace_callback(static::MMSR_REGEXP, 'self::regexp', $seg));
  }

  private static function regexp($matches) {
    $_mmsr_vars = &drupal_static('_mmsr_vars');
    $_mmsr_query_segments = &drupal_static('_mmsr_query_segments');

    // debug_add_dump($matches,$_mmsr_vars);
    /** @noinspection PhpUnusedLocalVariableInspection */
    $query_segments = $_mmsr_query_segments;   // set for use within pseudocode
    if ($matches[1] == '=') {          //  ={something}
      $e = preg_replace_callback(static::MMSR_REGEXP, 'self::regexp', $matches[2]) . ';';
      return preg_replace_callback(static::MMSR_REGEXP, 'self::regexp', eval($e));
    }

    if ($matches[1] == '|') {          //  |{something}
      $e = array();
      foreach (explode(',', $old = $_mmsr_vars['val']) as $v) {
        static::setVariables($v);
        $e[] = preg_replace_callback(static::MMSR_REGEXP, 'self::regexp', $matches[2]);
      }
      static::setVariables($old);
      return preg_replace_callback(static::MMSR_REGEXP, 'self::regexp', join(', ', $e));
    }

    if ($matches[3][0] == "'") {       //  ${'something'}
      return Database::getConnection()
        ->quote($_mmsr_vars[substr($matches[3], 1, -1)]);
    }

    return $_mmsr_vars[$matches[3]];   //  ${something}
  }

  private static function searchDate($item_id, $field) {
    $_mmsr_vars = &drupal_static('_mmsr_vars');
    $_mmsr_query_segments = &drupal_static('_mmsr_query_segments');

    $date = $_mmsr_vars[$item_id . '[date]'] . ' ' . $_mmsr_vars[$item_id . '[time]'];
    $dv = preg_match('/\d/', $date) ? @date_create($date) : '';
    return $field . $_mmsr_query_segments[$item_id][intval($_mmsr_vars["search-$item_id-0"])] . ($dv ? date_format($dv, 'U') : 0);
  }

  private static function splitMMList() {
    $count = 0;
    $out = array();
    foreach (func_get_args() as $a) {
      $o = array();
      if (preg_match_all('#(\d+(?:/\d+)?)\{([^}]*)\}#', $a, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
          $o[] = $match[1];
        }
      }
      sort($o);
      $out[] = join(',', $o);
      $count += count($o);
    }
    return array($out, $count);
  }

  private static function splitMMListPerms($mode, &$others) {
    $_mmsr_vars = &drupal_static('_mmsr_vars');

    if ($others = $_mmsr_vars["others_$mode"]) {
      return array('', '');
    }
    $others = '';
    list($a) = static::splitMMList($_mmsr_vars["groups_$mode"], $_mmsr_vars["users_$mode"]);
    return $a;
  }

  private static function getSearchDepthList($thing) {
    return array(
      0 => t('only this @thing', array('@thing' => $thing)),
      -1 => t('this @thing and all children', array('@thing' => $thing)),
      1 => t('this @thing and 1 level of children', array('@thing' => $thing)),
      2 => t('this @thing and 2 levels of children', array('@thing' => $thing)),
      3 => t('this @thing and 3 levels of children', array('@thing' => $thing)),
      4 => t('this @thing and 4 levels of children', array('@thing' => $thing)),
      5 => t('this @thing and 5 levels of children', array('@thing' => $thing)),
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'mm_search_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mmtid = NULL, $data = NULL) {
    module_load_include('inc', 'monster_menus', 'mm_search_replace_alter');
    $item = (object) array('mmtid' => $mmtid, 'flags' => array());
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\monster_menus\Form\EditContentForm', $item, $mmtid, FALSE, TRUE, TRUE);

    $data->form['search-type'] = array(
      '#type' => 'select',
      '#title' => t('Find all'),
      '#default_value' => 1,
      '#options' => array(t('contents'), t('pages'), t('contents on pages'), t('groups')),
    );
    $tree = mm_content_get($item->mmtid);
    $tree->name = mm_content_get_name($tree);
    $data->form['search-group-catlist'] = array(
      '#prefix' => '<div id="search-group-catlist">',
      '#suffix' => '</div>',
    );
    $data->form['search-group-catlist']['search-group-cat'] = array(
      '#type' => 'mm_grouplist',
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => '',
      '#title' => t('starting at:'),
      '#default_value' => array($tree->mmtid => $tree->name),
      '#description' => t('Search down the tree starting at this location.'),
    );
    $data->form['search-group-catlist']['search-group-depth'] = array(
      '#type' => 'select',
      '#title' => t('limited to:'),
      '#options' => self::getSearchDepthList(t('group')),
      '#default_value' => -1,
    );
    $data->form['search-page-catlist'] = array(
      '#prefix' => '<div id="search-page-catlist">',
      '#suffix' => '</div>',
    );
    $data->form['search-page-catlist']['search-page-cat'] = array(
      '#type' => 'mm_catlist',
      '#mm_list_min' => 1,
      '#mm_list_max' => 1,
      '#mm_list_selectable' => '',
      '#mm_list_no_info' => TRUE,
      '#title' => t('starting at:'),
      '#default_value' => array($tree->mmtid => $tree->name),
      '#description' => t('Search down the tree starting at this location.'),
    );
    $data->form['search-page-catlist']['search-page-depth'] = array(
      '#type' => 'select',
      '#title' => t('limited to:'),
      '#options' => self::getSearchDepthList(t('page')),
      '#default_value' => -1,
    );
    $data->form['search-logic'] = array(
      '#type' => 'select',
      '#default_value' => 'and',
      '#id' => 'search-logic',
      '#attributes' => array('style' => 'display: none'),
      '#options' => array('and' => t('and'), 'or' => t('or')),
    );
    $data->form['search-page-wheres'] = array(
      '#type' => 'select',
      '#default_value' => '',
      '#id' => 'search-page-wheres',
      '#attributes' => array('style' => 'display: none'),
      '#options' => array('' => '(choose a property)'),
    );
    $data->form['search-group-wheres'] = array(
      '#type' => 'select',
      '#default_value' => '',
      '#id' => 'search-group-wheres',
      '#attributes' => array('style' => 'display: none'),
      '#options' => array('' => '(choose a property)'),
    );

    $node_types = array('' => t('(any type)'));
    /** @var NodeType $type */
    foreach (NodeType::loadMultiple() as $type) {
      if (mm_node_access_create($type->id())) {
        $node_types[$type->id()] = $type->label();
      }
    }
    natcasesort($node_types);
    $data->form['search-node-type'] = array(
      '#type' => 'select',
      '#id' => 'search-node-type',
      '#options' => $node_types,
    );

    mm_search_replace_alter_mm($form, FALSE);
    array_walk($form, 'self::walkPage', $data);

    /** @noinspection PhpMethodParametersCountMismatchInspection */
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\monster_menus\Form\EditContentForm', $item, $mmtid, TRUE, TRUE, TRUE);
    mm_search_replace_alter_mm($form, TRUE);
    array_walk($form, 'self::walkGroup', $data);

    $data->form['search-node-wheres'] = array(
      '#type' => 'select',
      '#id' => 'search-node-wheres',
      '#options' => array('' => '(choose a property)'),
    );
    $data->form['data'] = array(
      '#type' => 'hidden',
    );
    $data->form['actions'] = array('#type' => 'actions');
    $data->form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
    );
    $data->form['actions']['result'] = array(
      '#type' => 'submit',
      '#value' => t('Show Results'),
      '#button_type' => 'primary',
    );

    // Set up a dummy node. Use the story type if available, otherwise use the
    // first defined type.
    $node_type = NodeType::load($type_name = 'story');
    if (!$node_type) {
      $list = array_keys(NodeType::loadMultiple());
      $type_name = $list[0];
    }
    $node = Node::create([
      'type' => $type_name,
      'uid' => 1,
      'name' => '',
      'language' => '',
    ]);
    $form_id = $type_name . '_node_form';
    $info = $form_state->getBuildInfo();
    array_unshift($info['args'], $node);

    $temp_form_state_add = [
      'is_mm_search' => TRUE,
      'build_info' => $info,
    ];
    /** @var EntityFormInterface $form */
    $form = \Drupal::service('entity.manager')->getFormObject($node->getEntityTypeId(), 'default');
    $form->setEntity($node);
    $temp_form_state = (new FormState())->setFormState($temp_form_state_add);
    /** @var array $form */
    $form = \Drupal::formBuilder()->buildForm($form, $temp_form_state);
    \Drupal::formBuilder()->prepareForm($form_id, $form, $temp_form_state);
    $form['mm_appearance']['changed'] = isset($form['mm_appearance']['author']['date']) ? $form['mm_appearance']['author']['date'] : '';
    static::disableWysiwyg($form['body_field']['body']);
    mm_search_replace_alter_node($form);
    //  debug_add_dump($form);
    array_walk($form, 'self::walkNode', $data);

    $mmlist_name = $item->mmtid . '{' . $tree->name . '}';
    if (mm_content_is_group($item->mmtid)) {
      $node_page = mm_home_mmtid() . '{' . mm_content_get_name(mm_home_mmtid()) . '}';
      $group_cat = $mmlist_name;
    }
    else {
      $node_page = $mmlist_name;
      $group_cat = mm_content_groups_mmtid() . '{' . mm_content_expand_name(Constants::MM_ENTRY_NAME_GROUPS) . '}';
    }

    $reset = $startup = array(
      'search-type' => 0,
      'search-page-cat' => $node_page,
      'search-group-cat' => $group_cat,
      'search-node-type' => '',
      'mmsr-cont-row' => array(array('search-node-wheres' => '')),
    );
    if (isset($_SESSION['mmsr-data'])) {
      $row = -1;
      $startup = array();
      foreach (explode('&', $_SESSION['mmsr-data']) as $arg) {
        list($key, $val) = explode('=', $arg, 2);
        $val = urldecode($val);
        if ($key == 'search-node-wheres') {
          $row_type = 'mmsr-cont-row';
        }
        elseif ($key == 'search-page-wheres') {
          $row_type = 'mmsr-page-row';
        }
        elseif ($key == 'search-group-wheres') {
          $row_type = 'mmsr-group-row';
        }

        if ($key == 'search-logic') {
          if ($row < 0) {
            continue;
          }
          else {
            $row++;
          }
        }
        elseif ($row < 0 && ($key == 'search-node-wheres' || $key == 'search-page-wheres' || $key == 'search-group-wheres')) {
          $row++;
        }

        if ($row >= 0 && $key != 'search-page-cat' && $key != 'search-group-cat' && $key != 'search-node-cat') {
          $startup[$row_type][$row][$key] = $val;
        }
        else {
          $startup[$key] = $val;
        }
      }
      $startup['search-page-cat'] = $reset['search-page-cat'];
      $startup['search-group-cat'] = $reset['search-group-cat'];
      $startup = array_merge($reset, $startup);
    }

    mm_add_js_setting($data->form, 'MMSR', [
      'get_path' => base_path() . 'mmsr-get',
      'startup' => $startup,
      'reset' => $reset,
      'fixups' => [],
    ]);

    global $_mmsr_node_queries;
    $data->query['queries'] += $_mmsr_node_queries;
    $_SESSION['mmsr-query'] = $data->query;
    if (Constants::MMSR_debug) {
      debug_add_dump($data);
    }
    mm_add_library($data->form, 'mm_search_replace');
    return $data->form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['mmsr-data'] = $form_state->getValue('data');
    $form_state->setRedirectUrl(Url::fromRoute('monster_menus.page_settings_search_result'));
  }

  public function getSearchResultCount() {
    $data = \Drupal::request()->get('data', '');
    //  $f = fopen('/tmp/xx','a');fwrite($f,print_r(\Drupal::request()->get('data')."\n",1));
    $debug = $query = '';
    if (Constants::MMSR_debug) {
      $debug = '<p>' . htmlspecialchars($data) . '</p>';
    }

    try {
      $query = static::getResultQuery($_SESSION['mmsr-data'] = $data, $_SESSION['mmsr-query']['queries'], FALSE, $result_query, $header);
      $result = Database::getConnection()->query($query)->fetchColumn();
      if (isset($result)) {
        $result = \Drupal::translation()->formatPlural($result, '@count match', '@count matches');
      }
    }
    catch (\Exception $e) {
      $result = $this->t('An error occurred. See the <em>Query</em> section for details.');
      $debug .= '<p>' . $e->getMessage() . '</p>';
    }
    //  fwrite($f,print_r($result."\n",1));

    $debug .= '<p>' . htmlspecialchars(Database::getConnection()->prefixTables($query)) . '</p>';

    if (function_exists('AMH_debug_footer')) {
      $debug .= AMH_debug_footer(FALSE);
    }
    //  fclose($f);
    return mm_json_response([
      'result' => $result,
      'query' => $debug,
    ]);
  }

}