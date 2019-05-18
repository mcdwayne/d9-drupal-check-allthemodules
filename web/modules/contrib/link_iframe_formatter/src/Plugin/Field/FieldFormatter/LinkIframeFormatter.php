<?php

/**
 * @file
 * Contains \Drupal\link_iframe_formatter\Plugin\Field\FieldFormatter\IframeLinkFormatter.
 *
 */

namespace Drupal\link_iframe_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link_iframe_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "link_iframe_formatter",
 *   label = @Translation("Iframe Formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkIframeFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        'width' => '640',
        'height' => '480',
        'class' => '',
        'original' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)  {

    $elements['width'] = array(
      '#title' => t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
    );

    $elements['height'] = array(
      '#title' => t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
    );

    $elements['class'] = array(
      '#title' => t('Class'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('class'),
      '#required' => FALSE,
    );
    $elements['original'] = array(
      '#title' => t('Show original link'),
      '#type' => 'radios',
      '#options' => array(
        TRUE => t('On'),
        FALSE => t('Off'),
        ),
      '#default_value' => $this->getSetting('original'),
      '#required' => FALSE,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('Width: @width, Height: @height, Class: @class, Original link is @original', array('@width' => $this->getSetting('width'), '@height' => $this->getSetting('height'), '@class' => $this->getSetting('class') == "" ? 'None' : $this->getSetting('class'), '@original' => $this->getSetting('original') ? t('On') : t('Off')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      // By default use the full URL as the link text.
      $url = $this->buildUrl($item);

      $element[$delta] = array(
          '#theme' => 'link_iframe_formatter',
          '#url' => $url,
          '#width' => $settings['width'],
          '#height' => $settings['height'],
          '#class' => $settings['class'],
          '#original' => $settings['original'],
          '#path' => $url,
        );
    }
    return $element;
  }

}
