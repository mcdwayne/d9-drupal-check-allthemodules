<?php

namespace Drupal\get_node_img\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Settings form for selecting a default image.
 *
 * This default image will be served in place of an otherwise 404 response.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'get_node_img_404_selection';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return ['get_node_img.settings'];
  }

  /**
   * Form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('get_node_img.settings');
    $form = parent::buildForm($form, $form_state);

    $form['404_img'] = [
      '#type' => 'fieldset',
      '#title' => '404 Image',
    ];

    $form['404_img']['404_img_file'] = [
      '#type' => 'managed_file',
      '#title' => 'Image Selection',
      '#description'     => 'If a new image is chosen, the current image will be replaced upon submitting the form.',
      '#upload_location' => 'public://images',
      '#weight' => 9,
    ];

    // Do we already have a 404 image?
    $img_404_fid = $config->get('404_img');

    if ($file_obj = File::load($img_404_fid)) {
      $form['404_img']['current_404_img'] = [
        '#type'        => 'item',
        '#title'       => 'Current 404 Image',
        '#description' => [
          '#theme'      => 'image',
          '#uri'        => $file_obj->url(),
          '#alt'        => t('404 image'),
          '#title'      => t('404 image'),
          '#attributes' => [],
        ],
        '#weight'      => 3,
      ];

      $form['404_img']['404_img_rm'] = [
        '#type' => 'checkbox',
        '#title' => 'Delete image',
        '#return_value' => $img_404_fid,
        '#description' => 'Check this box and then submit the form to delete
                             the current 404 image.',
        '#weight' => 6,
      ];
    }

    return $form;
  }

  /**
   * Form submit handler.
   *
   * Either save the file ID of the selected image or delete a previously
   * saved image.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $is_delete_img = $form_state->getValue('404_img_rm', FALSE);
    $img_fid       = $form_state->getValue(['404_img_file', 0]);

    if ($is_delete_img) {
      $img_fid = $is_delete_img;
      $this->deleteDefaultImg($img_fid);
    }
    elseif ($img_fid) {
      $this->saveDefaultImg($img_fid);
    }

    return parent::submitForm($form, $form_state);
  }

  /**
   * Delete default image and remove its reference from configuration.
   *
   * @param int $img_fid
   *   Image file ID.
   */
  protected function deleteDefaultImg($img_fid) {

    $img = File::load($img_fid);

    try {
      $img->delete();

      $config = $this->config('get_node_img.settings');
      $config->set('404_img', '');
      $config->save();

      drupal_set_message('404 image file has been removed.');
    }
    catch (Exception $e) {
      drupal_set_message('Failed to remove 404 image file.');
    }
  }

  /**
   * Process newly uploaded default image.
   *
   * Mark it as permanent.  If successful, update module configuration variable.
   * Ignore files that are already permanent.  These have been processed
   * already.
   *
   * @param int $img_fid
   *   Image file ID.
   */
  protected function saveDefaultImg($img_fid) {

    $img               = File::load($img_fid);
    $is_permanent_file = $img->isPermanent();

    if ($is_permanent_file) {
      return;
    }

    // Newly uploaded files are treated as temporary files until they are
    // explicitely marked as permanent.
    $img->setPermanent();

    if (SAVED_UPDATED === $img->save()) {
      $config= $this->config('get_node_img.settings');
      $config->set('404_img', $img_fid);
      $config->save();

      drupal_set_message('Default image file has been saved.');
    }
    else {
      drupal_set_message('Failed to save default image.', 'error');
    }
  }

}
