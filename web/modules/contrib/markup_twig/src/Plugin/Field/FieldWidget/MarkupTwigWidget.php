<?php

/**
 * @file
 * Contains \Drupal\markup_twig\Plugin\Field\FieldWidget\MarkupTwigWidget.
 */

namespace Drupal\markup_twig\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\markup\Plugin\Field\FieldWidget\MarkupWidget;

/**
 * Plugin implementation of the 'markup_twig' widget.
 *
 * @FieldWidget(
 *   id = "markup_twig",
 *   label = @Translation("Markup Twig"),
 *   field_types = {
 *     "markup"
 *   }
 * )
 */
class MarkupTwigWidget extends MarkupWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Add global context information:
    $context = _markup_twig_get_twig_global_context();
    // Add entity context information:
    $entity = $items->getEntity();
    if(!empty($entity)){
      $entity_type = $entity->getEntityTypeId();
      $context[$entity_type] = $entity;
    }    
    
    // Add field_name context:
    $context['field_name'] = $items->getName();

    // Widget only
    $context['widget_form'] = $form;
    $context['widget_form_state'] = $form_state;

    $value = $this->fieldDefinition->getSetting('markup')['value'];
    $format = $this->fieldDefinition->getSetting('markup')['format'];
    $element['markup'] = _markup_twig_build_element_inline_template($value, $format, '', $context);
    return $element;
  }

}
