<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Core\DataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'Slick Gallery Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_slick_gallery_basic",
 *  label = @Translation("Slick Gallery Basic"),
 *  compatible_drawers = {
 *    "visualn_slick_gallery_basic"
 *  }
 * )
 */
class SlickGalleryBasicDataGenerator extends DataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'slide_content' => 'image_url',
      'number' => '5',
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: drawer could dynamically define content by data key
    //   though it could potentially break or not work with keys mapping
    $options = [
      'image_url' => t('Image URL'),
      'html' => t('HTML markup'),
      // @todo: what about svg ?
    ];
    $form['slide_content'] = [
      '#type' => 'radios',
      '#title' => t('Slide content'),
      '#options' => $options,
      '#default_value' => $this->configuration['slide_content'],
      '#required' => TRUE,
    ];
    $form['number'] = [
      '#type' => 'number',
      '#title' => t('Number of slides'),
      '#default_value' => $this->configuration['number'],
      '#min' => 1,
      '#max' => 15,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateData() {
    $data = [];

    switch ($this->configuration['slide_content']) {
      case 'image_url':
        // @todo: then add module into dependencies
        //   or check if it is enabled
        $original_path = \Drupal::config('image.settings')->get('preview_image');
        // @todo: use some kind of images provider service (that could be also altered by other modules)
        //   to get nice images, of different types and dimensions
        //   the service could be also used by other generators and modules

        for ($i = 0; $i < $this->configuration['number']; $i++) {
          $data[] = ['url' => '/' . $original_path];
        }
        break;
      case 'html':
        // @todo: generate some better content
        for ($i = 0; $i < $this->configuration['number']; $i++) {
          $n = $i+1;
          $data[] = ['html' => "Slide {$n} content"];
        }
        break;
    }


    return $data;
  }

}
