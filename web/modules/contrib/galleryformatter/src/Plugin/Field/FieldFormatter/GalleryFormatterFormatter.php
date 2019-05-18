<?php

namespace Drupal\galleryformatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;

/**
 * Plugin for galleryformatter.
 *
 * @FieldFormatter(
 *   id = "galleryformatter",
 *   label = @Translation("jQuery Gallery"),
 *   field_types = {
 *     "image",
 *   }
 * )
 */
class GalleryFormatterFormatter extends ResponsiveImageFormatter implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'slide_style' => 'wide',
      'thumb_style' => 'narrow',
      'style' => 'galleryformatter',
      'modal' => ''
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();

    $elements = [];
    // get a list of all style names for our elements options
    foreach ($responsive_image_styles as $id => $style) {
      $options[$id] = $id;
    }
    $elements['slide_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the slide style'),
      '#options' => $options,
      '#default_value' => $this->getSetting('slide_style'),
      '#description' => $this->t('Select the imagecache style you would like to show when clicked on the thumbnail.'),
    ];
    $elements['thumb_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the thumbnail style'),
      '#options' => $options,
      '#default_value' => $this->getSetting('thumb_style'),
      '#description' => $this->t('Select the imagecache style you would like to show for the thumbnails list.'),
    ];
    $style_options = ['@todo'];
    // @TODO Implement a plugin type instead of defining a new hook.
    $styles = [];
    // $styles = \Drupal::moduleHandler()->invokeAll('galleryformatter_styles');
    // The keys used for options must be valid html id-s.
    foreach ($styles as $style) {
      $style_options[$style] = $style;
    }
    ksort($style_options);
    $elements['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#options' => ['nostyle' => $this->t('No style')] + $style_options,
      '#default_value' => $this->getSetting('style'),
      '#description' => $this->t('Choose the gallery style.'),
    ];
    $modal_options = [];
    // integration with other modules for jQuery modal windows
    if (\Drupal::moduleHandler()->moduleExists('colorbox')) {
     $modal_options['colorbox'] = 'colorbox';
    }
    if (\Drupal::moduleHandler()->moduleExists('shadowbox')) {
      $modal_options['shadowbox'] = 'shadowbox';
    }
    if (\Drupal::moduleHandler()->moduleExists('lightbox2')) {
      $modal_options['lightbox2'] = 'lightbox2';
    }
    if (\Drupal::moduleHandler()->moduleExists('fancybox')) {
      $modal_options['fancybox'] = 'fancybox';
    }
    $modal_options['none'] = t('Do not use modal');
    $elements['modal'] = [
     '#type' => 'select',
     '#title' => $this->t('Use jQuery modal for full image link'),
     '#options' => $modal_options,
     '#default_value' => $this->getSetting('modal'),
     '#description' => $this->t("Select which jQuery modal module you'd like to display the full link image in, if any."),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];

    if (!empty($settings['slide_style']) || !empty($settings['thumb_style'])) {
      $summary[] = $this->t('Slides style: @value', ['@value' => $settings['slide_style']]);
      $summary[] = $this->t('Thumbnails style: @value', ['@value' => $settings['thumb_style']]);
      $summary[] = $this->t('Gallery style: @value', ['@value' => $settings['style']]);
      $summary[] = $this->t('Modal: @value', ['@value' => $settings['modal']]);
    }
    else {
      $summary[] = $this->t('Customize your options for the jQuery Gallery.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $thumb_elements = [];
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as $key => $item) {
      $elements[$key]['#responsive_image_style_id'] = $this->responsiveImageStyleStorage->load($this->getSetting('slide_style'))->id();
    }
    // Duplicate each element and set it a thumb_style image style.
    foreach ($elements as $element) {
      $new_element = $element;
      $new_element['#responsive_image_style_id'] = $this->responsiveImageStyleStorage->load($this->getSetting('thumb_style'))->id();
      $thumb_elements[] = $new_element;
    }

    return [
      '#theme' => 'galleryformatter',
      '#slides' => $elements,
      '#thumbs' => $thumb_elements,
      '#settings' => $this->getSettings(),
      '#dimensions' => '', // @TODO
    ];
  }
}
