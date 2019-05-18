<?php

namespace Drupal\amp\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\amp\AmpFormTrait;

/**
 * Plugin implementation of the 'amp_image' formatter.
 *
 * @FieldFormatter(
 *   id = "amp_image",
 *   label = @Translation("AMP Image Formatter"),
 *   description = @Translation("Display an image file as amp-image."),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class AmpImageFormatter extends ImageFormatter {

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
    return ['amp/amp.image'];
  }

 /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'layout' => 'responsive',
      'width' => '',
      'height' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['layout'] = $this->layoutElement();
    $form['width'] = $this->widthElement();
    $form['height'] = $this->heightElement();

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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $layout = $this->getSetting('layout');
    $width = $this->validWidth($this->getSetting('width'), $this->getSetting('layout'));
    $height = $this->validHeight($this->getSetting('height'), $this->getSetting('layout'));

    foreach ($elements as $delta => $element) {
      $elements[$delta]['#item_attributes']['layout'] = $layout;
      $elements[$delta]['#item_attributes']['width'] = $width;
      $elements[$delta]['#item_attributes']['height'] = $height;
      $elements[$delta]['#item_attributes'] = array_filter($elements[$delta]['#item_attributes']);
    }
    $elements['#attached']['library'] = $this->getLibraries();
    return $elements;
  }
}
