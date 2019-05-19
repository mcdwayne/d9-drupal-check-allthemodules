<?php

namespace Drupal\uikit_slideshow\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a Uikit Slideshow.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "uikit_slideshow",
 *   title = @Translation("Uikit Slideshow"),
 *   help = @Translation("Render a slideshow based on uikit"),
 *   theme = "views_view_uikit_slideshow",
 *   display_types = { "normal" }
 * )
 */
class UikitSlideshow extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['slide_image'] = ['default' => ''];
    $options['slide_title'] = ['default' => ''];
    $options['slide_body'] = ['default' => ''];
    $options['node_link'] = ['default' => ''];
    $options['thumbnail'] = ['default' => ''];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $this->displayHandler->getFieldLabels(TRUE);
    $form['slide_image'] = [
      '#title' => $this->t('The slide image field'),
      '#description' => $this->t('Select the field that will be used as slide image.'),
      '#type' => 'select',
      '#default_value' => $this->options['slide_image'],
      '#options' => $options,
    ];
    $form['slide_title'] = [
      '#title' => $this->t('The slide title field'),
      '#description' => $this->t('Select the field that will be used as slide title.'),
      '#type' => 'select',
      '#default_value' => $this->options['slide_title'],
      '#options' => $options,
    ];
    $form['slide_body'] = [
      '#title' => $this->t('The slide body field'),
      '#description' => $this->t('Select the field that will be used as slide body.'),
      '#type' => 'select',
      '#default_value' => $this->options['slide_body'],
      '#options' => $options,
    ];
    $form['node_link'] = [
      '#title' => $this->t('The node link field'),
      '#description' => $this->t('Select the field that will be used as link to node.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_link'],
      '#options' => $options,
    ];
    $form['thumbnail'] = [
      '#title' => $this->t('The thumbnail field'),
      '#description' => $this->t('Select the field that will be used as navigation thumbnail.'),
      '#type' => 'select',
      '#default_value' => $this->options['thumbnail'],
      '#options' => $options,
    ];
  }

}
