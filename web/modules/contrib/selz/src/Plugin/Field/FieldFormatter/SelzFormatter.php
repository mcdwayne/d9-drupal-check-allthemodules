<?php

/**
 * @file
 * Contains \Drupal\simple_gmap\Plugin\Field\FieldFormatter\SelzFormatter.
 */

namespace Drupal\selz\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Plugin implementation of the 'selz' formatter.
 *
 * @FieldFormatter(
 *   id = "selz",
 *   label = @Translation("Selz Link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */

class SelzFormatter extends LinkFormatter {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
     'type' => 'widget',
     'position' => 'right',
     'button_color' => '6D48CC',
     'button_color_text' => 'FFFFFF',
     'header_color_order' => '6D48CC',
     'header_color_order_text' => 'FFFFFF',
     'button_text' => 'Get it now',
     'logos' => FALSE,
     'overlay' => TRUE,
     'fixed_width' => '',
     'url_value' => 'http://selz.co/',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    //$elements = parent::settingsForm($form, $form_state);

    $elements['type'] = array(
    '#title' => $this->t('Display type'),
    '#type' => 'select',
    '#options' => array(
      'widget' => $this->t('Widget'),
      'button' => $this->t('Button'),
    ),
    '#default_value' => $this->getSetting('type'),
    );

    $elements['fixed_width'] = array(
      '#title' => $this->t('Fixed width (px)'),
      '#type' => 'textfield',
      '#size' => 4,
      '#maxlength' => 6,
      '#description' => t('Introduces a fixed width in pixels for a non-fluid behavior'),
      '#states' => array(
        'visible' => array(
          ':input[name*="[settings_edit_form][settings][type]"]' => array('value' => 'widget'),
        ),
      ),
      '#default_value' => $this->getSetting('fixed_width'),
    );

    $elements['position'] = array(
      '#title' => $this->t('Price position'),
      '#type' => 'select',
      '#options' => array(
        'right' => $this->t('Right'),
        'above' => $this->t('Above'),
        'fluid_above' => t('Fluid above'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name*="[settings_edit_form][settings][type]"]' => array('value' => 'button'),
        ),
      ),
      '#default_value' => $this->getSetting('position'),
    );

    $elements['button_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button color'),
      '#default_value' => $this->getSetting('button_color'),
    );

    $elements['button_color_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Button color text'),
      '#default_value' => $this->getSetting('button_color_text'),
    );

    $elements['header_color_order'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Checkout header color'),
      '#default_value' => $this->getSetting('header_color_order'),
    );

    $elements['header_color_order_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Checkout header color text'),
      '#default_value' => $this->getSetting('header_color_order_text'),
    );

    $elements['button_text'] = array(
      '#title' => $this->t('Button text'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('button_text'),
    );

    $elements['logos'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add payment logos'),
      '#default_value' => !empty($this->getSetting('logos')) ? (int) $this->getSetting('logos') : 0,
    );

    $elements['overlay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Overlay'),
      '#description' => $this->t('Buyers interact in an overlay layer'),
      '#default_value' => !empty($this->getSetting('overlay')) ? (int) $this->getSetting('overlay') : 0,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $button_text = SafeMarkup::checkPlain($this->getSetting('button_text'));
    $width_fixed = SafeMarkup::checkPlain($this->getSetting('fixed_width'));
    $overlay = $this->getSetting('overlay') ? $this->t('Yes') : $this->t('No');
    $logos = $this->getSetting('logos') ? $this->t('Yes') : $this->t('No');

    $summary = array();
    $summary[] = $this->t('Type: @type', array('@type' => $this->getSetting('type')));
    $summary[] = $this->t('Button text: @button_text', array('@button_text' => $button_text));
    $summary[] = $this->t('Overlay: @overlay', array('@overlay' => $overlay));
    $summary[] = $this->t('Add payment logos: @logos', array('@logos' => $logos));

    if ($settings['type'] == 'widget' && !empty($width_fixed)) {
      $summary[] = $this->t('Fixed width: @fixed_width px', array('@fixed_width' => $fixed_width));
    }
    else {
      $summary[] = $this->t('Position: @position', array('@position' => $this->getSetting('position')));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $element = array();
    $settings = $this->getSettings();

    $type = $settings['type'];
    $position = $settings['position'];
    $button_color = SafeMarkup::checkPlain($settings['button_color']);
    $button_color_text = SafeMarkup::checkPlain($settings['button_color_text']);
    $header_color_order = SafeMarkup::checkPlain($settings['header_color_order']);
    $header_color_order_text = SafeMarkup::checkPlain($settings['header_color_order_text']);
    $button_text = $settings['button_text'];
    $logos = (int) $settings['logos'] ? TRUE : FALSE;
    $overlay = (int) $settings['overlay'] ? TRUE : FALSE;
    $fixed_width =  SafeMarkup::checkPlain($settings['fixed_width']);

    foreach ($items as $delta => $item) {
      $url_value = $this->buildUrl($item);
      if (empty($settings['url_only']) && !empty($item->title)) {
        $button_text = $item->title;
      }

      $element[$delta] = array(
        '#theme' => 'selz_output',
        '#type' => $type,
        '#position' => $position,
        '#button_color' => $button_color,
        '#button_color_text' => $button_color_text,
        '#header_color_order' => $header_color_order,
        '#header_color_order_text' => $header_color_order_text,
        '#button_text' => $button_text,
        '#logos' => $logos,
        '#overlay' => $overlay,
        '#fixed_width' => $fixed_width,
        '#url_value' => $url_value,
      );
    }
    return $element;
  }
}
