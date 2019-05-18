<?php

namespace Drupal\baidumap_fieldtype\Plugin\Field\FieldWidget;

use Drupal;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'BaidumapFieldtypeDefaultWidget' widget.
 *
 * @FieldWidget(
 *   id = "BaidumapFieldtypeDefaultWidget",
 *   label = @Translation("Baidumap select"),
 *   field_types = {
 *     "BaidumapFieldtype"
 *   }
 * )
 */
class BaidumapFieldtypeDefaultWidget extends WidgetBase {

  /**
   * Define the form for the field type.
   * 
   * Inside this method we can define the form used to edit the field type.
   * 
   * Here there is a list of allowed element types: https://goo.gl/XVd4tA
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta, 
    Array $element, 
    Array &$form, 
    FormStateInterface $formState
  ) {    
    $element = array(
      '#type' => 'fieldset',
      '#title' => SafeMarkup::format('@label', array('@label' => 'Location Info')),
      '#tree' => TRUE,
    );
    // Location
    $element['location'] = [
      '#type' => 'hidden',
      '#title' => t('Location'),

      // Set here the current value for this field, or a default value (or 
      // null) if there is no a value
      '#default_value' => isset($items[$delta]->location) ? 
          $items[$delta]->location : null,

      '#empty_value' => '',
      '#placeholder' => t('Location'),
      '#attributes' => array('class' => array('baidumap-location')),
    ];

    $element['address'] = [
      '#type' => 'textarea',
      '#title' => t('Address'),

      // Set here the current value for this field, or a default value (or 
      // null) if there is no a value
      '#default_value' => isset($items[$delta]->address) ? 
          $items[$delta]->address : null,

      '#empty_value' => '',
      '#placeholder' => t('Address'),
      '#attributes' => array('class' => array('baidumap-address')),
    ];


    $element['phone'] = [
      '#type' => 'textfield',
      '#title' => t('Phone'),

      // Set here the current value for this field, or a default value (or 
      // null) if there is no a value
      '#default_value' => isset($items[$delta]->phone) ? 
          $items[$delta]->phone : null,

      '#empty_value' => '',
      '#placeholder' => t('Phone'),
      '#attributes' => array('class' => array('baidumap-phone')),
    ];

    $element['profile'] = [
      '#type' => 'textarea',
      '#title' => t('Profile'),

      // Set here the current value for this field, or a default value (or 
      // null) if there is no a value
      '#default_value' => isset($items[$delta]->profile) ? 
          $items[$delta]->profile : null,

      '#empty_value' => '',
      '#placeholder' => t('Profile'),
      '#attributes' => array('class' => array('baidumap-profile')),
    ];

    // Map
    $element['map'] = [
      '#theme' => 'baidumap_fieldtype_widget',   
      '#attached' => [
        'library' => [
          'baidumap_fieldtype/baidumap',
          'baidumap_fieldtype/baidumap.widget_widget',
          'baidumap_fieldtype/baidumap.api',
          'baidumap_fieldtype/baidumap.SearchInfoWindow'
        ],
        'drupalSettings' => [
          'baidumap' =>[
             'address' => isset($items[$delta]->address) ? 
          $items[$delta]->address : null,
             'phone' => isset($items[$delta]->phone) ? 
          $items[$delta]->phone : null,
             'profile' => isset($items[$delta]->profile) ? 
          $items[$delta]->profile : null,
          ]
        ]
      ]
    ];

    return $element;
  }
} // class