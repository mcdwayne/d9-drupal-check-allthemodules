<?php

namespace Drupal\podcast\Plugin\views\row;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\podcast\PodcastViewsMappingsTrait;
use Drupal\views\Plugin\views\row\RssFields as ViewsRssFields;

/**
 * Renders an RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "podcast_rss_fields",
 *   title = @Translation("Podcast Fields"),
 *   help = @Translation("Display fields as podcast RSS items."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RssFields extends ViewsRssFields {

  use PodcastViewsMappingsTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['enclosure_field_options']['contains']['enclosure_field_url'] = ['default' => ''];
    $options['enclosure_field_options']['contains']['enclosure_field_length'] = ['default' => ''];
    $options['enclosure_field_options']['contains']['enclosure_field_type'] = ['default' => ''];
    $options['itunes:author_field'] = ['default' => ''];
    $options['itunes:keywords_field'] = ['default' => ''];
    $options['itunes:explicit_field'] = ['default' => ''];
    $options['itunes:duration_field'] = ['default' => ''];
    $options['itunes:summary_field'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['link_field']['#description'] .= ' ' . $this->t('URL must contain a leading slash. Example: /foo/bar/baz.');
    $form['enclosure_field_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Audio file settings'),
      '#open' => TRUE,
    ];
    $form['enclosure_field_options']['enclosure_field_url'] = [
      '#type' => 'select',
      '#title' => $this->t('URL field'),
      '#description' => $this->t('The globally unique identifier of the RSS item.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['enclosure_field_options']['enclosure_field_url'],
      '#required' => TRUE,
    ];
    $form['enclosure_field_options']['enclosure_field_length'] = [
      '#type' => 'select',
      '#title' => $this->t('Length field'),
      '#description' => $this->t('The length of the episode.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['enclosure_field_options']['enclosure_field_length'],
    ];
    $form['enclosure_field_options']['enclosure_field_type'] = [
      '#type' => 'select',
      '#title' => $this->t('MIME type field'),
      '#description' => $this->t('The MIME for the audio file.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['enclosure_field_options']['enclosure_field_type'],
    ];
    $form['itunes:author_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Author field'),
      '#description' => $this->t('Authors of the podcast for iTunes meta.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:author_field'],
    ];
    $form['itunes:keywords_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Keywords field'),
      '#description' => $this->t('Keywords to display in iTunes.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:keywords_field'],
    ];
    $form['itunes:explicit_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Explicit field'),
      '#description' => $this->t('Mark the episode as being explicit or not. Format: "true" / "false".'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:explicit_field'],
    ];
    $form['itunes:duration_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Duration field'),
      '#description' => $this->t('iTunes Episode duration'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:duration_field'],
    ];
    $form['itunes:summary_field'] = [
      '#type' => 'select',
      '#title' => $this->t('iTunes Summary field'),
      '#description' => $this->t('A brief summary of the episode to display in iTunes.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['itunes:summary_field'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    $build = parent::render($row);
    $item = $build['#row'];
    $description = $this->buildElementFromOptions('description', $row_index);
    $item->description = NestedArray::getValue($description, ['value']);

    $keyval_url = $this->buildElementForLink(NULL, $row_index, [
      'enclosure_field_options',
      'enclosure_field_url',
    ]);
    $keyval_length = $this->buildElementFromOptions(NULL, $row_index, [
      'enclosure_field_options',
      'enclosure_field_length',
    ]);
    $keyval_type = $this->buildElementFromOptions(NULL, $row_index, [
      'enclosure_field_options',
      'enclosure_field_type',
    ]);
    $item->elements[] = [
      'key' => 'enclosure',
      'attributes' => [
        'url' => NestedArray::getValue($keyval_url, ['value']),
        'length' => NestedArray::getValue($keyval_length, ['value']),
        'type' => NestedArray::getValue($keyval_type, ['value']),
      ],
    ];
    $link_keys = ['link'];
    $item->elements = array_reduce(
      $link_keys,
      function ($elements, $key) use ($row_index) {
        return array_merge(
          $elements,
          [$this->buildElementForLink($key, $row_index)]
        );
      },
      $item->elements
    );
    $keys = [
      'description',
      'itunes:author',
      'itunes:keywords',
      'itunes:explicit',
      'itunes:duration',
      'itunes:summary',
    ];
    $item->elements = array_reduce(
      $keys,
      function ($elements, $key) use ($row_index) {
        return array_merge(
          $elements,
          [$this->buildElementFromOptions($key, $row_index)]
        );
      },
      $item->elements
    );
    $item->elements = array_filter($item->elements);
    $build['#row'] = $item;

    $row_index++;
    return $build;
  }

}
