<?php

namespace Drupal\amp\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\amp\AmpFormTrait;

/**
 * Plugin implementation of the 'amp_image_carousel' formatter.
 *
 * @FieldFormatter(
 *   id = "amp_image_carousel",
 *   label = @Translation("AMP Image Carousel"),
 *   description = @Translation("Display images in a carousel."),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class AmpImageCarousel extends ImageFormatter {

  use AmpFormTrait;

  /**
   * AMP layouts
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   Array of layout options allowed by this component.
   */
  private function getLayouts() {
    $options = $this->allLayouts();
    unset($options['container']);
    unset($options['intrinsic']);
    return $options;
  }

  /**
   * AMP libraries
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   The names of the AMP libraries used by this formatter.
   */
  private function getLibraries() {
    return ['amp/amp.carousel'];
  }

 /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'slides',
      'layout' => 'responsive',
      'width' => '',
      'height' => '',
      'autoplay' => FALSE,
      'controls' => FALSE,
      'loop' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['type'] = [
      '#type' => 'select',
      '#title' => t('Carousel type'),
      '#default_value' => $this->getSetting('type'),
      '#options' => [
        'carousel' => 'carousel',
        'slides' => 'slides',
      ],
    ];

    $form['layout'] = $this->layoutElement();
    $form['layout']['#description'] .= ' ' . $this->t('The "carousel" type only supports the fixed, fixed-height, and nodisplay layouts. The "slides" type supports the fill, fixed, fixed-height, flex-item, nodisplay, and responsive layouts.');
    $form['width'] = $this->widthElement();
    $form['height'] = $this->heightElement();
    $form['autoplay'] = $this->autoplayElement();;
    $form['controls'] = $this->controlsElement();;
    $form['loop'] = $this->loopElement();;

    $form['#prefix'] = '<div class="description">' . $this->libraryDescription() . '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary = $this->addToSummary($summary);
    return [implode('; ', $summary)];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {

    $elements = [];
    $elements['#items'] = parent::viewElements($items, $langcode);
    $layout = $this->getSetting('layout');
    $width = $this->validWidth($this->getSetting('width'), $this->getSetting('layout'));
    $height = $this->validHeight($this->getSetting('height'), $this->getSetting('layout'));

    foreach ($elements['#items'] as $delta => $item) {
      $elements['#items'][$delta]['#item_attributes']['layout'] = $layout;
      $elements['#items'][$delta]['#item_attributes']['width'] = $width;
      $elements['#items'][$delta]['#item_attributes']['height'] = $height;
      $elements['#items'][$delta]['#item_attributes'] = array_filter($elements['#items'][$delta]['#item_attributes']);
    }
    $elements['#attributes']['type'] = $this->getSetting('type');
    $elements['#attributes']['layout'] = $layout;
    $elements['#attributes']['width'] = $width;
    $elements['#attributes']['height'] = $height;
    $elements['#attributes']['controls'] = $this->getSetting('controls');
    $elements['#attributes']['loop'] = $this->getSetting('loop');
    $elements['#attributes'] = array_filter($elements['#attributes']);

    $elements['#theme'] = 'amp_image_carousel';
    $elements['#attached']['library'] = $this->getLibraries();

    return $elements;
  }
}
