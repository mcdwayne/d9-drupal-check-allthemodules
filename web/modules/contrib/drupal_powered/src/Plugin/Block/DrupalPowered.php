<?php

/**
 * @file
 * Contains \Drupal\drupal_powered\Plugin\Block\DrupalPowered.
 */

namespace Drupal\drupal_powered\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides a 'Powered by Drupal' block.
 *
 * @Block(
 *   id = "drupal_powered",
 *   admin_label = @Translation("Powered by Drupal (wordmark)")
 * )
 */
class DrupalPowered extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'website' => 'http://drupal.com/',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['website'] = [
      '#title' => $this->t("Link website"),
      '#type' => 'radios',
      '#options' => [
        'http://drupal.com/' => 'drupal.com',
        'https://www.drupal.org/' => 'drupal.org',
      ],
      '#default_value' => $this->configuration['website'],
      '#description' => $this->t("Most websites should link to drupal.com. Only websites related to web development should link to drupal.org."),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['website'] = $form_state->getValue('website');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = Url::fromUri('base:core/vendor/drupalnomad/drupal-wordmark-svg/wordmark.svgz');

    $img = [
      '#theme' => 'image',
      '#attributes' => [
        'src' => $url->toString(),
        'alt' => 'Drupal',
      ],
    ];
    $attributes = [
      'href' => $this->configuration['website'],
      'target' => '_blank',
    ];
    $link = [
      '#markup' => '<a' . new Attribute($attributes) . '>' . drupal_render($img) . '</a>',
      '#prefix' => '<h2>Powered by</h2>',
    ];
    $link['#attached']['library'][] = 'drupal_powered/drupal_powered';
    return $link;
  }

}
