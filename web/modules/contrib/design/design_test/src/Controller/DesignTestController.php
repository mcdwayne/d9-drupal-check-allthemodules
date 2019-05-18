<?php
/**
 * @file
 * Contains \Drupal\design_test\Controller\DesignTestController.
 */

namespace Drupal\design_test\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for design_test routes.
 */
class DesignTestController implements ContainerInjectionInterface {

  /**
   * Constructs a DesignTestController object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Lists available test categories.
   *
   * This is a specialized version of system_admin_menu_block_page(), which
   * retrieves all direct child menu links of the current page, regardless of
   * their type, skips default local tasks, and outputs them as a simple menu
   * tree as the main page content.
   *
   * @return array
   *   A render array containing a menu link tree.
   */
  public function categoryListPage() {
    $link = menu_link_get_preferred();
    $tree = menu_build_tree($link['menu_name'], array(
      'expanded' => array($link['mlid']),
      'min_depth' => $link['depth'] + 1,
      'max_depth' => $link['depth'] + 2,
    ));
    // Local tasks are hidden = -1, so normally not rendered in menu trees.
    foreach ($tree as &$data) {
      // Exclude default local tasks.
      if (!($data['link']['type'] & MENU_LINKS_TO_PARENT)) {
        $data['link']['hidden'] = 0;
      }
    }
    $build = menu_tree_output($tree);
    return $build;
  }

  /**
   * Lists available tests in a category.
   *
   * @param string $category
   *   The design test category being currently accessed.
   *   Maps to the subdirectory names of this module.
   *
   * @return array
   *   A render array containing a list of links.
   */
  public function categoryPage($category) {
    $module_path = drupal_get_path('module', 'design_test');
    $tests = file_scan_directory("$module_path/$category", '/\.inc$/', array('key' => 'name'));
    $build['tests'] = array(
      '#theme' => 'links',
      '#links' => array(),
    );
    foreach ($tests as $name => $file) {
      $build['tests']['#links'][$name] = array(
        'title' => ucfirst($name),
        'route_name' => "design_test.$category.test",
        'route_parameters' => array(
          'test' => $name,
        ),
      );
    }
    $build['#title'] = ucfirst($category);
    return $build;
  }

  public function testPage($test) {
    $path = drupal_get_path('module', 'design_test');
    include_once DRUPAL_ROOT . "/$path/page/$test.inc";

    $test = strtr($test, array('-' => '_'));
    $function = 'design_test_page_' . $test;
    $build = $function();

    $test = strtr($test, array('_' => ' '));
    $build['#title'] = ucfirst($test);
    return $build;
  }

}
