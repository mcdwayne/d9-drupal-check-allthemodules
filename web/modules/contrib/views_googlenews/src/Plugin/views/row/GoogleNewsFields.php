<?php

namespace Drupal\views_googlenews\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Renders an GoogleNews item based on fields.
 *
 * @ViewsRow(
 *   id = "google_news_fields",
 *   title = @Translation("Google News fields"),
 *   help = @Translation("Display fields as Google News items."),
 *   theme = "views_view_row_googlenews",
 *   display_types = {"feed"}
 * )
 */
class GoogleNewsFields extends RowPluginBase {

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['loc_field'] = ['default' => ''];
    $options['news_publication_name_field'] = ['default' => ''];
    $options['news_publication_language_field'] = ['default' => ''];
    $options['news_access_field'] = ['default' => ''];
    $options['news_genres_field'] = ['default' => ''];
    $options['news_publication_date_field'] = ['default' => ''];
    $options['news_title_field'] = ['default' => ''];
    $options['news_keywords_field'] = ['default' => ''];
    $options['news_stock_tickers_field'] = ['default' => ''];
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

    $form['loc_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#description' => $this->t('The URL to the news (&lt;loc&gt;).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['loc_field'],
      '#required' => TRUE,
    ];
    $form['news_publication_name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The name of the publication (&lt;news:name&gt;), defaults to the site name.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_publication_name_field'],
    ];
    $form['news_publication_language_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Language code'),
      '#description' => $this->t('The language code (&lt;news:language&gt;). Must be a language code, will use the default language if not provided.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_publication_language_field'],
    ];
    $form['news_access_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Access'),
      '#description' => $this->t('Access information (&lt;news:access&gt;), must be Subscription, Registration or an empty string if access is not restricted.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_access_field'],
    ];
    $form['news_genres_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Genres'),
      '#description' => $this->t('The field that is going to be used as the Google News &lt;news:genres&gt; attribute for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_genres_field'],
    ];
    $form['news_publication_date_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Publication date'),
      '#description' => $this->t('The publication date of the news (&lt;news:publication_date&gt;).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_publication_date_field'],
      '#required' => TRUE,
    ];
    $form['news_title_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The news title (&lt;news:title&gt;).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_title_field'],
      '#required' => TRUE,
    ];
    $form['news_keywords_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Keywords'),
      '#description' => $this->t('Keywords or tags for this news (Google News &lt;news:keywords&gt;).'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_keywords_field'],
    ];
    $form['news_stock_tickers_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Stock tickers'),
      '#description' => $this->t('Stock ticker references (&lt;news:stock_tickers&gt;)'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['news_stock_tickers_field'],
    ];

    $form['documentation'] = [
      '#type' => 'item',
      '#title' => $this->t('Documentation'),
      '#markup' => $this->t('See <a href="@url">the Google News Sitemap reference</a> for more information', ['@url' => 'https://support.google.com/news/publisher/answer/74288?hl=en#tagdefinitions']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    // @todo Add validation.
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    // Create the Google News item array.
    $item = [];
    $row_index = $this->view->row_index;
    $item['loc'] = $this->getField($row_index, $this->options['loc_field']);
    $item['news_publication_name'] = $this->getField($row_index, $this->options['news_publication_name_field']);
    $item['news_publication_language'] = $this->getField($row_index, $this->options['news_publication_language_field']);
    if ($this->options['news_access_field']) {
      $item['news_access'] = $this->getField($row_index, $this->options['news_access_field']);
    }
    if ($this->options['news_genres_field']) {
      $item['news_genres'] = $this->getField($row_index, $this->options['news_genres_field']);
    }
    $item['news_publication_date'] = $this->getField($row_index, $this->options['news_publication_date_field']);
    $item['news_title'] = $this->getField($row_index, $this->options['news_title_field']);
    if ($this->options['news_keywords_field']) {
      $item['news_keywords'] = $this->getField($row_index, $this->options['news_keywords_field']);
    }
    if ($this->options['news_stock_tickers_field']) {
      $item['news_stock_tickers'] = $this->getField($row_index, $this->options['news_stock_tickers_field']);
    }

    // Remove empty attributes.
    $item = array_filter($item);

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
      '#field_alias' => isset($this->field_alias) ? $this->field_alias : '',
    ];
    return $build;
  }

  /**
   * Retrieves a views field value from the style plugin.
   *
   * @param int $index
   *   The index count of the row as expected by views_plugin_style::getField().
   * @param int $field_id
   *   The ID assigned to the required field in the display.
   *
   * @return string
   *   The field value.
   */
  public function getField($index, $field_id) {
    if (empty($this->view->style_plugin) || !is_object($this->view->style_plugin) || empty($field_id)) {
      return '';
    }
    return $this->view->style_plugin->getField($index, $field_id);
  }

}
