<?php

namespace Drupal\jquery_view_ticker\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a option of tricker
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jquery_view_ticker",
 *   title = @Translation("jQuery View Ticker"),
 *   help = @Translation(""),
 *   theme = "views_view_jquery_view_ticker",
 *   display_types = { "normal" }
 * )
 */
class jQueryViewTicker extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['random'] = array(
      '#type' => 'select',
      '#title' => t('Random'),
      '#options' => array(0 => 'False', 1 => 'True'),
      '#default_value' => (isset($this->options['random'])) ? $this->options['random'] : 0,
      '#description' => t('To display ticker items in a random order or not.'),
    );
    
    $form['itemspeed'] = array(
      '#type' => 'number',
      '#title' => t('Item Speed'),
      '#default_value' => (isset($this->options['itemspeed'])) ? $this->options['itemspeed'] : 3000,
      '#description' => t('The pause on each ticker item before being replaced'),
    );

    $form['cursorspeed'] = array(
      '#type' => 'number',
      '#title' => t('Cursor Speed'),
      '#default_value' => (isset($this->options['cursorspeed'])) ? $this->options['cursorspeed'] : 50,
      '#description' => t('Speed at which the characters are typed.'),
    );
    
    $form['pauseonhover'] = array(
      '#type' => 'select',
      '#title' => t('Pause On Hover'),
      '#options' => array(0 => 'False', 1 => 'True'),
      '#default_value' => (isset($this->options['pauseonhover'])) ? $this->options['pauseonhover'] : 0,
      '#description' => t('Whether to pause when the mouse hovers over the ticker.'),
    );
    
    $form['finishonhover'] = array(
      '#type' => 'select',
      '#title' => t('Finish On Hover'),
      '#options' => array(0 => 'False', 1 => 'True'),
      '#default_value' => (isset($this->options['finishonhover'])) ? $this->options['finishonhover'] : 0,
      '#description' => t('Whether or not to complete the ticker item instantly when moused over.'),
    );
    
    $form['fadeeffect'] = array(
      '#type' => 'select',
      '#title' => t('Fade Effect'),
      '#options' => array(0 => 'False', 1 => 'True'),
      '#default_value' => (isset($this->options['fadeeffect'])) ? $this->options['fadeeffect'] : 1,
      '#description' => t('To fade between ticker items or not.'),
    );
    
    $form['fadeinspeed'] = array(
      '#type' => 'number',
      '#title' => t('Fade In Speed'),
      '#default_value' => (isset($this->options['fadeinspeed'])) ? $this->options['fadeinspeed'] : 600,
      '#description' => t('Speed of the fade-in animation'),
    );

    $form['fadeoutspeed'] = array(
      '#type' => 'number',
      '#title' => t('Fade Out Speed'),
      '#default_value' => (isset($this->options['fadeoutspeed'])) ? $this->options['fadeoutspeed'] : 300,
      '#description' => t('Speed of the fade-out animation'),
    );
    
  }
}
