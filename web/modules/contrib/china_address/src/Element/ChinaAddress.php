<?php

/**
 * @file
 * Contains \Drupal\china_address\Element\ChinaAddress.
 */

namespace Drupal\china_address\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Defines the china address element with pulldown select options.
 *
 * @FormElement("china_address")
 */
class ChinaAddress extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => array(
        array($class, 'processChinaAddress'),
        array($class, 'processAjaxForm'),
      ),
      '#theme' => 'china_address',
      '#theme_wrappers' => array('form_element'),
      '#options' => array(),
    );
  }

  /**
   * Processes a select list form element.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processChinaAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = !empty($element['#options']) ? $element['#options'] : array();

    $element['#attached']['library'][] = 'china_address/china_address.main';
    
    $element['#attached']['drupalSettings']['china_address']['sheng_val'] = $options['sheng_val'];
    $element['#attached']['drupalSettings']['china_address']['shi_val'] = $options['shi_val'];
    $element['#attached']['drupalSettings']['china_address']['xian_val'] = $options['xian_val'];
    $element['#attached']['drupalSettings']['china_address']['xiang_val'] = $options['xiang_val'];

    $element['province'] = array(
      '#type' => 'select',
      '#title' => t('Province'),
      '#id' => 'sheng',
      '#name' => 'province',
      '#options' => array(),
      '#default_value' => '', 
      '#validated' => TRUE,
    );
    $element['city'] = array(
      '#type' => 'select',
      '#title' => t('City'),
      '#id' => 'shi',
      '#name' => 'city',
      '#options' => array(),
      '#default_value' => '',
      '#validated' => TRUE,
    );
    $element['country'] = array(
      '#type' => 'select',
      '#title' => t('Country'),
      '#id' => 'xian',
      '#name' => 'country',
      '#options' => array(),
      '#default_value' => '',
      '#validated' => TRUE,
    );
    $element['street'] = array(
      '#type' => 'select',
      '#title' => t('Street'),
      '#id' => 'xiang',
      '#name' => 'street',
      '#options' => array(),
      '#default_value' => '',
      '#validated' => TRUE,
    );

    return $element;
  }

}
