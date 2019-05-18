<?php

namespace Drupal\parallax_blur_bg\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a 'Parallax' block.
 *
 * @Block(
 *  id = "parallax_blur_bg_block",
 *  admin_label = @Translation("Parallax Blur Background"),
 * )
 */
class ParallaxBlurBg extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['upload_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('Upload an image'),
      '#default_value' => $this->configuration['upload_image'],
      '#weight' => '1',
      '#upload_location' => 'public://parallax_blur_bg',
    ];
    $form['tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class or ID'),
      '#description' => $this->t('Enter the css class or id which you want to cover up by the background image'),
      'weight' => '2',
      '#default_value' => $this->configuration['tags'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveImage($fid) {
    $file = File::load($fid);
    if ($file) {
      $file->setPermanent();
      $file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('upload_image')[0];
    $this->saveImage($fid);
    $this->configuration['upload_image'] = [$fid];
    $this->configuration['tags'] = $form_state->getValue('tags');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $config = $this->getConfiguration();
    $file = File::load($config['upload_image'][0]);
    if ($file) {
      $style = \Drupal::entityTypeManager()->getStorage('image_style');
      $small_bg_url = file_url_transform_relative($style->load('thumbnail')->buildUrl($file->getFileUri()));
      $large_bg_url = file_url_transform_relative($style->load('large')->buildUrl($file->getFileUri()));
      $tags = $config['tags'];
      static $i = 0;
      $build['#attached']['library'] = 'parallax_blur_bg/parallax_blur_bg';
      $build['#attached']['drupalSettings']['parallax_blur_bg']['tags'][$i] = $tags;
      $build['#attached']['drupalSettings']['parallax_blur_bg']['small_bg_url'][$i] = $small_bg_url;
      $build['#attached']['drupalSettings']['parallax_blur_bg']['large_bg_url'][$i] = $large_bg_url;
      $i++;
      return $build;
    }
    return [];
  }

}
