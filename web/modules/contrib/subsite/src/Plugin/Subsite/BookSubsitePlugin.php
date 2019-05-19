<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 25/01/2016
 * Time: 13:40
 */

namespace Drupal\subsite\Plugin\Subsite;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\subsite\BaseSubsitePlugin;
use Drupal\subsite\SubsitePluginInterface;

/**
 * @Plugin(
 *   id = "book_subsite",
 *   label = @Translation("Book subsite options"),
 *   block_prerender = {
 *     "system_menu_block:main"
 *   },
 *   node_view_alter = TRUE,
 * )
 */
class BookSubsitePlugin extends BaseSubsitePlugin {
  public function blockPrerender($build, $node, $subsite_node) {
    if ($menu_items = $this->getMainMenuItems($node)) {
      $build['content']['#items'] = $menu_items;
    }
    return $build;
  }

  public function getMainMenuItems($node) {
    if ($this->configuration['replace_main_navigation']) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];

      if ($current_bid) {
        $book_manager = \Drupal::service('subsite.book.manager');
        $data = $book_manager->bookTreeAllData($node->book['bid'], $node->book);

        $items = $book_manager->bookTreeOutput($data);

        if ($this->configuration['touch_friendly_dropdowns']) {
          foreach ($items as &$item) {
            if (!empty($item['below'])) {
              $new_item = array(
                'attributes' => $item['attributes'],
                'title' => $item['title'],
                'url' => $item['url'],
                'below' => array(),
                'original_link' => $item['original_link'],
              );

              array_unshift($item['below'], $new_item);
            }
          }
        }
        // Mimic main menu.
//        $build['#theme'] = 'menu__main';
        return $items;
      }
      else {
        return array();
      }
    }
  }

  /**
   * Gets default configuration for this plugin.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  public function defaultConfiguration() {
    return array(
      'replace_main_navigation' => FALSE,
      'hide_book_navigation' => TRUE,
      'show_navigation_teasers' => FALSE,
      'touch_friendly_dropdowns' => FALSE,
    );
  }


  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['replace_main_navigation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Subsite main navigation'),
      '#description' => t('Use book navigation for main subsite navigation'),
      '#default_value' => $configuration['replace_main_navigation'],
    );

    $form['hide_book_navigation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide book navigation'),
      '#description' => t('Hide the contextual book navigation (e.g. next, previous, up)'),
      '#default_value' => $configuration['hide_book_navigation'],
    );

    $form['show_navigation_teasers'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show navigation teasers'),
      '#description' => t('Show navigation teasers on the subsite home page.'),
      '#default_value' => $configuration['show_navigation_teasers'],
    );

    $form['touch_friendly_dropdowns'] = array(
      '#type' => 'checkbox',
      '#title' => t('Touch friendly dropdowns'),
      '#description' => t('For use with themes like bootstrap that don\'t allow clicks on parent menu items. The parent menu item will additionally be added as the first child menu item.'),
      '#default_value' => $configuration['touch_friendly_dropdowns'],
    );

    return $form;
  }

  /**
   * @param array $build
   * @param \Drupal\Core\Entity\EntityInterface $node
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   */
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display) {
    $configuration = $this->getConfiguration();

    if ($configuration['hide_book_navigation']) {
      if (isset($build['book_navigation'])) {
        if ($node->bundle() != 'book') {
          $build['book_navigation']['#access'] = FALSE;
        }
      }
    }

    if ($configuration['show_navigation_teasers']) {
      if (isset($build['book_navigation'])) {

        if ($node->bundle() == 'sub_site') {
//      $book_navigation = array( '#theme' => 'book_navigation', '#book_link' => $node->book);
//      $build['book_navigation'] = array(
//        '#markup' => drupal_render($book_navigation),
//        '#weight' => 100,
//        '#attached' => [
//          'library' => [
//            'book/navigation',
//          ],
//        ],
//        // The book navigation is a listing of Node entities, so associate its
//        // list cache tag for correct invalidation.
//        '#cache' => [
//          'tags' => $node->getEntityType()->getListCacheTags(),
//        ],
//      );

          // Get the book and add teasers to the page.

          if ($book_link = $node->book) {
            /** @var SubsiteBookManager $book_manager */
            $book_manager = \Drupal::service('subsite.book.manager');

            if ($book_link['nid']) {
              $subtree_data = $book_manager->bookSubtreeData($book_link);

              $subtree_root = reset($subtree_data);
              if (!empty($subtree_root['below'])) {
                $child_nids = array();
                foreach ($subtree_root['below'] as $child) {
                  if (isset($child['link']['nid'])) {
                    $child_nids[] = $child['link']['nid'];
                  }
                }

                if (!empty($child_nids)) {
                  $nodes = \Drupal\node\Entity\Node::loadMultiple($child_nids);
                  $built_child_nodes = node_view_multiple($nodes, 'teaser');
                  $built_child_nodes['#weight'] = 200;
                  $build['subsite_touts'] = $built_child_nodes;
                }
              }
            }
          }
        }
      }
    }
  }
}