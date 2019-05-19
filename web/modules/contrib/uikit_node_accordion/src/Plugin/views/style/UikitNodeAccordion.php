<?php

namespace Drupal\uikit_node_accordion\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a Uikit Node Accordion.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "uikit_node_accordion",
 *   title = @Translation("Uikit Node Accordion"),
 *   help = @Translation("Render a node accordion based on uikit"),
 *   theme = "views_view_uikit_node_accordion",
 *   display_types = { "normal" }
 * )
 */
class UikitNodeAccordion extends StylePluginBase {

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['node_title'] = ['default' => ''];
    $options['node_image'] = ['default' => ''];
    $options['node_created'] = ['default' => ''];
    $options['node_summary'] = ['node_summary' => ''];
    $options['node_body'] = ['node_body' => ''];
    $options['node_link'] = ['node_link' => ''];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $this->displayHandler->getFieldLabels(TRUE);
    $form['node_title'] = [
      '#title' => $this->t('The node title field'),
      '#description' => $this->t('Select the field that will be used as node title.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_title'],
      '#options' => $options,
    ];
    $form['node_image'] = [
      '#title' => $this->t('The node image field'),
      '#description' => $this->t('Select the field that will be used as node image.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_image'],
      '#options' => $options,
    ];
    $form['node_created'] = [
      '#title' => $this->t('The node creation field'),
      '#description' => $this->t('Select the field that will be used as node creation date.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_created'],
      '#options' => $options,
    ];
    $form['node_summary'] = [
      '#title' => $this->t('The node summary field'),
      '#description' => $this->t('Select the field that will be used as node summary.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_summary'],
      '#options' => $options,
    ];
    $form['node_body'] = [
      '#title' => $this->t('The node body field'),
      '#description' => $this->t('Select the field that will be used as node body.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_body'],
      '#options' => $options,
    ];
    $form['node_link'] = [
      '#title' => $this->t('The node link field'),
      '#description' => $this->t('Select the field that will be used as link to node.'),
      '#type' => 'select',
      '#default_value' => $this->options['node_link'],
      '#options' => $options,
    ];
  }

}
