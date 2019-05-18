<?php

namespace Drupal\funnel\Controller;

/**
 * @file
 * Contains \Drupal\funnel\Controller\Page.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\node\Entity\Node;

/**
 * Controller routines for page example routes.
 */
class Helpers extends ControllerBase {

  /**
   * Vocabs.
   */
  public static function vocabs($vid = FALSE) {
    $config = \Drupal::config('funnel.settings');
    $vocabs = $config->get('vocabulary');
    $list = [];
    $v = taxonomy_vocabulary_get_names();
    foreach (taxonomy_vocabulary_get_names() as $vocab) {
      if (isset($vocabs[$vocab]) && $vocabs[$vocab]) {
        $vocabulary = Vocabulary::load($vocab);
        if ($vid == FALSE) {
          $list[$vocab] = $vocabulary;
        }
        elseif ($vid && $vid == $vocab) {
          $list[$vocab] = $vocabulary;
        }
      }
    }
    if (empty($list)) {
      return FALSE;
    }
    return $list;
  }

  /**
   * Rand Tags.
   */
  public static function randTags() {
    $tags = ['price', 'edit', 'cart', 'billing', 'order', 'issue', 'login'];
    return implode(", ", [$tags[rand(0, 6)], $tags[rand(0, 6)]]);
  }

  /**
   * Rand HEX.
   */
  public static function randHex() {
    $hex = ['#5dc3f0', '#f19b60', '#6bbd49'];
    return $hex[rand(0, 2)];
  }

  /**
   * Rand HEX.
   */
  public static function randUser() {
    $users = self::getUsers();
    return rand(0, count($users) - 1);
  }

  /**
   * Users.
   */
  public static function getUsers() {
    $users = [
      [
        'id' => 0,
        'name' => 'No name',
        'image' => '/libraries/jqwidgets/jqwidgets/styles/images/common.png',
        'common' => 'true',
      ],
      [
        'id' => 1,
        'name' => 'Andrew Fuller',
        'image' => '/libraries/jqwidgets/images/andrew.png',
      ],
      [
        'id' => 2,
        'name' => 'Janet Leverling',
        'image' => '/libraries/jqwidgets/images/janet.png',
      ],
      [
        'id' => 3,
        'name' => 'Steven Buchanan',
        'image' => '/libraries/jqwidgets/images/steven.png',
      ],
      [
        'id' => 4,
        'name' => 'Nancy Davolio',
        'image' => '/libraries/jqwidgets/images/nancy.png',
      ],
      [
        'id' => 5,
        'name' => 'Michael Buchanan',
        'image' => '/libraries/jqwidgets/images/michael.png',
      ],
      [
        'id' => 6,
        'name' => 'Margaret Buchanan',
        'image' => '/libraries/jqwidgets/images/margaret.png',
      ],
      [
        'id' => 7,
        'name' => 'Robert Buchanan',
        'image' => '/libraries/jqwidgets/images/robert.png',
      ],
      [
        'id' => 8,
        'name' => 'Laura Buchanan',
        'image' => '/libraries/jqwidgets/images/laura.png',
      ],
      [
        'id' => 9,
        'name' => 'Laura Buchanan',
        'image' => '/libraries/jqwidgets/images/anne.png',
      ],
    ];
    $tags = ['price', 'edit', 'cart', 'billing', 'order', 'issue', 'login'];
    return $users;
  }

  /**
   * Load Nodes.
   */
  public static function loadNodes($vid = FALSE) {
    $nodes = [];
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'project')
      ->sort('changed', 'DESC')
      // ->condition('field_activity_group', $tid) //.
      ->range(0, 30);
    $ids = $query->execute();
    if (!empty($ids)) {
      foreach (Node::loadMultiple($ids) as $nid => $node) {
        $nodes[$nid] = $node;
      }
    }
    return $nodes;
  }

}
