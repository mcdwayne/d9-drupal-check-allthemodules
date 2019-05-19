<?php

namespace Drupal\wikiloc\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'wikiloc_map_field_default' widget.
 *
 * @FieldWidget(
 *   id = "wikiloc_map_field_default",
 *   label = @Translation("Wikiloc Field default"),
 *   field_types = {
 *     "wikiloc_map_field"
 *   }
 * )
 */
class MapWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element += array(
      '#type' => 'fieldset',
      '#title' => t('Trail or waypoints (Wikiloc)'),
    );

    $element['id'] = array(
      '#type' => 'textfield',
      '#title' => t('Wikiloc Id'),
      '#size' => 10,
      '#maxlength' => 10,
      '#required' => FALSE,
      '#default_value' => (isset($items[$delta]->id)) ? $items[$delta]->id : '',
    );

    $element['measures'] = array(
      '#type' => 'select',
      '#title' => t('Trail data'),
      '#options' => array(
        0 => t('Off'),
        1 => t('On'),
      ),
      '#default_value' => (isset($items[$delta]->measures)) ? $items[$delta]->measures : FALSE,
      '#description' => t('Show trail data, like distance, max. and min. height, difficulty, completion time, etc...'),
    );

    $element['near'] = array(
      '#type' => 'select',
      '#title' => t('Show nearest location'),
      '#options' => array(
        0 => t('Off'),
        1 => t('On'),
      ),
      '#default_value' => (isset($items[$delta]->near)) ? $items[$delta]->near : FALSE,
      '#description' => t('Show the nearest location to the trail'),
    );

    $element['images'] = array(
      '#type' => 'select',
      '#title' => t('Images'),
      '#options' => array(
        0 => t('Off'),
        1 => t('On'),
      ),
      '#default_value' => (isset($items[$delta]->images)) ? $items[$delta]->images : FALSE,
      '#description' => t("Show trail's images"),
    );

    $element['maptype'] = array(
      '#type' => 'select',
      '#title' => t('Map type'),
      '#options' => array(
        'M' => t('Normal Map'),
        'S' => t('Satellite Map'),
        'H' => t('Hybrid Map'),
        'T' => t('Terrain (physical) Map'),
      ),
      '#default_value' => (isset($items[$delta]->maptype)) ? $items[$delta]->maptype : 'T',
    );

    $element['width'] = array(
      '#type' => 'textfield',
      '#title' => t('Iframe width'),
      '#default_value' => (isset($items[$delta]->width)) ? $items[$delta]->width : '100%',
      '#size' => 10,
      '#maxlength' => 10,
      '#description' => t('You can specify any valid HTML units (%, px, em, etc...). If no units specified, pixels will be used'),
    );
    $element['height'] = array(
      '#type' => 'textfield',
      '#title' => t('Iframe height'),
      '#default_value' => (isset($items[$delta]->height)) ? $items[$delta]->height : 400,
      '#size' => 10,
      '#maxlength' => 10,
      '#description' => t('You can specify any valid HTML units (%, px, em, etc...). If no units specified, pixels will be used'),
    );

    $element['metricunits'] = array(
      '#type' => 'select',
      '#title' => t('Metric units'),
      '#options' => array(
        0 => t('Off'),
        1 => t('On'),
      ),
      '#default_value' => (isset($items[$delta]->metricunits)) ? $items[$delta]->metricunits : FALSE,
      '#description' => t('To force metric units when the language is in english'),
    );

    return $element;
  }

}
