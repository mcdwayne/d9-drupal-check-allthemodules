<?php

namespace Drupal\drupal_planet_feeds_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a 'DrupalPlanetFeedsBlock' block.
 *
 * @Block(
 *  id = "drupal_planet_feeds_block",
 *  admin_label = @Translation("Planet Drupal"),
 * )
 */
class DrupalPlanetFeedsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_feeds_to_display'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of feeds to display'),
      '#description' => $this->t('Enter the number of planets feeds to be displayed.'),
      '#default_value' => isset($this->configuration['number_of_feeds_to_display']) ? $this->configuration['number_of_feeds_to_display'] : '',
      '#min' => 1,
      '#max' => 30,
    ];
    $options = array_merge(['' => t(' - Select -')], \Drupal::config('views.settings')->get('field_rewrite_elements'));
    $form['html_tag_to_render'] = [
      '#type' => 'select',
      '#title' => t('Html tag to render'),
      '#options' => $options,
      '#default_value' => isset($this->configuration['html_tag_to_render']) ? $this->configuration['html_tag_to_render'] : '',
      '#description' => 'Select the html tag which you need to be rendered with.',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_feeds_to_display'] = $form_state->getValue('number_of_feeds_to_display');
    $this->configuration['html_tag_to_render'] = $form_state->getValue('html_tag_to_render');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $number = $this->configuration['number_of_feeds_to_display'];
    if (isset($number)) {
      $xml = simplexml_load_file('https://www.drupal.org/planet/rss.xml');
      $i = 0;
      foreach ($xml->channel->item as $entry) {
        $i = $i + 1;
        if ($i > $number) {
          continue;
        }
        $entry_array = (array) $entry;
        $url = Url::fromUri($entry_array['link']);
        $link = Link::fromTextAndUrl($entry_array['title'], $url)->toString();
        $start_tag = '<h4>';
        $end_tag = '</h4>';
        if (!empty($this->configuration['html_tag_to_render'])) {
          $start_tag = '<' . $this->configuration['html_tag_to_render'] . '>';
          $end_tag = '</' . $this->configuration['html_tag_to_render'] . '>';
        }
        $build['drupal_planet_feed' . $i] = [
          '#type' => 'markup',
          '#markup' => $start_tag . $link->getGeneratedLink() . $end_tag,
        ];
      }
    }
    $build['drupal_planet_feed_more'] = [
      '#type' => 'more_link',
      '#url' => Url::fromUri('https://www.drupal.org/planet'),
    ];

    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
