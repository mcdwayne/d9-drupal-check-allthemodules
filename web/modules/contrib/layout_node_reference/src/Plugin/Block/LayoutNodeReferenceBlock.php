<?php

namespace Drupal\layout_node_reference\Plugin\Block;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Provides a 'LayoutNodeReferenceBlock' block.
 *
 * @Block(
 *  id = "layout_node_reference_block",
 *  admin_label = @Translation("Layout node reference block"),
 * )
 */
class LayoutNodeReferenceBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Uncheck 'Display title' checkbox by default.
    return ['label_display' => FALSE] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');
    $options = ['default' => $this->t('Default')];
    foreach ($view_modes as $machine_name => $view_mode) {
      $options[$machine_name] = $view_mode['label'];
    }

    $allowed_content_types = \Drupal::config('layout_node_reference.settings')->get('layout_allow_embed');
    $allowed_content_types = !empty($allowed_content_types) ? array_values($allowed_content_types) : ['article', 'page'];

    $form['node_block'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#required' => TRUE,
      '#title' => $this->t('Node block'),
      '#default_value' => !empty($this->configuration['node_block']) ? Node::load($this->configuration['node_block']) : '',
      '#selection_settings' => [
        'target_bundles' => $allowed_content_types,
      ],
      '#weight' => '8',
      '#ajax' => [
        'event' => 'blur',
        'callback' => [$this, 'ajaxHandleDisplayModesCallback'],
        'wrapper' => 'edit-display-mode-wrapper',
      ],
    ];

    $form['node_block_display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#default_value' => !empty($this->configuration['node_block_display_mode']) ? $this->configuration['node_block_display_mode'] : '',
      '#weight' => '9',
      '#options' => $options,
      '#prefix' => '<div id="edit-display-mode-wrapper">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['node_block'] = $form_state->getValue('node_block');
    $this->configuration['node_block_display_mode'] = $form_state->getValue('node_block_display_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = [];
    $output = '';
    $view_mode = !empty($this->configuration['node_block_display_mode']) ? $this->configuration['node_block_display_mode'] : 'default';
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $node = Node::load($this->configuration['node_block']);
    if (!empty($node) && $node->id()) {
      $view_build = $view_builder->view($node, $view_mode);
      $output = render($view_build);
    }

    $build['layout_node_reference_block_node_block']['#markup'] = $output;

    return $build;
  }

  /**
   * Dynamically load display modes based on selected node bundle.
   *
   * @param array $form
   *   Form elements array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   JSON response object for AJAX requests.
   */
  public function ajaxHandleDisplayModesCallback(array $form, FormStateInterface $form_state) {

    $options = [];
    $node_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getUserInput()['settings']['node_block']);
    $node = Node::load($node_id);

    if (!empty($node) && $node instanceof Node) {
      $view_modes = \Drupal::service('entity_display.repository')->getViewModeOptionsByBundle('node', $node->getType());
      foreach ($view_modes as $machine_name => $view_mode) {
        $label = $view_mode instanceof TranslatableMarkup ? $view_mode->render() : $view_mode;
        $options[$machine_name] = $label;
      }
    }
    else {
      $options = ['default' => $this->t('Default')];
      $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');
      foreach ($view_modes as $machine_name => $view_mode) {
        $options[$machine_name] = $view_mode['label'];
      }
    }

    $display_modes = [
      '#type' => 'select',
      '#title' => $this->t('Display mode'),
      '#attributes' => ['name' => 'settings[node_block_display_mode]'],
      '#options' => $options,
      '#value' => !empty($this->configuration['node_block_display_mode']) ? $this->configuration['node_block_display_mode'] : '',
    ];

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#edit-display-mode-wrapper', $display_modes));

    return $response;
  }

}
