<?php

use Drupal\block\Entity\Block;
use Drupal\block_content\BlockContentUuidLookup;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\system\MenuInterface;

/**
 * Implements hook_form_FORM_ID_alter(): node_type_form.
 */
function improvements_form_node_type_form_alter(&$form, FormStateInterface $form_state) {
  $node_type = $form_state->getFormObject()->getEntity(); /** @var NodeTypeInterface $node_type */

  $form['submission']['hide_revision_group'] = [
    '#type' => 'checkbox',
    '#title' => t('Hide revision group'),
    '#default_value' => $node_type->getThirdPartySetting('improvements', 'hide_revision_group'),
  ];

  $form['submission']['move_status_to_vertical_tab'] = [
    '#type' => 'checkbox',
    '#title' => t('Move "Status" field to vertical tab'),
    '#default_value' => $node_type->getThirdPartySetting('improvements', 'move_status_to_vertical_tab'),
  ];

  if ($node_type->isNew()) {
    $node_type->setThirdPartySetting('menu_ui', 'available_menus', []);
  }

  $form['#entity_builders'][] = 'improvements_node_type_form_entity_builder';
}

/**
 * Node type form entity builder callback.
 */
function improvements_node_type_form_entity_builder($entity_type, NodeTypeInterface $node_type, &$form, FormStateInterface $form_state) {
  $node_type->setThirdPartySetting('improvements', 'hide_revision_group', $form_state->getValue('hide_revision_group'));
  $node_type->setThirdPartySetting('improvements', 'move_status_to_vertical_tab', $form_state->getValue('move_status_to_vertical_tab'));
}

/**
 * Implements hook_form_BASE_FORM_ID_alter(): node_form.
 */
function improvements_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $node = $form_state->getFormObject()->getEntity(); /** @var NodeInterface $node */

  // Add autofocus to title field
  if ($node->isNew()) {
    $form['title']['widget'][0]['value']['#attributes']['autofocus'] = 'autofocus';
  }

  // Hide revision group
  $node_type = $node->get('type')->entity; /** @var NodeTypeInterface $node_type */
  if ($node_type->getThirdPartySetting('improvements', 'hide_revision_group') && !$form['revision']['#default_value']) {
    $form['revision_information']['#access'] = FALSE;
  }

  // Move status field to fieldset
  if ($node_type->getThirdPartySetting('improvements', 'move_status_to_vertical_tab')) {
    $form['status']['#group'] = 'options';
    $form['options']['#title'] = t('Publishing options');
  }
}

/**
 * Implements hook_form_FORM_ID_alter(): form_system_modules.
 */
function improvements_form_system_modules_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Add autofocus to modules filter input
  if (isset($form['filters']['text'])) {
    $form['filters']['text']['#attributes']['autofocus'] = 'autofocus';
  }
}

/**
 * Implements hook_field_widget_third_party_settings_form().
 */
function improvements_field_widget_third_party_settings_form(WidgetInterface $widget, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state) {
  $element = [];
  $widget_plugin_id = $widget->getPluginId();

  // Long text widgets
  if (in_array($widget_plugin_id, ['text_textarea', 'text_textarea_with_summary'])) {
    $element['dynamic_height'] = [
      '#type' => 'checkbox',
      '#title' => t('Dynamic height'),
      '#default_value' => $widget->getThirdPartySetting('improvements', 'dynamic_height'),
    ];
  }
  // File widget
  elseif ($widget_plugin_id == 'file_generic' || $widget_plugin_id == 'image_image') {
    $element['autoupload'] = [
      '#type' => 'checkbox',
      '#title' => t('Auto upload'),
      '#default_value' => $widget->getThirdPartySetting('improvements', 'autoupload', TRUE),
    ];

    $element['upload_button_state'] = [
      '#type' => 'radios',
      '#title' => t('Upload button state'),
      '#options' => [
        2 => t('Hidden'),
        1 => t('Visible'),
        0 => t('Removed'),
      ],
      '#default_value' => $widget->getThirdPartySetting('improvements', 'upload_button_state', 2),
    ];
  }

  return $element;
}

/**
 * Implements hook_field_widget_form_alter().
 */
function improvements_field_widget_form_alter(&$element, FormStateInterface $form_state, $context) {
  $widget = $context['widget']; /** @var WidgetInterface $widget */
  $widget_plugin_id = $widget->getPluginId();

  // Long text widgets
  if (in_array($widget_plugin_id, ['text_textarea', 'text_textarea_with_summary'])) {
    if ($widget->getThirdPartySetting('improvements', 'dynamic_height')) {
      $element['#rows'] = improvements_get_textarea_rows_count($element['#default_value'], $element['#rows']);
    }
  }
  // File widget
  elseif ($widget_plugin_id == 'file_generic' || $widget_plugin_id == 'image_image') {
    $element['#autoupload'] = $widget->getThirdPartySetting('improvements', 'autoupload', TRUE);
    $element['#upload_button_state'] = $widget->getThirdPartySetting('improvements', 'upload_button_state', 2);
  }
}

/**
 * Return textarea rows count by text length.
 */
function improvements_get_textarea_rows_count($text, $min_rows) {
  $rows = substr_count($text, "\n");
  $rows = min($rows + 1, 25);
  return max($rows, $min_rows);
}

/**
 * Implements hook_form_FORM_ID_alter(): block_admin_display_form.
 */
function improvements_form_block_admin_display_form_alter(&$form, FormStateInterface $form_state) {
  $block_content_uuid_lookup = \Drupal::service('block_content.uuid_lookup'); /** @var BlockContentUuidLookup $block_content_uuid_lookup */

  // Add link to edit block content in blocks layout page
  foreach (Element::children($form['blocks']) as $block_name) {
    if (isset($form['blocks'][$block_name]['operations'])) {
      $block_plugin = Block::load($block_name)->getPlugin(); /** @var BlockPluginInterface $block_plugin */

      if ($block_plugin->getBaseId() == 'block_content') {
        $block_content_id = $block_content_uuid_lookup->get($block_plugin->getDerivativeId());

        $form['blocks'][$block_name]['operations']['#links']['edit_content'] = [
          'title' => t('Edit content'),
          'url' => Url::fromRoute('entity.block_content.edit_form', ['block_content' => $block_content_id]),
          'weight' => 20,
        ];

        uasort($form['blocks'][$block_name]['operations']['#links'], '\Drupal\Component\Utility\SortArray::sortByWeightElement');
      }
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter(): block_content_form.
 */
function improvements_form_block_content_form_alter(&$form, FormStateInterface $form_state) {
  // Add autofocus to content-block name field
  $form['info']['widget'][0]['value']['#attributes']['autofocus'] = 'autofocus';
}

/**
 * Implements hook_form_FORM_ID_alter(): menu_edit_form.
 */
function improvements_form_menu_edit_form_alter(&$form, FormStateInterface $form_state) {
  $menu = $form_state->getFormObject()->getEntity(); /** @var MenuInterface $menu */

  if (!empty($form['links'])) {
    // Add link "Add children" for menu link operations
    foreach (Element::children($form['links']['links']) as $key) {
      $form['links']['links'][$key]['operations']['#links']['add'] = [
        'title' => t('Add children item'),
        'url' => Url::fromRoute('entity.menu.add_link_form', ['menu' => $menu->id()]),
        'query' => [
          'parent' => $menu->id() . ':' . $form['links']['links'][$key]['id']['#value'],
        ],
      ];
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter(): menu_link_content_form.
 */
function improvements_form_menu_link_content_form_alter(&$form, FormStateInterface $form_state) {
  // Add autofocus to menu link title
  $form['title']['widget'][0]['value']['#attributes']['autofocus'] = 'autofocus';

  // Set menu link parent if exists $_GET['parent'] param
  if ($parent = \Drupal::request()->query->get('parent')) {
    $form['menu_parent']['#default_value'] = $parent;
  }

  $menu_link = $form_state->getFormObject()->getEntity(); /** @var MenuLinkContentInterface $menu_link */
  $menu_link_options = $menu_link->link->options;

  $form['target_blank'] = [
    '#type' => 'checkbox',
    '#title' => t('Open in new tab'),
    '#default_value' => (isset($menu_link_options['attributes']['target']) && $menu_link_options['attributes']['target'] == '_blank'),
  ];

  $form['item_highlighted'] = [
    '#type' => 'checkbox',
    '#title' => t('Highlighted item'),
    '#default_value' => $menu_link_options['item_highlighted'] ?? FALSE,
  ];

  $form['#entity_builders'][] = 'improvements_menu_link_entity_builder';
}

/**
 * Menu link entity builder.
 */
function improvements_menu_link_entity_builder($entity_type, MenuLinkContentInterface $menu_link, &$form, FormStateInterface $form_state) {
  $menu_link_options = $menu_link->link->options;

  if ($form_state->getValue('target_blank')) {
    $menu_link_options['attributes']['target'] = '_blank';
  }
  else {
    if (isset($menu_link_options['attributes']['target'])) {
      unset($menu_link_options['attributes']['target']);
    }
  }

  // Clean attributes
  if (isset($menu_link_options['attributes']) && !$menu_link_options['attributes']) {
    unset($menu_link_options['attributes']);
  }

  $menu_link_options['item_highlighted'] = $form_state->getValue('item_highlighted');

  $menu_link->link->options = $menu_link_options;
}

/**
 * Implements hook_form_FORM_ID_alter(): node_type_add_form.
 */
function improvements_form_node_type_add_form_alter(&$form, FormStateInterface $form_state) {
  // Change default node type form values
  $form['submission']['preview_mode']['#default_value'] = 0;
  $form['workflow']['options']['#default_value']['promote'] = FALSE;
  $form['workflow']['options']['#default_value']['revision'] = FALSE;
  $form['display']['display_submitted']['#default_value'] = FALSE;
}

/**
 * Implements hook_form_BASE_FORM_ID_alter(): taxonomy_term_form.
 */
function improvements_form_taxonomy_term_form_alter(&$form, FormStateInterface $form_state) {
  // Add autofocus to term name field
  if (isset($form['name'])) {
    $form['name']['widget']['0']['value']['#attributes']['autofocus'] = 'autofocus';
  }
}
