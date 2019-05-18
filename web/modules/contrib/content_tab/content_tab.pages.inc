<?php

/**
 * @file
 * User page callbacks for content_tab.module.
 */

use Drupal\Component\Utility\String;
use Drupal\node\Entity\Node;

/**
 * Helper function to generate the query for the users content listing.
 *
 * @return SelectQueryInterface
 *   The called object.
 */
function _content_tab_content_query($account, $view_unpublished, $node_type) {
  $query = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('uid', $account->id());
  if ($node_type) {
    $query->condition('type', $node_type->type);
  }

  $result = $query->execute();
  return $result;
}

/**
 * Page callback: Generates a page of nodes for the site.
 *
 * Queries the database for info, adds RDFa info if applicable, and generates
 * the render array that will be used to render the page.
 *
 * @return array
 *   A renderable array.
 *
 * @see content_tab_menu()
 */
function content_tab_page($account, $node_type = NULL) {
  $user = \Drupal::currentUser();
  $noyes = array(t('No'), t('Yes'));

  // Check to see whether only allowed users are able to see unpublished nodes.
  $view_unpublished = TRUE;

  $header = array(
    array('data' => t('Title'), 'field' => 'title', 'sort' => 'asc'),
    array('data' => t('Created'), 'field' => 'created'),
    array('data' => t('Changed'), 'field' => 'changed'),
    array('data' => t('Published'), 'field' => 'status'),
    array('data' => t('Sticky'), 'field' => 'sticky'),
    array('data' => t('Promoted to front page'), 'field' => 'promoted'),
    array('data' => t('Operations'), 'field' => 'operations'),
  );
  // The logic being used is that the user needs to be able to create content
  // or to have content within the tab. Menu controls the first bit, the user
  // ID completes these restrictions.
  $nids = _content_tab_content_query($account, $view_unpublished, $node_type);
  $nodes = Node::loadMultiple($nids);
  $rows = array();
  foreach ($nodes as $node) {
    $row = array(
      'title' => array(
        'data' => array(
          '#type' => 'link',
          '#url' => $node->urlInfo(),
          '#title' => $node->getTitle(),
        ),
      ),
      'created' => array(
        'data' => \Drupal::service('date.formatter')->format($node->getCreatedTime()),
      ),
      'changed' => array(
        'data' => t('!time ago', array(
          '!time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $node->getChangedTime()),
        )),
      ),
      'published' => array(
        'data' => $noyes[$node->isPublished()],
      ),
      'sticky' => array(
        'data' => $noyes[$node->isSticky()],
      ),
      'promoted' => array(
        'data' => $noyes[$node->isPromoted()],
      ),
    );
    $rows[] = $row;
  }
  $page['tracker'] = array(
    '#rows' => $rows,
    '#header' => $header,
    '#type' => 'table',
    '#empty' => t('No content available.'),
  );
  $page['pager'] = array(
    '#theme' => 'pager',
    '#weight' => 10,
  );
  $page['#sorted'] = TRUE;
  return $page;
}
