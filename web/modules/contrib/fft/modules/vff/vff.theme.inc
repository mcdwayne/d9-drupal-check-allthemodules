<?php

/**
 * @file
 * Preprocess formatter template.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Build tree from flat array.
 *
 * @param array $flat
 *   The array.
 * @param string $idField
 *   The id field.
 * @param string $parentIdField
 *   The parent id field.
 * @param string $childNodesField
 *   The child name.
 *
 * @return array
 *   The tree array.
 */
function vff_build_tree(array $flat, $idField = 'id', $parentIdField = 'parentId', $childNodesField = 'childNodes') {
  $flat[] = [
    $idField => 0,
    $parentIdField => NULL,
    $childNodesField => [],
  ];
  $indexed = [];
  // First pass - get the array indexed by the primary id.
  foreach ($flat as $row) {
    $indexed[$row[$idField]] = $row;
    $indexed[$row[$idField]][$childNodesField] = [];
  }

  // Second pass.
  $root = NULL;
  foreach ($indexed as $id => $row) {
    $indexed[$row[$parentIdField]][$childNodesField][$id] =& $indexed[$id];
    if (!$row[$parentIdField]) {
      $root = $id;
    }
  }

  return $indexed[$root][$childNodesField];
}

/**
 * Prepares variables for views carousel template.
 *
 * Default template: views-bootstrap-carousel.html.twig.
 *
 * @param array $variables
 *   The array variables.
 */
function template_preprocess_views_formatter_template(array &$variables) {
  /** @var \Drupal\views\ViewExecutable $view */
  $view = $variables['view'];
  $rows = $variables['rows'];
  /** @var \Drupal\vff\Plugin\views\style\ViewFormatterTemplate $style */
  $style = $view->style_plugin;
  $options = $style->options;
  $template_file = fft_storage_dir() . "/" . $options['template'];
  $variables['template_rendered'] = '';
  if ($options['render_type'] === 'styled') {
    $variables['template_rendered'] = fft_render($template_file, ['data' => $rows]);
  }
  else {
    $renderedField = $style->getRenderedFields();
    if (!empty($renderedField) || !empty($options['show_empty'])) {
      if (!empty($options['vff_tree_field']) && !empty($options['vff_tree_parent_field'])) {
        foreach ($renderedField as $index => $row) {
          foreach ($renderedField[$index] as &$rendered_field) {
            if ($rendered_field instanceof MarkupInterface) {
              $rendered_field = $rendered_field->__toString();
              if (\Drupal::service('twig')->isDebug()) {
                $rendered_field = trim(preg_replace('/<!--(.|\s)*?-->\s*/', '', $rendered_field));
              }
            }
          }
        }
        $renderedField = vff_build_tree($renderedField, $options['vff_tree_field'], $options['vff_tree_parent_field']);
      } 
      else {
        if (\Drupal::service('twig')->isDebug()) {
          foreach ($renderedField as $index => $row) {
            foreach ($renderedField[$index] as &$rendered_field) {
              if ($rendered_field instanceof MarkupInterface) {
                $rendered_field = $rendered_field->__toString();
                $rendered_field = trim(preg_replace('/<!--(.|\s)*?-->\s*/', '', $rendered_field));
              }
            }
          }
        }
      }
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $langcode_content = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      $langcode_interface = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();

      $variables['template_rendered'] = fft_render($template_file, [
        'data' => $renderedField,
        'view' => $view,
        'langcode' => $langcode,
        'langcode_content' => $langcode_content,
        'langcode_interface' => $langcode_interface,
      ]);

      if (\Drupal::service('twig')->isDebug()) {
        $variables['template_rendered'] = preg_replace('/<!--(.|\s)*?-->\s*/', '', $variables['template_rendered']);
      }
    }
  }

}
