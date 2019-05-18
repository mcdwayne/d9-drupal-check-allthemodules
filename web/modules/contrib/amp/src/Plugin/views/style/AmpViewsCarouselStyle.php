<?php
namespace Drupal\amp\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amp\AmpFormTrait;

/**
 * Style plugin for the carousel view.
 *
 * @ViewsStyle(
 *   id = "amp_views_carousel",
 *   title = @Translation("AMP Views Carousel"),
 *   help = @Translation("Displays content in an AMP carousel."),
 *   theme = "amp_views_carousel",
 *   display_types = {"normal"}
 * )
 */
class AmpViewsCarouselStyle extends StylePluginBase {

  use AmpFormTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * Emulates formatter getSetting() method.
   *
   * Added so AmpFormTrait will work in both Field Formatters and also in
   * Views style plugins, for easier code re-use.
   *
   * @param string $setting
   *   The setting to retrieve.
   *
   * @return mixed
   *    The value of the setting.
   */
  public function getSetting($setting) {
    return $this->options[$setting];
  }

  /**
   * AMP layouts
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   Array of layout options allowed by this component.
   */
  private function getLayouts() {
    $options = $this->allLayouts();
    unset($options['container']);
    unset($options['intrinsic']);
    return $options;
  }

  /**
   * AMP libraries
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   The names of the AMP libraries used by this formatter.
   */
  public function getLibraries() {
    return ['amp/amp.carousel'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['wrapper_class'] = ['default' => ''];
    $options['type'] = array('default' => 'slides');
    $options['layout'] = array('default' => 'responsive');
    $options['width'] = array('default' => '');
    $options['height'] = array('default' => '');
    $options['autoplay'] = array('default' => FALSE);
    $options['controls'] = array('default' => FALSE);
    $options['loop'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $form['wrapper_class'] = [
      '#title' => $this->t('Wrapper class'),
      '#description' => $this->t('The class to provide on the wrapper, outside the carousel.'),
      '#type' => 'textfield',
      '#size' => '30',
      '#default_value' => $this->options['wrapper_class'],
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => t('Carousel type'),
      '#default_value' => $this->getSetting('type'),
      '#options' => [
        'carousel' => 'carousel',
        'slides' => 'slides',
      ],
    ];

    $form['layout'] = $this->layoutElement();
    $form['layout']['#description'] .= ' ' . $this->t('The "carousel" type only supports the fixed, fixed-height, and nodisplay layouts. The "slides" type supports the fill, fixed, fixed-height, flex-item, nodisplay, and responsive layouts.');
    $form['width'] = $this->widthElement();
    $form['height'] = $this->heightElement();
    $form['autoplay'] = $this->autoplayElement();;
    $form['controls'] = $this->controlsElement();;
    $form['loop'] = $this->loopElement();;

    $form['#prefix'] = '<div class="description">' . $this->libraryDescription() . '</div>';

  }

}

