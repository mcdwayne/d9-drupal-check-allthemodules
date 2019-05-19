<?php

namespace Drupal\views_flipboard\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * Renders an Flipboard RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "flipboard_rss_fields",
 *   title = @Translation("Flipboard RSS fields"),
 *   help = @Translation("Display fields as Flipboard RSS items."),
 *   theme = "views_view_row_flipboard_rss",
 *   display_types = {"feed"}
 * )
 */
class FlipboardRssFields extends RssFields {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['enclosure_field'] = ['default' => ''];
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

    $form['enclosure_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Enclosure field'),
      '#description' => $this->t('The field that is going to be used as the RSS item media for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['enclosure_field'],
      '#required' => TRUE,
    ];
    $form['category_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Category field'),
      '#description' => $this->t('The field that is going to be used as the RSS item category for each row.'),
      '#options' => $view_fields_labels,
      '#default_value' => $this->options['category_field'],
      '#required' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $required_options = [
      'title_field',
      'link_field',
      'description_field',
      'creator_field',
      'date_field',
      'enclosure_field',
    ];
    foreach ($required_options as $required_option) {
      if (empty($this->options[$required_option])) {
        $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
        break;
      }
    }
    // Once more for guid.
    if (empty($this->options['guid_field_options']['guid_field'])) {
      $errors[] = $this->t('Row style plugin requires specifying which views fields to use for RSS item.');
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    static $row_index;
    if (!isset($row_index)) {
      $row_index = 0;
    }
    if (function_exists('rdf_get_namespaces')) {
      // Merge RDF namespaces in the XML namespaces in case they are used
      // further in the RSS content.
      $xml_rdf_namespaces = [];
      foreach (rdf_get_namespaces() as $prefix => $uri) {
        $xml_rdf_namespaces['xmlns:' . $prefix] = $uri;
      }
    }

    // Create the RSS item object.
    $item = new \stdClass();
    $item->title = $this->getField($row_index, $this->options['title_field']);
    $item->link = $this->getFieldUrl($this->getField($row_index, $this->options['link_field']))->setAbsolute()->toString();

    $field = $this->getField($row_index, $this->options['description_field']);
    $item->description = is_array($field) ? $field : ['#markup' => $field];
    $item->enclosure = $this->getField($row_index, $this->options['enclosure_field']);

    $item->elements = [
      [
        'key' => 'pubDate',
        'value' => $this->getField($row_index, $this->options['date_field'])],
      [
        'key' => 'dc:creator',
        'value' => $this->getField($row_index, $this->options['creator_field']),
        'namespace' => ['xmlns:dc' => 'http://purl.org/dc/elements/1.1/'],
      ],
      [
        'key' => 'category',
        'value' => $this->getField($row_index, $this->options['category_field'])],
    ];
    $guid_is_permalink_string = 'false';
    $item_guid = $this->getField($row_index, $this->options['guid_field_options']['guid_field']);
    if ($this->options['guid_field_options']['guid_field_is_permalink']) {
      $guid_is_permalink_string = 'true';
      $item_guid = $this->getFieldUrl($item_guid)->setAbsolute()->toString();
    }
    $item->elements[] = [
      'key' => 'guid',
      'value' => $item_guid,
      'attributes' => ['isPermaLink' => $guid_is_permalink_string],
    ];

    $row_index++;

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
   * Retrieves a URL from a field value.
   *
   * @param string $field_value
   *   The field value retrieved with RssFields::getField().
   *
   * @return \Drupal\Core\Url
   *   The URL object built from the field value.
   */
  protected function getFieldUrl($field_value) {
    global $base_path;

    $value = rawurldecode($field_value);

    // Url::fromUserInput expects the argument to be an internal path, so the
    // base path should be stripped if it's there.
    if (substr($value, 0, strlen($base_path)) === $base_path) {
      $value = substr($value, strlen($base_path));
    }

    // @todo Views should expect and store a leading /. See:
    //   https://www.drupal.org/node/2423913
    return Url::fromUserInput('/' . $value);
  }

}
