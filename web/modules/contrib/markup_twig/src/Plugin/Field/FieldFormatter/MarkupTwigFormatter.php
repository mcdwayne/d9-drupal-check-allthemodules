<?php

/**
 * @file
 * Contains \Drupal\markup_twig\Plugin\Field\FieldFormatter\MarkupTwigFormatter.
 */

namespace Drupal\markup_twig\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\markup\Plugin\Field\FieldFormatter\MarkupFormatter;

/**
 * Plugin implementation of the 'markup_twig' formatter.
 *
 * @FieldFormatter(
 *   id = "markup_twig",
 *   label = @Translation("Markup Twig"),
 *   field_types = {
 *     "markup"
 *   }
 * )
 */
class MarkupTwigFormatter extends MarkupFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Add global context information:
    $context = _markup_twig_get_twig_global_context();
    // Add entity context information:
    $entity = $items->getEntity();
    if (!empty($entity)) {
      $entity_type = $entity->getEntityTypeId();
      $context[$entity_type] = $entity;
    }
    
    // Add field_name context:
    $context['field_name'] = $items->getName();

    // Formatter only:
    $context['formatter_field_langcode'] = $langcode;
    $context['formatter_field_view_mode'] = $this->viewMode;
    $context['formatter_field_label_position'] = $this->label;

    $value = $this->fieldDefinition->getSetting('markup')['value'];
    $format = $this->fieldDefinition->getSetting('markup')['format'];
    $element = [_markup_twig_build_element_inline_template($value, $format, $langcode, $context)];
    return $element;
  }

}
