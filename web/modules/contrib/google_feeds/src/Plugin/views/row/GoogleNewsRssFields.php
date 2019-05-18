<?php

namespace Drupal\google_feeds\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * View row plugin to render a Google News RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "google_news_rss_fields",
 *   title = @Translation("Google News Fields"),
 *   help = @Translation("Custom RSS items for Google news."),
 *   theme = "views_view_row_rss_google_news_feed",
 *   display_types = {"feed"}
 * )
 */
class GoogleNewsRssFields extends RssFields {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  protected $distinctValues = [];

  /**
   * Define the available options.
   *
   * @return array
   *   The array with options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['name_field'] = ['default' => ''];
    $options['publication_date_field'] = ['default' => ''];
    $options['title_field'] = ['default' => ''];
    $options['genre_field'] = ['default' => ''];
    $options['keywords'] = ['default' => ''];
    $options['stock_tickers'] = ['default' => ''];
    return $options;
  }

  /**
   * Options form for Google News rss feed.
   *
   * @param array $form
   *   The form to build.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state and values.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove the fields that are not needed for a Google News sitemap.
    unset(
      $form['title_field'],
      $form['description_field'],
      $form['creator_field'],
      $form['date_field'],
      $form['guid_field_options']
    );

    // Set the initial labels for the form fields.
    $initial_labels = ['' => $this->t('- None -')];
    $view_fields_labels = $this->displayHandler->getFieldLabels();
    $view_fields_labels = array_merge($initial_labels, $view_fields_labels);

    $form['link_field']['#description'] = $this->t('Absolute URL to node.');
    $form['name_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name field'),
      '#description' => $this->t('The name of the news publication. It must exactly match the name as it appears on your articles in news.google.com, omitting any trailing parentheticals.'),
      '#default_value' => $this->options['name_field'],
      '#required' => TRUE,
    ];
    $form['publication_date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication date field'),
      '#description' => $this->t('The field that contains the publication date of the node, allowed formats are: YYYY-MM-DD, YYYY-MM-DDThh:mmTZD, YYYY-MM-DDThh:mm:ssTZD, YYYY-MM-DDThh:mm:ss.sTZD.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['publication_date_field'],
      '#required' => TRUE,
    ];
    $form['title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#description' => $this->t('The field that contains the title of the node.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['title_field'],
      '#required' => TRUE,
    ];
    $form['genre_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Genre(s) field'),
      '#description' => $this->t('The "field_goo" field that was included with this module.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['genre_field'],
    ];
    $form['keywords'] = [
      '#type' => 'select',
      '#title' => $this->t('Keywords field'),
      '#description' => $this->t('Keywords/tags regarding the node in a comma separated list (commonly an entity reference field WITHOUT links).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['keywords'],
    ];
    $form['stock_tickers'] = [
      '#type' => 'select',
      '#title' => $this->t('Stock tickers field'),
      '#description' => $this->t('A maximum of five (5) stock tickers in a comma separated list that matches its entry in Google Finance (commonly an entity reference field WITHOUT links).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['stock_tickers'],
    ];
  }

  /**
   * Validate the Google News RSS settings.
   *
   * @return array
   *   Array with errors, if any.
   */
  public function validate() {
    $errors = [];
    // Only title, name and date are mandatory.
    $required_options = [
      'title_field',
      'name_field',
      'publication_date_field',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Not all required fields were filled in (Google News RSS fields).');
        break;
      }
    }
    return $errors;
  }

  /**
   * Render the RSS feed.
   *
   * @param object $row
   *   Current row to render.
   *
   * @return array
   *   Render array.
   */
  public function render($row) {
    /**
     * See:
     * https://support.google.com/news/publisher/answer/74288?hl=en
     * For required format of sitemap/feed for Google News.
     */

    static $row_index;

    // Reset the row index to zero if it has not been set.
    if (!isset($row_index)) {
      $row_index = 0;
    }

    // Create the RSS item object.
    $item = new \stdClass();

    // Add the required elements from the current row.
    $item->elements = [
      [
        'key' => 'link',
        'value' => $this->getField($row_index, $this->options['link_field']),
      ],

      [
        'key' => 'news:publication',
        'value' => '',
        'subitems' => [
          [
            'key' => 'news:name',
            'value' => $this->options['name_field'],
          ],
          [
            'key' => 'news:language',
            'value' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
          ],
        ],
      ],

      [
        'key' => 'news:publication_date',
        'value' => $this->getField($row_index, $this->options['publication_date_field']),
      ],

      [
        'key' => 'news:title',
        'value' => $this->getField($row_index, $this->options['title_field']),
      ],
    ];

    // For the non-required fields, first check if they exist and then add them
    // to the elements as well.
    if ($this->options['genre_field'] !== FALSE) {
      $genres = $this->getField($row_index, $this->options['genre_field']);

      // Also check if it's not empty, to prevent empty elements in the
      // sitemap.
      if (strlen($genres) > 0) {
        $item->elements[] = [
          'key' => 'news:genres',
          'value' => $genres,
        ];
      }
    }

    if ($this->options['keywords'] !== FALSE) {
      $keywords = $this->getField($row_index, $this->options['keywords']);

      if (strlen($keywords) > 0) {
        $item->elements[] = [
          'key' => 'news:keywords',
          'value' => $keywords,
        ];
      }
    }

    if ($this->options['stock_tickers'] !== FALSE) {
      $stockTickers = $this->getField($row_index, $this->options['stock_tickers']);

      if (strlen($stockTickers) > 0) {
        $item->elements[] = [
          'key' => 'news:stock_tickers',
          'value' => $stockTickers,
        ];
      }
    }

    // Increase the row index by one after each row.
    $row_index++;

    // Add the required namespaces.
    $this->view->style_plugin->namespaces = [
      'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
      'xmlns:news' => 'http://www.google.com/schemas/sitemap-news/0.9',
    ];

    // Create the build array and return it.
    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];
    return $build;

  }

}
