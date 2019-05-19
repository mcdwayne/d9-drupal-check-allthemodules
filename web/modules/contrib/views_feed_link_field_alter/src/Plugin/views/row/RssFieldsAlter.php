<?php

namespace Drupal\views_feed_link_field_alter\Plugin\views\row;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\row\RssFields;

/**
 * Renders an RSS item based on fields.
 *
 * @ViewsRow(
 *   id = "rss_fields_alter",
 *   title = @Translation("Fields"),
 *   help = @Translation("Display fields as RSS items."),
 *   theme = "views_view_row_rss",
 *   display_types = {"feed"}
 * )
 */
class RssFieldsAlter extends RssFields {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['custom_path_alter'] = ['default' => FALSE];
    $options['custom_path_domain'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['path_options'] = [
      '#type' => 'details',
      '#title' => $this->t('Path custom options'),
      '#open' => TRUE,
    ];

    $form['custom_path_alter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Alter the feed path'),
      '#default_value' => $this->options['custom_path_alter'],
      '#fieldset' => 'path_options',
    ];

    $form['custom_path_domain'] = [
      '#title' => $this->t('Custom domain'),
      '#type' => 'textfield',
      '#description' => $this->t('Enter the domain name'),
      '#default_value' => $this->options['custom_path_domain'],
      '#fieldset' => 'path_options',
      '#states' => [
        'visible' => [
          'input[name="row_options[custom_path_alter]"]' => ['checked' => TRUE],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    $values = $form_state->getValues();

    $checked = $values['row_options']['custom_path_alter'];
    $domain = $values['row_options']['custom_path_domain'];
    if ($checked && $domain !== '') {
      if (!UrlHelper::isValid($domain, TRUE)) {
        $form_state->setErrorByName('custom_path_domain', $this->t('Make sure that the domain contains the scheme and is well formed.'));
      }
    }
    elseif ($checked && $domain === '') {
      $form_state->setErrorByName('custom_path_domain', $this->t('Please provide a domain name for the path altering.'));
    }
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

    if ($this->options['custom_path_alter']) {
      // Get the defined domain to replace the current one.
      $custom_domain = $this->options['custom_path_domain'];

      // We always get a relative path here.
      $link = Url::fromUri($custom_domain . $this->getField($row_index, $this->options['link_field']));
      // Alter the existing link in the item.
      $build['#row']->link = $link;

      // Alter guid.
      $item_guid = $this->getField($row_index, $this->options['guid_field_options']['guid_field']);
      $item_guid =  Url::fromUri($custom_domain . $item_guid);

      foreach ($build['#row']->elements as &$element) {
        if (isset($element['key']) && $element['key'] === 'guid') {
          $element['value'] = $item_guid;
        }
      }
    }

    $row_index++;

    return $build;
  }

}
