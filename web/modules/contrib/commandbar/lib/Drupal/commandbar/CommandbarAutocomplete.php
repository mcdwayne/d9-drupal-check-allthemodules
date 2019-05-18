<?php

/**
 * @file
 * Contains \Drupal\commandbar\CommandbarAutocomplete.
 */

namespace Drupal\commandbar;

use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a helper class to get commandbar autocompletion results.
 */
class CommandbarAutocomplete {

  /**
   * The config factory to get the commandbar settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a CommandbarAutocomplete object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * A recursive function to build a flat menu tree from the admin menu.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function buildFlatMenu($menu = array(), $menu_id = 'admin') {
    global $user;

    $config = $this->configFactory->get('commandbar.settings');

    // Initialize the menu.
    if (!isset($this->menu)){
      $this->full_tree = menu_tree_all_data($menu_id);
      $this->menu = reset($this->full_tree);
      $menu = $this->menu;
    }

    // Only add hidden menu items if we've configured commandbar to show them.
    $search_hidden = drupal_container()->get('user.data')->get('commandbar', $user->id(), 'deep_search_enabled');
    $pass = TRUE;
    if ($menu['link']['hidden'] == -1) {
      $pass = FALSE;
      if ($search_hidden == 1) {
        $pass = TRUE;
      }
    }

    // Reduce the full menu link to a few important attributes.
    if ($pass) {
      $description = '';
      if (isset($menu['link']['options']['attributes'])) {
        $description = $menu['link']['options']['attributes']['title'];
      }
      $parents = array();
      if (isset($this->menu_parents)) {
        $parents = $this->menu_parents;
      }
      // Don't add empty titles because they're confusing.
      if ($menu['link']['link_title'] != '') {
        $this->flat_menu[$menu['link']['link_path']] = array(
          // Add the link, title, and description to searchable text.
          'searchable' => $menu['link']['link_title'] . ' ' . $menu['link']['link_path'] . ' ' . $description,
          'path' => $menu['link']['link_path'],
          'title' => $menu['link']['link_title'],
          'description' => $description,
          'parents' => $parents,
        );
      }

      // Add to menu parent array.
      if (isset($menu['below'])) {
        if (isset($menu['link']['link_title'])) {
          $this->menu_parents[] = $menu['link']['link_title'];
        }
      }

      // Recurse through menu tree.
      foreach ($menu['below'] as $item) {
        $this->buildFlatMenu($item);
      }

      // At the end of a recursion, remove the last item from the parent array.
      if (is_array($this->menu_parents)) {
        array_pop($this->menu_parents);
      }
    }
  }

  /**
   * Get matches for the autocompletion of menu items.
   *
   * @param string $string
   *   The string to match for menu items.
   *
   * @return array
   *   An array containing the matching menu items.
   */
  public function getMatches($string) {
    $matches = array();
    $matches_secondary = array();
    $continue = TRUE;


    // Allow skipping of the menu building by other modules.
    drupal_alter('commandbar_build', $matches, $continue, $string);
    if (!$continue) {
      return $matches;
    }

    // Create the flat list of menu items.
    $this->buildFlatMenu();
    $menu = $this->flat_menu;

    // Explode string to match on all parts.
    $string_parts = explode(' ', strtolower($string));

    // Loop through each menu item and theme it.
    foreach ($menu as $item) {
      $pass = TRUE;
      foreach ($string_parts as $part) {
        if (stristr($item['searchable'], $part) === FALSE) {
          $pass = FALSE;
        }
      }
      if ($pass) {
        $path = $item['path'];
        $content = theme('commandbar_item', array(
          'url' => url($item['path']),
          'path' => $item['path'],
          'description' => $item['description'],
          'title' => $item['title'],
          'parents' => $item['parents'],
        ));
        // Match first characters and make special if a match.
        $string_length = strlen($string);
        if (strtolower(substr($string, 0, $string_length)) == strtolower(substr($item['searchable'], 0, $string_length))) {
          $matches[$path] = $content;
        } else {
          $matches_secondary[$path] = $content;
        }
        if (is_array($item['parents'])) {
          array_shift($item['parents']);
        }
      }
    }

    $matches = $matches + $matches_secondary;

    drupal_alter('commandbar_matches', $matches, $string);
    return $matches;
  }

}