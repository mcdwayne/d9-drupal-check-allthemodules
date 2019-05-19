<?php

/**
 * @file
 * Contains admin.inc.
 */

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\stacks\Widget\WidgetTemplates;
use Drupal\stacks\Widget\WidgetRequiredFields;
use Drupal\stacks\Widget\WidgetAutomaticTitleHandler;
use Drupal\Core\Entity\EntityInterface;

/**
 * Grabs the correct template drop down options.
 */
function stacks_options_templates(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, $cacheable = FALSE) {
  return WidgetTemplates::getTemplatesSelect($definition, $entity, $cacheable);
}

/**
 * Grabs the list of themes based on template. Should be defined in the config.
 */
function stacks_options_themes(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, $cacheable = FALSE) {
  return WidgetTemplates::getThemeSelect($definition, $entity, $cacheable);
}

/**
 * Get the IEF table.
 */
function stacks_widget_extend_ief_table_render($entity, $theme_variables) {
  $view_builder = Drupal::entityTypeManager()->getViewBuilder('widget_extend');
  $render_array = $view_builder->view($entity, 'widget_extend.admin_preview');
  return Drupal::service('renderer')->render($render_array);
}

/**
 * Implements hook_node_presave(). Before a node is saved (added/updated), we
 * need to see if we need to add any required widget bundles.
 *
 * Now hook_node_create() should auto add required nodes. But in the case of
 * someone adding a new required widget to a content type, this will catch those
 * use cases, because it will add these required stacks when they save an
 * existing node.
 */
function stacks_node_presave(EntityInterface $node) {
  new WidgetRequiredFields($node, TRUE);
  new WidgetAutomaticTitleHandler($node);
}

/**
 * Implements hook_node_create(). We use this to add the required widgets
 * to the node when they view the add page and for setting the automatic node titles.
 */
function stacks_node_create(EntityInterface $node) {
  new WidgetRequiredFields($node);
}