<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;

/**
 * Provides a 'News Feed Html Basic' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_newsfeed_html_basic",
 *  label = @Translation("News Feed Html Basic"),
 *  input = "generic_data_array",
 * )
 */
class NewsfeedHtmlBasicDrawer extends DrawerBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('List of news feed items with image, link title and short text on each line');
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    $default_config = [
      'header_title' => '',
    ];
    return $default_config;
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo: maybe use a different name, e.g. Tile of Newsfeed title
    $form['header_title'] = [
      '#type' => 'textfield',
      '#title' => t('Header title'),
      '#default_value' => $this->configuration['header_title'],
    ];

    // @todo: other possible settings: image size, open in a new tab

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {

    // @todo: consider translating header title (though can be used in translatable fields)
    $data = $resource->data ?: [];
    $build['newsfeed_content'] = [
      '#theme' => 'visualn_newsfeed_html_basic_drawer',
      '#header_title' => $this->configuration['header_title'],
      '#data' => $data,
    ];

    $build['#attached']['library'][] = 'visualn_basic_drawers/newsfeed-html-basic-drawer';

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    $data_keys = [
      'title',
      'link',
      'image_url',
      'text',
    ];

    return $data_keys;
  }

}
