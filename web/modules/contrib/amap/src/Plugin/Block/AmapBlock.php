<?php

namespace Drupal\amap\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'AmapBlock' block.
 *
 * @Block(
 *  id = "amap_block",
 *  admin_label = @Translation("aMap Block"),
 *  category = @Translation("aMap"),
 * )
 */
class AmapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['svg_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL for ajax SVG class/style'),
      '#description' => $this->t('The URL where we will obtain SVG IDs and class/style.'),
      '#default_value' => $config['svg_url'],
      '#maxlength' => 2000,
      '#size' => 128,
      '#weight' => '10',
    ];
    $form['svg_url_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Path Component Start for Parameters'),
      '#description' => $this->t('Starting at the number provided, all path(s) at that number and after will be passed over to AJAX URL. 0 (Default) will not pass anything. (e.g http://site/1/2/3/4/5)'),
      '#default_value' => isset($config['svg_url_path']) ? $config['svg_url_path'] : 0,
      '#maxlength' => 1,
      '#size' => 1,
      '#weight' => '20',
    ];
    $form['svg_eid_mn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SVG Element ID field / machine name'),
      '#description' => $this->t('The field machine name that will provide the SVG ID for Class/Style manipulation.'),
      '#default_value' => $config['svg_eid_mn'],
      '#maxlength' => 64,
      '#size' => 32,
      '#weight' => '30',
    ];
    $form['svg_eid_class_mn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Class containing field / machine name for SVG Element ID'),
        '#description' => $this->t('The field machine name containing a class'),
        '#default_value' => $config['svg_eid_class_mn'],
        '#maxlength' => 64,
        '#size' => 32,
        '#weight' => '40',
    ];
    $form['svg_eid_style_mn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Style containing field / machine name for SVG Element ID'),
        '#description' => $this->t('The field machine name containing a class'),
        '#default_value' => $config['svg_eid_style_mn'],
        '#maxlength' => 64,
        '#size' => 32,
        '#weight' => '50',
    ];
    $form['svg_eid_url_mn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field / machine name for SVG URL (click)'),
      '#description' => $this->t('Where you would like to take the user when an SVG item is clicked.'),
      '#default_value' => $config['svg_eid_url_mn'],
      '#maxlength' => 64,
      '#size' => 32,
      '#weight' => '60',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['svg_url'] = $form_state->getValue('svg_url');
    $this->configuration['svg_url_path'] = $form_state->getValue('svg_url_path');
    $this->configuration['svg_eid_mn'] = $form_state->getValue('svg_eid_mn');
    $this->configuration['svg_eid_class_mn'] = $form_state->getValue('svg_eid_class_mn');
    $this->configuration['svg_eid_style_mn'] = $form_state->getValue('svg_eid_style_mn');
    $this->configuration['svg_eid_url_mn'] = $form_state->getValue('svg_eid_url_mn');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;

    $build = [];
    $build['#theme'] = 'amap';

    $build['#attached'] = [
      'drupalSettings' => ['amap' => $config],
    ];
    return $build;
  }

}
