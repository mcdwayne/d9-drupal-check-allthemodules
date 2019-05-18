<?php

namespace Drupal\spin\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\spin\SpinStorage;

/**
 * Plugin implementation of the 'spin_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "spin_formatter",
 *   module = "spin",
 *   label = @Translation("3D Spin Slideshow formatter"),
 *   field_types = {
 *     "spin"
 *   }
 * )
 */
class SpinFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'slideshow_profile' => 'default',
      'spin_profile'      => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['slideshow_profile'] = [
      '#title'         => t('Slideshow Profile'),
      '#type'          => 'select',
      '#default_value' => $this->getSetting('slideshow_profile') ? $this->getSetting('slideshow_profile') : $this->defaultSettings()['slideshow_profile'],
      '#empty_option'  => t('None (default slideshow profile)'),
      '#options'       => SpinStorage::getOptions('slideshow'),
    ];
    $element['spin_profile'] = [
      '#title'         => t('Spin Profile'),
      '#type'          => 'select',
      '#default_value' => $this->getSetting('spin_profile') ? $this->getSetting('spin_profile') : $this->defaultSettings()['spin_profile'],
      '#empty_option'  => t('None (default spin profile)'),
      '#options'       => SpinStorage::getOptions('spin'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $slideshow_profile = $this->getSetting('slideshow_profile') ? SpinStorage::getLabel($this->getSetting('slideshow_profile')) : '';
    $spin_profile = $this->getSetting('spin_profile') ? SpinStorage::getLabel($this->getSetting('spin_profile')) : '';

    return [
      'slideshow_profile' => $slideshow_profile ? $slideshow_profile : t('None, (default slideshow profile)'),
      'spin_profile'      => $spin_profile ? $spin_profile : t('None, (default spin profile)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $data = (object) [
      'slideshow'         => (count($items) > 1) ? 1 : 0,
      'slideshow_options' => $this->getSetting('slideshow_profile') ? SpinStorage::getProfile($this->getSetting('slideshow_profile')) : SpinStorage::getDefaultProfile('slideshow'),
      'spin_query'        => $this->getSetting('spin_profile') ? SpinStorage::getProfile($this->getSetting('spin_profile')) : SpinStorage::getDefaultProfile('spin'),
      'spins'             => [],
    ];
    foreach ($items as $obj) {
      if (empty($obj->fid) && empty($obj->spin)) {
        continue;
      }
      $file = !empty($obj->fid) ? File::load($obj->fid) : FALSE;
      $uri = $file ? $file->getFileUri() : '';

      $data->spins[] = [
        'img'  => $uri ? file_create_url($uri) : '',
        'nav'  => $uri ? ImageStyle::load('thumbnail')->buildUrl($uri) : '',
        'spin' => strpos($obj->spin, '?') ? check_url($obj->spin) : check_url($obj->spin) . "?$data->spin_query",
      ];
    }
    return [
      '#type'     => 'markup',
      '#attached' => ['library' => ['spin/spin']],
      '#theme'    => 'spin_slideshow',
      '#data'     => $data,
    ];
  }

}
