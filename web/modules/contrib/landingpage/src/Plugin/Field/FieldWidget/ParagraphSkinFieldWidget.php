<?php

namespace Drupal\landingpage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'paragraph_skin_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "paragraph_skin_field_widget",
 *   label = @Translation("Paragraph skin field widget"),
 *   field_types = {
 *     "paragraph_skin_field_type"
 *   }
 * )
 */
class ParagraphSkinFieldWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';

    $element = array(
      '#type' => 'fieldset',
    );

    $element['value'] = array(
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'landingpage.autocomplete',
      '#autocomplete_route_parameters' => array(),
      '#default_value' => $value,
      //'#suffix' => '<input class="jscolor" value="000000">', 
    );
    // Colorpicker is temporary turned off. TODO: put it in frontent with landingpage_geysir module
    /*$element['colorpicker'] = array(
      '#type' => 'textfield',
      '#default_value' => '000000',
      '#size' => 6,
      '#attributes' => array(
        'class' => array(
          'jscolor',
        ),
        'onchange' => 'var val = jQuery(this).parent().parent().find(".ui-autocomplete-input").val(); if(val.indexOf("#") > -1 ) { var parts = val.split("#"); jQuery(this).parent().parent().find(".ui-autocomplete-input").val(parts[0] + "#" + this.jscolor + ";") }',
      ),
    );*/

    //$element['#attached']['library'][] = 'landingpage/colorpicker';

    // Return array('value' => $element);.
    return $element;
  }

}
