<?php

namespace Drupal\linkchecker\Controller;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;

/**
 * Builds admin broken link report page.
 */
class LinkCheckerAdminReportPage extends ControllerBase {

  public function content() {
    $ignore_response_codes = preg_split('/(\r\n?|\n)/', \Drupal::config('linkchecker.settings')->get('error.ignore_response_codes'));

    // Search for broken links in nodes and comments and blocks of all users.
    // @todo Try to make UNION'ed subselect resultset smaller.
    $subquery4 = \Drupal::database()->select('linkchecker_node', 'ln')
        ->distinct()
        ->fields('ln', ['lid']);

    $subquery3 = \Drupal::database()->select('linkchecker_comment', 'lc')
        ->distinct()
        ->fields('lc', ['lid']);

    $subquery2 = \Drupal::database()->select('linkchecker_block_custom', 'lb')
        ->distinct()
        ->fields('lb', ['lid']);

    // UNION all linkchecker type tables.
    $subquery1 = \Drupal::database()->select($subquery2->union($subquery3)->union($subquery4), 'q1')->fields('q1', ['lid']);

    // Build pager query.
    $query = \Drupal::database()->select('linkchecker_link', 'll');
    $query->innerJoin($subquery1, 'q2', 'q2.lid = ll.lid');
    $query->fields('ll');
    $query->condition('ll.last_checked', 0, '<>');
    $query->condition('ll.status', 1);
    $query->condition('ll.code', $ignore_response_codes, 'NOT IN');
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
           ->extend('Drupal\Core\Database\Query\TableSortExtender');

    return $this->_linkchecker_report_page($query);
  }

  /**
   * Builds the HTML report page table with pager.
   *
   * @param SelectQueryInterface $query
   *   The pager query for the report page. Can be per user report or global.
   * @param object|null $account
   *   The user account object.
   *
   * @return string
   *   Themed report page.
   */
   public function _linkchecker_report_page($query, $account = NULL) {
    $connection = \Drupal::database();
    $links_unchecked = $connection->query('SELECT COUNT(1) FROM {linkchecker_link} WHERE last_checked = :last_checked AND status = :status', [':last_checked' => 0, ':status' => 1])->fetchField();

    if ($links_unchecked > 0) {
      $links_all = $connection->query('SELECT COUNT(1) FROM {linkchecker_link} WHERE status = :status', [':status' => 1])->fetchField();

      drupal_set_message(\Drupal::translation()->formatPlural($links_unchecked,
        'There is 1 unchecked link of about @links_all links in the database. Please be patient until all links have been checked via cron.',
        'There are @count unchecked links of about @links_all links in the database. Please be patient until all links have been checked via cron.',
        ['@links_all' => $links_all]
        ),
        'warning'
      );
    }

    $header = [
      ['data' => t('URL'), 'field' => 'url', 'sort' => 'desc'],
      ['data' => t('Response'), 'field' => 'code', 'sort' => 'desc'],
      ['data' => t('Error'), 'field' => 'error'],
      ['data' => t('Operations')],
    ];

    $result = $connection->query('SELECT * FROM {linkchecker_link}')
      ->fetchAll();

    // Evaluate permission once for performance reasons.
    $account = \Drupal::currentUser();
    $access_edit_link_settings = $account->hasPermission('edit linkchecker link settings');
    $access_administer_blocks = $account->hasPermission('administer blocks');
    $access_administer_redirects = $account->hasPermission('administer redirects');

    $rows = [];
    foreach ($result as $link) {
      // Get the node, block and comment IDs that refer to this broken link and
      // that the current user has access to.
      $cids = _linkchecker_link_comment_ids($link, $account);
      $bids = _linkchecker_link_block_ids($link);

      $nids = $connection->query('SELECT nid  FROM {linkchecker_node} WHERE lid = :lid', [':lid' => $link->lid])->fetchCol();

      // If the user does not have access to see this link anywhere, do not
      // display it, for reasons explained in _linkchecker_link_access(). We
      // still need to fill the table row, though, so as not to throw off the
      // number of items in the pager.
      if (empty($nids) && empty($cids) && empty($bids)) {
        $rows[] = array(array('data' => t('Permission restrictions deny you access to this broken link.'), 'colspan' => count($header)));
        continue;
      }
      $links = array();

      // Show links to link settings.
     // if ($access_edit_link_settings) {

       // $links[] = $this->l(t('Edit link settings'), Url::fromUri('base:' . 'linkchecker/' . $link->lid . '/edit'), array('query' => drupal_get_destination()));
      //}

      // Show link to nodes having this broken link.
      foreach ($nids as $nid) {
        $links[] = $this->l(t('Edit node @node', array('@node' => $nid)), Url::fromUri('base:' . 'node/' . $nid . '/edit'), array('query' => drupal_get_destination()));
      }

      // Show link to comments having this broken link.
      $comment_types = linkchecker_scan_comment_types();
      if (\Drupal::moduleHandler()->moduleExists('comment') && !empty($comment_types)) {
        foreach ($cids as $cid) {
          $links[] = $this->l(t('Edit comment @comment', array('@comment' => $cid)), Url::fromUri('base:' . 'comment/' . $cid . '/edit'), array('query' => drupal_get_destination()));
        }
      }

      // Show link to blocks having this broken link.
      if ($access_administer_blocks) {
        foreach ($bids as $bid) {
          $links[] = $this->l(t('Edit block @block', array('@block' => $bid)), Url::fromUri('base:' . 'admin/structure/block/manage/block/' . $bid . '/configure') , array('query' => drupal_get_destination()));
        }
      }

      // Show link to redirect this broken internal link.
      if (\Drupal::moduleHandler()->moduleExists('redirect') && $access_administer_redirects  && $this->_linkchecker_is_internal_url($link)) {
        $links[] = $this->l(t('Create redirection'), Url::fromUri('base:' . 'admin/config/search/redirect/add'), array('query' => array('source' => $link->internal, drupal_get_destination())));
      }

      // Create table data for output.
      $items = array(
          '#theme' => 'item_list',
          '#items' => $links,
          '#title' => t(''),
      );

      $rows[] = array(
          'data' => array(
              $this->l(_filter_url_trim($link->url, 60), Url::fromUri($link->url)),
              $link->code,
              SafeMarkup::checkPlain($link->error),
              drupal_render($items),
          ),
      );
    }

    $build['linkchecker_table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No broken links have been found.'),
    );
    //$build['linkchecker_pager'] = ['#theme' => 'pager'];

    return $build;
  }

  /**
   * Check if the link is an internal URL or not.
   *
   * @param object $link
   *   Link object.
   *
   * @return bool
   *   TRUE if link is internal, otherwise FALSE.
   */
  public function _linkchecker_is_internal_url(&$link) {
    global $base_url;

    if (strpos($link->url, $base_url) === 0) {
      $link->internal = trim(substr($link->url, strlen($base_url)), " \t\r\n\0\\/");
      return TRUE;
    }
  }

}
