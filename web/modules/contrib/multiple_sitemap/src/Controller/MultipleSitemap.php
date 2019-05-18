<?php
/**
 * @file
 * Contains \Drupal\multiple_sitemap\MultipleSitemap.
 */

namespace Drupal\multiple_sitemap\Controller;

use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Vocabulary;

class MultipleSitemap {

  /**
   * Get all content types.
   *
   * @return array
   *   Having content types.
   */
  public static function multipleSitemapGetNodeTypes() {
    $types = array();
    $content_types = NodeType::loadMultiple();

    if (!empty($content_types)) {
      foreach ($content_types as $type => $details) {
        $types[$details->id()] = $details->label();
      }
      asort($types);
    }

    return $types;
  }

  /**
   * Get all menu types.
   *
   * @return array
   *   Having menu types.
   */
  public static function multipleSitemapGetMenuTypes() {
    $menus = array();
    $menu_types = Menu::loadMultiple();

    if (!empty($menu_types)) {
      foreach ($menu_types as $menu_name => $menu) {
        $menus[$menu_name] = $menu->label();
      }
      asort($menus);
    }

    return $menus;
  }

  /**
   * Get all vocabs types.
   *
   * @return array
   *   Having vocabs types.
   */
  public static function multipleSitemapGetVocabTypes() {
    $vocabs = array();
    $vocabs_types = Vocabulary::loadMultiple();

    if (!empty($vocabs_types)) {
      foreach ($vocabs_types as $vocab_name => $vocab) {
        $vocabs[$vocab_name] = $vocab->label();
      }
      asort($vocabs);
    }

    return $vocabs;
  }

  /**
   * Return all priority values.
   *
   * @return array
   *   Having priority values.
   */
  public static function multiple_sitemap_get_priority_options() {

    $priority = array(
      '0.1' => '0.1',
      '0.2' => '0.2',
      '0.3' => '0.3',
      '0.4' => '0.4',
      '0.5' => '0.5',
      '0.6' => '0.6',
      '0.7' => '0.7',
      '0.8' => '0.8',
      '0.9' => '0.9',
      '1.0' => '1.0',
    );

    return $priority;
  }

  /**
   * Return all changefreq values.
   *
   * @return array
   *   Having changefreq values.
   */
  public static function multiple_sitemap_get_changefreq_options() {

    $changefreq = array(
      'always' => 'always',
      'hourly' => 'hourly',
      'daily' => 'daily',
      'weekly' => 'weekly',
      'monthly' => 'monthly',
      'yearly' => 'yearly',
      'never' => 'never',
    );

    return $changefreq;
  }
}
