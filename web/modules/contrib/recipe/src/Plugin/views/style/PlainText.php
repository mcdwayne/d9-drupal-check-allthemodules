<?php

namespace Drupal\recipe\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Default style plugin to render recipes in plain text.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "recipe_plain_text",
 *   title = @Translation("Plain text"),
 *   help = @Translation("Generates a plain text recipe from a view."),
 *   theme = "recipe_view_plain_text",
 *   display_types = {"recipe"}
 * )
 */
class PlainText extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  public function attachTo(array &$build, $display_id, Url $url, $title) {
    $url_options = [];
    $input = $this->view->getExposedInput();
    if ($input) {
      $url_options['query'] = $input;
    }
    $url_options['absolute'] = TRUE;

    // Attach a link to the plain text, which is an alternate representation.
    $build['#attached']['html_head_link'][][] = [
      'rel' => 'alternate',
      'type' => 'text/plain',
      'title' => $title,
      'href' => $url->setOptions($url_options)->toString(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['wordwrap_width'] = ['default' => 75];
    $options['hide_empty'] = ['default' => FALSE];
    $options['row_separator'] = ['default' => "====\n\n"];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['wordwrap_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Wordwrap width'),
      '#default_value' => $this->options['wordwrap_width'],
      '#description' => $this->t('The number of characters at which text will wrap to the next line.'),
    ];
    $form['hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide empty fields'),
      '#default_value' => $this->options['hide_empty'],
      '#description' => $this->t('Do not display fields or labels for fields that are empty.'),
    ];
    $form['row_separator'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Row separator'),
      '#default_value' => $this->options['row_separator'],
      '#description' => $this->t('Text used to separate multiple recipes. Any HTML tags will be stripped.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function renderRowGroup(array $rows = []) {
    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#rows' => $rows,
    ];
  }

}
