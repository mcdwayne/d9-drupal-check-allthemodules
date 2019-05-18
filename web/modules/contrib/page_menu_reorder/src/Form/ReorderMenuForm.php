<?php

namespace Drupal\page_menu_reorder\Form;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Class ReorderMenuForm.
 */
class ReorderMenuForm extends FormBase {

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the current node id.
    $cur_node_id = \Drupal::routeMatch()->getParameter('node')->id();
    $menu_tree = \Drupal::menuTree();

    // Currently only use the main navigation, so menu name to that.
    $menu_name = 'main';

    // Use the node id to get the current nodes menu item from main navigation.
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $result = $menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $cur_node_id], $menu_name);
    $menu_link_id = '';

    // Get the menu link id from the menu array.
    if ($result) {
      foreach ($result as $menu_link_item) {
        $menu_link_id = $menu_link_item->getPluginId();
      }
    }

    // If we have a menu link id, build the menu tree.
    if ($menu_link_id != "") {
      // Build a menu tree of all the current nodes menu children
      // with the current nodes menu link id as the parent root
      // and sort by weight.
      $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
      $parameters->setRoot($menu_link_id);
      $tree = $menu_tree->load($menu_name, $parameters);
      $manipulators = [
        [
          'callable' => 'menu.default_tree_manipulators:generateIndexAndSort',
        ],
      ];
      $tree = $menu_tree->transform($tree, $manipulators);
      $menu = $menu_tree->build($tree);
    }

    // Build the table to display our menu
    // set the headers.
    $header = [t('Menu Items'), t('Weight')];

    // Spacing at the top of the page.
    $form['top_spacing'] = [
      '#type' => 'html_tag',
      '#tag' => 'br',
    ];

    // Set caption.
    $form['table_caption'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => t('Drag and drop menu items to reorder'),
    ];

    // Create a drag drop table.
    $form['tabledrag'] = [
      '#type' => 'table',
      '#id' => 'menus-table',
      '#empty' => t('No menu items found!'),
      '#header' => $header,

      '#tabledrag' => [
        [
          // The HTML ID of the table to make draggable.
          'table_id' => 'menus-table',
          'action' => 'order',
          'relationship' => 'sibling',
          // Class name applied on all related form elements for this action.
          'group' => 'table-order-weight',
        ],
      ],
    ];

    // If we have a menu link id loop through the menu tree array
    // and output each items weight and name.
    if ($menu_link_id != "") {
      foreach ($menu['#items'] as $item) {
        foreach ($item['below'] as $menu_val) {

          // Load the node for the current child menu item in the loop.
          $nid = Node::load($menu_val['url']->getRouteParameters()['node']);

          // Check the node is published
          // If it isn't published add a message next to the title
          // on the display to say its unpublished.
          $isPublished = $nid->status->value;
          if ($isPublished) {
            $title = $menu_val['title'];
          }
          else {
            $title = $menu_val['title'] . ' (UNPUBLISHED)';
          }
          // Set the current child menu items menu link id.
          $mlid = $menu_val['original_link']->getPluginDefinition()['metadata']['entity_id'];

          // Set up draggable rows.
          $form['tabledrag'][$mlid] = [
             // Set the draggable class.
            '#attributes' => ['class' => ['draggable']],
            // Set the first column value to the title of the menu item.
            'item' => ['#plain_text' => $title],
            // Set the weight column.
            'weight' => [
              '#type' => 'weight',
              '#title_display' => 'invisible',
              '#value' => $menu_val['original_link']->getPluginDefinition()['weight'],
              '#default_value' => 0,
              '#attributes' => ['class' => ['table-order-weight']],
            ],
          ];
        }
      }

      // Submit button.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;

  }

  /**
   * Getter method for Form ID.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'reorder_menu_form';
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation to make sure we have weights entered for each item
    // set to 0 if no weight entered.
    foreach ($form_state->getvalue('tabledrag') as $menu_item) {
      if (empty($menu_item['weight']) && $menu_item['weight'] != '0') {
        $menu_item['weight'] = '0';
      }
    }
  }

  /**
   * Implements a form submit handler.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Loop through and save the weights.
    foreach ($form_state->getvalue('tabledrag') as $menu_id => $menu_item) {
      $update_link = MenuLinkContent::load($menu_id);
      $update_link->set('weight', $menu_item['weight']);
      $update_link->save();
    }
    drupal_set_message(t('The menu items order has been successfully saved.'));
  }

}
