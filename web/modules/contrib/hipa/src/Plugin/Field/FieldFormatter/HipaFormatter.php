<?php

namespace Drupal\hipa\Plugin\Field\FieldFormatter;

use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hipa\Controller\HipaController;

/**
 * Plugin for hipa image formatter.
 *
 * @FieldFormatter(
 *   id = "hipa",
 *   label = @Translation("Hide Path"),
 *   field_types = {
 *     "image",
 *   },
 *   settings = {
 *     "hipa_image_style" = "",
 *   },
 * )
 */
class HipaFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'hipa_image_style' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $image_styles = ['default' => t('None (original image)')] + image_style_options(FALSE);
    $element['hipa_image_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('hipa_image_style'),
      '#options' => $image_styles,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $image_styles = image_style_options(FALSE);
    unset($image_styles['']);
    $image_style_setting = $this->getSetting('hipa_image_style');
    if (isset($image_styles[$image_style_setting])) {
      $summary[] = t('Image style: @style', array(
        '@style' => $image_styles[$image_style_setting],
      ));
    }
    else {
      $summary[] = t('Original image');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $files = $this->getEntitiesToView($items, $langcode);
    $all_files = $items->getValue();
    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }
    $image_style_setting = $this->getSetting('hipa_image_style');
    // Collect cache tags to be added for each item in the field.
    foreach ($files as $delta => $file) {
      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $fid = $all_files[$delta]['target_id'];
      unset($item->_attributes);
      $code = HipaController::generateCode($fid, $image_style_setting);
      $elements[$delta] = array(
        '#theme' => 'image',
        '#uri' => sprintf('hipa/%s/%s/%s', $fid, $image_style_setting, $code),
        '#title' => $all_files[$delta]['title'] ? $all_files[$delta]['title'] : '',
        '#alt' => $all_files[$delta]['alt'] ? $all_files[$delta]['alt'] : '',
      );
    }
    return $elements;
  }

}
