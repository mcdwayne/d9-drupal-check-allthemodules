<?php

namespace Drupal\amp\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\amp\AmpFormTrait;

/**
 * Plugin implementation of the 'amp_video' formatter.
 *
 * @FieldFormatter(
 *   id = "amp_video",
 *   label = @Translation("AMP Video"),
 *   description = @Translation("Display a video file as amp-video."),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class AmpVideoFormatter extends GenericFileFormatter {

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
    return ['amp/amp.video'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'height' => 175,
      'width' => 350,
      'layout' => 'responsive',
      'autoplay' => FALSE,
      'controls' => FALSE,
      'loop' => FALSE,
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $layout = $this->getSetting('layout');
    $width = $this->validWidth($this->getSetting('width'), $this->getSetting('layout'));
    $height = $this->validHeight($this->getSetting('height'), $this->getSetting('layout'));

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $elements[$delta]['#theme'] = 'amp_video';
      $elements[$delta]['#attributes']['width'] = $width;
      $elements[$delta]['#attributes']['height'] = $height;
      $elements[$delta]['#attributes']['layout'] = $layout;
      $elements[$delta]['#attributes']['controls'] = $this->getSetting('controls');
      $elements[$delta]['#attributes']['loop'] = $this->getSetting('loop');
      $elements[$delta]['#attributes']['src'] = file_create_url($file->getFileUri());
      $elements[$delta]['#cache'] = ['tags' => $file->getCacheTags()];
    }
    $elements['#attached']['library'] = $this->getLibraries();
    dpm($elements);
    return $elements;
  }
}
