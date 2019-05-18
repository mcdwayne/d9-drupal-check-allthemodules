<?php

namespace Drupal\contextual_views\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;

/**
 * Provides a contextual views block plugin.
 *
 * The derivative provided by Views makes sure to provide a block derivative
 * for each instance of this block, see
 * \Drupal\views\Plugin\Derivative\ViewsBlock.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "contextual_views_block",
 *   title = @Translation("Contextual block"),
 *   help = @Translation("Provides context dependent options to Views blocks."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Contextual block")
 * )
 *
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class ContextualBlock extends Block {

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $filtered_allow = array_filter($this->getOption('allow'));
    $filter_options = [
      'pager' => $this->t('Pager type'),
      'items_per_page' => $this->t('Items per page'),
      'offset' => $this->t('Pager offset'),
      'configure_sorts' => $this->t('Configure sorts'),
    ];
    $filter_intersect = array_intersect_key($filter_options, $filtered_allow);

    $options['allow'] = [
      'category' => 'block',
      'title' => $this->t('Allowed settings'),
      'value' => empty($filtered_allow) ? $this->t('None') : implode(', ', $filter_intersect),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $options = $form['allow']['#options'];
    $options['pager'] = $this->t('Pager type');
    $options['offset'] = $this->t('Pager offset');
    $options['configure_sorts'] = $this->t('Configure sorts');
    $form['allow']['#options'] = $options;
    // Update the items_per_page if set.
    $defaults = array_filter($form['allow']['#default_value']);
    if (isset($defaults['items_per_page'])) {
      $defaults['items_per_page'] = 'items_per_page';
    }
    $form['allow']['#default_value'] = $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();

    // @todo: Generate form based upon Views context.

    // Provide "Pager type" block settings form.
    if (!empty($allow_settings['pager'])) {
      $pager_options = [
        'view' => $this->t('Default'),
        'some' => $this->t('Display a specified number of items'),
        'none' => $this->t('Display all items'),
      ];
      $form['override']['pager'] = [
        '#type' => 'radios',
        '#title' => $this->t('Pager'),
        '#options' => $pager_options,
        '#default_value' => isset($block_configuration['pager']) ? $block_configuration['pager'] : 'view',
      ];
    }

    // Modify "Items per page" block settings form.
    if (!empty($allow_settings['items_per_page'])) {
      // Items per page.
      $form['override']['items_per_page']['#type'] = 'number';
      unset($form['override']['items_per_page']['#options']);
    }

    // Provide "Pager offset" block settings form.
    if (!empty($allow_settings['offset'])) {
      $form['override']['pager_offset'] = [
        '#type' => 'number',
        '#title' => $this->t('Pager offset'),
        '#default_value' => isset($block_configuration['pager_offset']) ? $block_configuration['pager_offset'] : 0,
        '#description' => $this->t('For example, set this to 3 and the first 3 items will not be displayed.'),
      ];
    }

    // Provide "Configure sorts" block settings form.
    if (!empty($allow_settings['configure_sorts'])) {
      $sorts = $this->getHandlers('sort');
      $options = [
        'ASC' => $this->t('Sort ascending'),
        'DESC' => $this->t('Sort descending'),
      ];
      foreach ($sorts as $sort_name => $plugin) {
        $form['override']['sort'][$sort_name] = [
          '#type' => 'details',
          '#title' => $plugin->adminLabel(),
        ];
        $form['override']['sort'][$sort_name]['plugin'] = [
          '#type' => 'value',
          '#value' => $plugin,
        ];
        $form['override']['sort'][$sort_name]['order'] = [
          '#title' => $this->t('Order'),
          '#type' => 'radios',
          '#options' => $options,
          '#default_value' => $plugin->options['order'],
        ];

        // Set default values for sorts for this block.
        if (!empty($block_configuration["sort"][$sort_name])) {
          $form['override']['sort'][$sort_name]['order']['#default_value'] = $block_configuration["sort"][$sort_name];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);
    $configuration = $block->getConfiguration();
    $allow_settings = array_filter($this->getOption('allow'));

    // Save "Pager type" settings to block configuration.
    if (!empty($allow_settings['pager'])) {
      if ($pager = $form_state->getValue(['override', 'pager'])) {
        $configuration['pager'] = $pager;
      }
    }

    // Save "Pager offset" settings to block configuration.
    if (!empty($allow_settings['offset'])) {
      $configuration['pager_offset'] = $form_state->getValue(['override', 'pager_offset']);
    }

    // Save "Configure sorts" settings to block configuration.
    if (!empty($allow_settings['configure_sorts'])) {
      $sorts = $form_state->getValue(['override', 'sort']);
      foreach ($sorts as $sort_name => $sort) {
        $plugin = $sort['plugin'];
        // Check if we want to override the default sort order.
        if ($plugin->options['order'] != $sort['order']) {
          $configuration['sort'][$sort_name] = $sort['order'];
        }
      }
    }

    $block->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function preBlockBuild(ViewsBlock $block) {
    $allow_settings = array_filter($this->getOption('allow'));
    $config = $block->getConfiguration();
    list(, $display_id) = explode('-', $block->getDerivativeId(), 2);

    if (!empty($allow_settings['items_per_page']) && !empty($config['items_per_page']) && $config['items_per_page'] !== 'none') {
      $this->view->setItemsPerPage($config['items_per_page']);
    }

    // Change pager offset settings based on block configuration.
    if (!empty($allow_settings['offset']) && isset($config['pager_offset'])) {
      $this->view->setOffset($config['pager_offset']);
    }

    // Change pager style settings based on block configuration.
    if (!empty($allow_settings['pager']) && isset($config['pager'])) {
      $pager = $this->view->display_handler->getOption('pager');
      if (!empty($config['pager']) && $config['pager'] != 'view') {
        $pager['type'] = $config['pager'];
      }
      $this->view->display_handler->setOption('pager', $pager);
    }

    // Change sorts based on block configuration.
    if (!empty($allow_settings['configure_sorts']) && isset($config['sort'])) {
      $sorts = $this->view->getHandlers('sort', $display_id);
      foreach ($sorts as $sort_name => $sort) {
        if (!empty($config["sort"][$sort_name])) {
          $sort['order'] = $config["sort"][$sort_name];
          $this->view->setHandler($display_id, 'sort', $sort_name, $sort);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    $filters = $this->getHandlers('filter');
    foreach ($filters as $filter) {
      if ($filter->isExposed() && !empty($filter->exposedInfo())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Exposed widgets typically only work with ajax in Drupal core, however
   * #2605218 totally breaks the rest of the functionality in this display and
   * in Core's Block display as well, so we allow non-ajax block views to use
   * exposed filters and manually set the #action to the current request uri.
   */
  public function elementPreRender(array $element) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $element['#view'];
    if (!empty($view->exposed_widgets['#action']) && !$view->ajaxEnabled()) {
      $view->exposed_widgets['#action'] = \Drupal::request()->getRequestUri();
    }
    return parent::elementPreRender($element);
  }

}
