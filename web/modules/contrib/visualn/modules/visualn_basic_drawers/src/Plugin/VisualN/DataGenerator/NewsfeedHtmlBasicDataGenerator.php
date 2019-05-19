<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\DataGenerator;

use Drupal\visualn\Core\DataGeneratorBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an 'News Feed Html Basic' VisualN data generator.
 *
 * @ingroup data_generator_plugins
 *
 * @VisualNDataGenerator(
 *  id = "visualn_newsfeed_html_basic",
 *  label = @Translation("News Feed Html Basic"),
 *  compatible_drawers = {
 *    "visualn_newsfeed_html_basic"
 *  }
 * )
 */
class NewsfeedHtmlBasicDataGenerator extends DataGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number' => '5',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['number'] = [
      '#type' => 'number',
      '#title' => t('Number of items'),
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

    // @todo: then add module into dependencies
    //   or check if it is enabled
    $original_path = \Drupal::config('image.settings')->get('preview_image');
    for ($i = 0; $i < $this->configuration['number']; $i++) {
      $n = $i + 1;
      $data[] = [
        // @todo: check replacement pattern here
        'title' => t("News item #:n", [':n' => $n]),
        'link' => 'http://example.com',
        'image_url' => '/' . $original_path,
        // @todo: use text generator as the one used for Table drawer
        //   also generator could generate text considering current user language
        'text' => 'here goes some text',
      ];
    }

    return $data;
  }
}
