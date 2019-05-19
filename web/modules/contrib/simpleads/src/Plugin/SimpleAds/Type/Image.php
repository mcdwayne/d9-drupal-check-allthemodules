<?php

namespace Drupal\simpleads\Plugin\SimpleAds\Type;

use Drupal\simpleads\SimpleAdsTypeBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\simpleads\Ads;

/**
 * Image Ad type.
 *
 * @SimpleAdsType(
 *   id = "image",
 *   name = @Translation("Image Ad")
 * )
 */
class Image extends SimpleAdsTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $ad = (new Ads())->setId($id)->load();
    $options = $ad->getOptions(TRUE);
    $form['image'] = [
      '#title'             => $this->t('Advertisement Creative'),
      '#type'              => 'managed_file',
      '#description'       => $this->t('Please upload advertisement image. Allowed extensions: gif png jpg jpeg'),
      '#upload_location'   => 'public://simpleads/image/',
      '#required'          => TRUE,
      '#multiple'          => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg'],
      ],
      '#default_value'     => !empty($options['fid']) ? [$options['fid']] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL) {
    if ($fid = reset($form_state->getValue('image'))) {
      $options['fid'] = $this->saveImage($fid);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    if ($fid = reset($form_state->getValue('image'))) {
      if ($options['fid'] != $fid) {
        $options['fid'] = $this->saveImage($fid);
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    if (!empty($options['fid'])) {
      $this->deleteImage($options['fid']);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function theme() {
    return [
      'image.simpleads' => [
        'variables' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

  }

  /**
   * Save image as a file entity.
   */
  protected function saveImage($fid) {
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'simpleads', 'file', $fid);
    return $fid;
  }

  /**
   * Delete file entity.
   */
  protected function deleteImage($fid) {
    $file = File::load($fid);
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'simpleads', 'file', $fid);
    $file->delete();
    return $fid;
  }

}
