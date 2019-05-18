<?php

/**
 * @file
 * Theme functions for Monster Menus
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\monster_menus\Constants;

/**
 * Format a tooltip activation string
 *
 * @param $variables
 *   An associative array containing:
 *   - text: The text of the link that starts the tooltip
 *   - title: The popup tooltip's title
 *   - tip: The popup tooltip's text. Can be a string or a render array.
 *   - html: If TRUE, text and tip should be raw HTML, not filtered
 *
 * @ingroup themeable
 */
function monster_menus_preprocess_tooltip(&$variables) {
  if (empty($variables['html'])) {
    $variables['text'] = Html::escape($variables['text']);
  }
  $attrib = ['onclick' => 'return false'];
  if (empty($variables['html'])) {
    $attrib['title'] = strip_tags($variables['tip']);
  }
  else if (is_array($variables['tip'])) {
    $variables['html_tip'] = Html::escape(render($variables['tip']));
  }
  else {
    $variables['html_tip'] = Html::escape($variables['tip']);
  }
  $variables['attributes'] = new Attribute($attrib);
}

/**
 * Preprocess an mm_archive_header theme item.
 *
 * @param $variables
 *   An associative array containing:
 *   - frequency: Duration of time this archive references
 *   - date: Date of this archive
 * @ingroup themeable
 */
function monster_menus_preprocess_mm_archive_header(&$variables) {
  $format = array(
    'year' => t('Archive for %Y'),
    'month' => t('Archive for %B %Y'),
    'week' => t('Archive for the week of %B %e, %Y'),
    'day' => t('Archive for %A, %B %e, %Y'),
  );
  $variables['header'] = strftime($format[$variables['frequency']], $variables['date']);
}

/**
 * Preprocess an mm_archive theme item.
 *
 * @param $variables
 *   An associative array containing:
 *   - list
 *   - frequency
 *   - this_mmtid
 *   - main_mmtid
 *   - archive_mmtid
 *   - date
 * @ingroup themeable
 */
function monster_menus_preprocess_mm_archive(&$variables) {
  $formats = [
    ['format' => '%Y', 'name' => 'year',  'label' => t('Year')],
    ['format' => '%B', 'name' => 'month', 'label' => t('Month')],
    [
      'format' => $variables['frequency'] == 'week' ? t('week of %b %e') : '%e',
      'name' => $variables['frequency'],
      'label' => $variables['frequency'] == 'week' ? t('Week') : t('Day'),
    ],
  ];

  $recurs = function($list, $offset, $mmtid, &$period = NULL) use ($formats, &$recurs) {
    $return = [];
    ksort($list, SORT_NUMERIC);
    foreach ($list as $item) {
      if (is_array($item)) {
        $below = $recurs($item, $offset + 1, $mmtid, $period);
        $return[] = array(
          'below' => $below,
          'title' => trim(strftime($formats[$offset]['format'], $period)),
          'attributes' => new Attribute([
            'id' => 'mm-archive-' . $formats[$offset + 1]['name'],
            'title' => $formats[$offset + 1]['label'],
          ]),
        );
      }
      else {
        $return[] = array(
          'title' => trim(strftime($formats[$offset]['format'], $item)),
          'url' => mm_content_get_mmtid_url($mmtid, ['query' => ['_date' => strftime('%Y-%m-%d', $item)]]),
        );
        $period = $item;
      }
    }
    return $return;
  };

  $link_attributes = array('attributes' => ['class' => array('archive-link')]);
  $variables['items'] = $variables['footer'] = array();

  // Remove unneeded upper levels.
  $offset = 0;
  $list = $variables['list'];
  while (is_array($list) && count($list) == 1 && is_array($list[key($list)])) {
    $list = $list[key($list)];
    $offset++;
  }

  if (is_array($list) && !$list) {
    return;
  }

  $recent_link = [];
  if ($variables['this_mmtid'] == $variables['archive_mmtid']) {
    $recent_link = [
      'title' => t('See the most recent entries'),
      'url' => mm_content_get_mmtid_url($variables['main_mmtid'], $link_attributes),
    ];
    if (count($list) == 1) {
      $variables['footer'][] = $recent_link;
      return;
    }
  }

  if (!is_array($list) || !mm_content_user_can($variables['archive_mmtid'], Constants::MM_PERMS_READ)) {
    return;
  }

  if (count($list) == 1) {
    $link_attributes['query']['_date'] = strftime('%Y-%m-%d', $list[key($list)]);
    $variables['footer'][] = [
      'title' => t('See older entries'),
      'url' => mm_content_get_mmtid_url($variables['archive_mmtid'], $link_attributes),
    ];
    return;
  }

  $variables['items'] = $recurs($list, $offset, $variables['archive_mmtid']);
  if ($variables['items']) {
    $variables['attributes'] = new Attribute([
      'class' => ['select-menu'],
      'id'    => 'mm-archive-' . $formats[$offset]['name'],
      'title' => $formats[$offset]['label'],
    ]);
    if ($variables['this_mmtid'] == $variables['archive_mmtid']) {
      $variables['title'] = t('Choose another archive:');
      $variables['footer'][] = $recent_link;
    }
    else {
      $variables['title'] = t('Choose an archive:');
    }
  }
}

function monster_menus_preprocess_mm_help_radio(&$variables) {
  $variables['help'] = [
    '#theme' => 'tooltip',
    '#text' => t('help'),
    '#title' => $variables['element']['#title'],
    '#tip' => $variables['element']['#help'],
  ];
  $variables['label'] = [
    '#theme' => 'form_element_label',
    '#title' => $variables['element']['#title'],
    '#title_display' => 'after',
    '#id' => $variables['attributes']['id'],
  ];
}
