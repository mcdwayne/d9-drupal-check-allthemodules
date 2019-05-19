<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 20/04/2015
 * Time: 21:08
 */

namespace Drupal\subsite;

use Drupal\book\BookManager;
use Drupal\Core\Template\Attribute;


class SubsiteBookManager extends BookManager {
  /**
   * {@inheritdoc}
   */
  public function bookTreeAllData($bid, $link = NULL, $max_depth = NULL) {
    // Root is home
    // 2nd level links are siblings of root.
    $data = $this->bookTreeBuild($bid, array('min_depth' => 1, 'max_depth' => 1));
    $root = reset($data);
    $root['link']['title'] = t('Home');

    $tree_parameters = array(
      'min_depth' => 2,
//            'max_depth' => $max_depth,
    );

    $data += $this->bookTreeBuild($bid, $tree_parameters);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function bookTreeOutput(array $tree) {
    $build = array();
    $items = array();

    // Pull out just the book links we are going to render so that we
    // get an accurate count for the first/last classes.
    foreach ($tree as $data) {
      if ($data['link']['access']) {
        $items[] = $data;
      }
    }

    $num_items = count($items);
    foreach ($items as $i => $data) {
      $class = ['menu-item'];
      // Set a class for the <li>-tag. Since $data['below'] may contain local
      // tasks, only set 'expanded' class if the link also has children within
      // the current book.
      if ($data['link']['has_children'] && $data['below']) {
        $class[] = 'menu-item--expanded';
      }
      elseif ($data['link']['has_children']) {
        $class[] = 'menu-item--collapsed';
      }

      // Set a class if the link is in the active trail.
      if ($data['link']['in_active_trail']) {
        $class[] = 'menu-item--active-trail';
        $data['link']['localized_options']['attributes']['class'][] = 'menu-item--active-trail';
      }

      // Allow book-specific theme overrides.
//      $element['#theme'] = 'book_link__book_toc_' . $data['link']['bid'];
      $element['attributes'] = new Attribute();
      $element['attributes']['class'] = $class;
      $element['title'] = $data['link']['title'];
      $node = $this->entityManager->getStorage('node')->load($data['link']['nid']);
      $element['url'] = $node->urlInfo();
//      $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
      $element['below'] = $data['below'] ? $this->bookTreeOutput($data['below']) : $data['below'];
      $element['original_link'] = $data['link'];
      // Index using the link's unique nid.
      $build[$data['link']['nid']] = $element;
    }
//    if ($build) {
//      // Make sure drupal_render() does not re-order the links.
//      $build['#sorted'] = TRUE;
//      // Add the theme wrapper for outer markup.
//      // Allow book-specific theme overrides.
//      $build['#theme_wrappers'][] = 'book_tree__book_toc_' . $data['link']['bid'];
//    }

    return $build;
  }


}