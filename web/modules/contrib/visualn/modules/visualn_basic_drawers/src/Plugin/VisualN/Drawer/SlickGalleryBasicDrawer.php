<?php

namespace Drupal\visualn_basic_drawers\Plugin\VisualN\Drawer;

use Drupal\visualn\Core\DrawerWithJsBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\ResourceInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a 'Slick Gallery' VisualN drawer.
 *
 * @ingroup drawer_plugins
 *
 * @VisualNDrawer(
 *  id = "visualn_slick_gallery_basic",
 *  label = @Translation("Slick Gallery Basic"),
 * )
 */
class SlickGalleryBasicDrawer extends DrawerWithJsBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Slick.js based slideshow gallery of images or HTML markup with basic (optional) controls');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'slide_content' => 'image_url',
      'controls_color' => '#42aaff',
      //'controls_color' => '#ffffff',
      'show_dots' => TRUE,
    ] + parent::defaultConfiguration();
 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // ajax wrapper id must be unique
    $ajax_wrapper_id = !empty($form['#array_parents'])
      ? implode('-', $form['#array_parents']) . '--slick-ajax-wrapper'
      : 'slick-ajax-wrapper';
    // @todo: check for other special chars
    // the "|" character is added to visualn_style list on visualn style config page
    $ajax_wrapper_id = str_replace('|', '-', $ajax_wrapper_id);

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
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      // limit_validation_errors is used only with buttons
      //'#limit_validation_errors' => [],
    ];
    $form['controls_color'] = [
      '#type' => 'color',
      '#title' => t('Controls color'),
      '#default_value' => $this->configuration['controls_color'],
      '#required' => TRUE,
    ];
    $form['show_dots'] = [
      '#type' => 'checkbox',
      '#title' => t('Show dots'),
      '#default_value' => $this->configuration['show_dots'],
    ];

    $form['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * @inheritdoc
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    // @todo: For now it is ok just to return an empty ajax command,
    //   its primary purpose is to trigger ajax options replacement
    //   for forms containing data keys subforms, see VisualNFormsHelper::replaceAjaxOptions()
    //   since no other changes are made to the config form itself.

    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    // slide_content radios add one additional level of array_parents hierarchy
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element;
    // ajax_container could be used for slide_content type specific settings, e.g. image style
    //return $element['ajax_container'];
  }

  /**
   * @inheritdoc
   */
  public function prepareJsConfig(array &$drawer_config) {
    $drawer_config['show_dots'] = $drawer_config['show_dots'] ? TRUE : FALSE;
  }

  /**
   * @inheritdoc
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource) {
    // Attach drawer config to js settings
    parent::prepareBuild($build, $vuid, $resource);
    // @todo: $resource = parent::prepareBuild($build, $vuid, $resource); (?)

    // Attach visualn style libraries
    $build['#attached']['library'][] = 'visualn_basic_drawers/slick-gallery-basic-drawer';
    $build['#prefix'] = isset($build['#prefix']) ? $build['#prefix'] : '';
    $build['#suffix'] = isset($build['#suffix']) ? $build['#suffix'] : '';
    $build['#prefix'] .= $build['#prefix'] . '<div class="visualn-slick-gallery-basic-drawer-wrapper">';
    $build['#suffix'] .= '</div>' . $build['#suffix'];

    return $resource;
  }

  /**
   * @inheritdoc
   */
  public function jsId() {
    return 'visualnSlickGalleryBasicDrawer';
  }

  /**
   * @inheritdoc
   */
  public function dataKeys() {
    // use data keys corresponding to selected type
    switch ($this->configuration['slide_content']) {
      case 'image_url':
        $data_keys = ['url'];
        break;
      case 'html':
        $data_keys = ['html'];
        break;
      default :
        $data_keys = [
          'url',
          'html',
        ];
    }

    return $data_keys;
  }

}
