<?php

namespace Drupal\vegas\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\file\Entity\File;

class FormVegasConfiguration extends ConfigFormBase {

  public function getFormId() {
    return 'vegas_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vegas.settings',
    ];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('vegas.settings');

    // Set up the vertical tabs.
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 50,
    );

    // Set up the tabs.
    $form['configuration'] = array(
      '#type' => 'details',
      '#title' => t('Configuration'),
      '#description' => t('Provide general configuration for how the images are displayed.'),
      '#group' => 'settings',
    );

    $form['images'] = array(
      '#type' => 'details',
      '#title' => t('Images'),
      '#description' => t('Configure which images should be presented as background images.'),
      '#group' => 'settings',
    );

    $form['path'] = array(
      '#type' => 'details',
      '#title' => t('Path'),
      '#description' => t('Configure which paths should be used to display background images.'),
      '#group' => 'settings',
    );

    // Images
    $count = 10;
    for ($i = 0; $i < $count; $i++) {
      $image = array();
      $form['images']['vegas_images_' . $i] = array(
        '#type' => 'managed_file',
        '#default_value' => $config->get('vegas_images_' . $i),
        '#upload_location' => 'public://vegas/',
        '#upload_validators' => array(
          'file_validate_extensions' => array(
            0 => 'png jpg gif jpeg',
          ),
        ),
      );
    }

    // Overlay
    $form['configuration']['vegas_overlay'] = array(
      '#type' => 'managed_file',
      '#title' => t('Overlay'),
      '#description' => t('The overlay will be placed on top of the image to give it a neat effect.'),
      '#default_value' => $config->get('vegas_overlay'),
      '#upload_location' => 'public://vegas/',
      '#upload_validators' => array(
        'file_validate_extensions' => array(
          0 => 'png jpg gif jpeg',
        ),
      ),
    );

    // Fade
    $form['configuration']['vegas_fade'] = array(
      '#title' => t('Fade'),
      '#type' => 'select',
      '#description' => t('Transition time between slides.'),
      '#default_value' => $config->get('vegas_fade'),
      '#options' => array(
        0 => t('None'),
        500 => t('Half a second'),
        1000 => t('One second'),
        2000 => t('Two seconds'),
        3000 => t('Three seconds'),
        4000 => t('Four seconds'),
        5000 => t('Five seconds'),
      ),
    );

    // Delay
    $form['configuration']['vegas_delay'] = array(
      '#title' => t('Delay'),
      '#type' => 'select',
      '#description' => t('The time taken between two slides.'),
      '#default_value' => $config->get('vegas_delay'),
      '#options' => array(
        500 => t('Half a second'),
        1000 => t('One second'),
        2000 => t('Two seconds'),
        3000 => t('Three seconds'),
        4000 => t('Four seconds'),
        5000 => t('Five seconds'),
        6000 => t('Six seconds'),
        7000 => t('Seven seconds'),
        8000 => t('Eight seconds'),
        9000 => t('Nine seconds'),
        10000 => t('Ten seconds'),
        11000 => t('Eleven seconds'),
        12000 => t('Twelve seconds'),
        13000 => t('Thirteen seconds'),
        14000 => t('Fourteen seconds'),
        15000 => t('Fifteen seconds'),
        16000 => t('Sixteen seconds'),
        17000 => t('Seventeen seconds'),
        18000 => t('Eighteen seconds'),
        19000 => t('Nineteen seconds'),
        20000 => t('Twenty seconds'),
      ),
    );

    // Shuffle
    $form['configuration']['vegas_shuffle'] = array(
      '#type' => 'checkbox',
      '#title' => t('Shuffle'),
      '#description' => t('Randomize the order of the images.'),
      '#default_value' => $config->get('vegas_shuffle'),
    );

    $form['path']['vegas_patterns'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => $config->get('vegas_patterns'),
      '#description' => $this->t('New line separated paths that must start with a leading slash. Wildcard character is *. E.g. /comment/*/reply.'),
    );

    //$form['path']['skip_admin_paths'] = array(
    //  '#title' => $this->t('Skip all admin paths'),
    //  '#type' => 'checkbox',
    //  '#default_value' => $config->get('skip_admin_paths'),
    //  '#description' => $this->t('This will exclude all admin paths from Vegas.'),
    //);

    return parent::buildForm($form, $form_state);

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

    $config = \Drupal::service('config.factory')->getEditable('vegas.settings');

    $config->set('vegas_fade', $form_state->getValue('vegas_fade'));
    $config->set('vegas_delay', $form_state->getValue('vegas_delay'));
    $config->set('vegas_shuffle', $form_state->getValue('vegas_shuffle'));
    $config->set('vegas_patterns', $form_state->getValue('vegas_patterns'));
    //$config->set('skip_admin_paths', $form_state->getValue('skip_admin_paths'));

    //overlay
    $overlay = $form_state->getValue('vegas_overlay');
    if (!empty($overlay)) {
      $this->saveImage($overlay[0]);
      $config->set('vegas_overlay', $overlay);
    }
    else {
      $config->set('vegas_overlay', FALSE);
    }

    //single background images
    $count = 10;
    for ($i = 0; $i < $count; $i++) {

      $image = $form_state->getValue('vegas_images_' . $i);

      if (!empty($image)) {
        $this->saveImage($image[0]);
        $config->set('vegas_images_' . $i, $image);
      }
      else {
        $config->set('vegas_images_' . $i, FALSE);
      }
    }

    $config->save();

  }

  public function saveImage($fid) {

    // call file usage service and load file
    $file_usage = \Drupal::service('file.usage');
    $file = File::load( $fid );

    if ($file) {

      // set file status permanent
      if(!$file->isPermanent()){
        $file->setPermanent();
      }

      // check file usage , if it's empty, add new entry
      $usage = $file_usage->listUsage($file);

      if(empty($usage)){
        // let's assume it's image
        $file_usage->add($file,'vegas','image',$fid);
      }

      $file->save();
    }
  }
}
