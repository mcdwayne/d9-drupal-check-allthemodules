<?php

namespace Drupal\jquery_carousel\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in a grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jquery_carousel",
 *   title = @Translation("jQuery Carousel"),
 *   help = @Translation("Display the results as a jquery Carousel."),
 *   theme = "views_view_jquery_carousel",
 *   display_types = {"normal"}
 * )
 */
class JqueryCarousel extends StylePluginBase {

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
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $carousel_config_form = jquery_carousel_config_form();
    $form = array_merge($form, $carousel_config_form);
    foreach (array_keys($form) as $key) {
      if (isset($form[$key]) && is_array($form[$key]) && isset($this->options[$key])) {
        $form[$key]['#default_value'] = $this->options[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $selector = $form_state->getValue(['style_options', 'selector']);
    $error = _jquery_carousel_config_validate($selector);
    if ($error) {
      $form_state->setErrorByName('selector', t("Selector should not contain any special characters or spaces. Only special character allowed is '-'"));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['selector'] = ['default' => 'rs-carousel'];
    return $options;
  }

}
