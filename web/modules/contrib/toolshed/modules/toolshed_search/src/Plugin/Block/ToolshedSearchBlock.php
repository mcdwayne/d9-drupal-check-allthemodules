<?php

namespace Drupal\toolshed_search\Plugin\Block;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\views\Views;
use Drupal\search_api\Plugin\views\filter\SearchApiFulltext;
use Drupal\Component\Utility\NestedArray;

/**
 * Block for creating simple search keyword blocks.
 *
 * @Block(
 *   id = "toolshed_search_api_keyword",
 *   admin_label = @Translation("Toolshed: Search block"),
 *   category = @Translation("Search API"),
 * )
 */
class ToolshedSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal form builder interface for generating form structures.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Create a new instance of the toolshed search block.
   *
   * @param array $configuration
   *   The plugin configurations for the block setup.
   * @param string $plugin_id
   *   The unique ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition from the plugin discovery.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Drupal form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * Retrieve a view and display settings based a view and display ID.
   *
   * @return \Drupal\views\Entity\View|false
   *   The view entity loaded, with the proper display configured. Will return
   *   NULL if the view or the display are no longer available.
   */
  protected static function fetchView($viewName) {
    list($viewId, $viewDisplay) = explode(':', $viewName);

    if ($viewId && $viewDisplay) {
      $view = Views::getView($viewId);
      return ($view && $view->setDisplay($viewDisplay)) ? $view : FALSE;
    }

    return FALSE;
  }

  /**
   * Retrieve the view configured to work with this block.
   *
   * @return \Drupal\views\Entity\View|false
   *   The view entity loaded, with the proper display configured. Will return
   *   NULL if the view or the display are no longer available.
   */
  protected function getView() {
    if (!isset($this->loadedView)) {
      $this->loadedView = static::fetchView($this->configuration['view']);
    }

    return $this->loadedView;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    $view = $this->getView();

    if ($view && $view->access($view->current_display, $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view' => ':',
      'filter' => '',
      'aria_context' => '',
      'placeholder_text' => $this->t('Search by Keyword'),
      'submit_class_names' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($view = $this->getView()) {
      $filterId = $this->configuration['filter'];
      $filters = $view->display_handler->getHandlers('filter');

      if (!empty($filters[$filterId]) && ($exposeInfo = $filters[$filterId]->exposedInfo())) {
        $formContext = [
          'view' => $view,
          'filter_value' => $exposeInfo['value'],
          'aria_context' => $this->configuration['aria_context'],
          'placeholder_text' => $this->configuration['placeholder_text'],
          'submit_class_names' => $this->configuration['submit_class_names'],
        ];

        $form_state = new FormState();
        $form_state->setMethod('GET');
        $form_state->setRequestMethod('GET');
        $form_state->disableCache();
        $form_state->addBuildInfo('args', [$formContext]);

        $form = $this->formBuilder->buildForm('\\Drupal\\toolshed_search\\Form\\KeywordSearchForm', $form_state);

        return ['content' => $form];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['aria_context'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Aria context"),
      '#pattern' => '^[\w_ ]*$',
      '#default_value' => $this->configuration['aria_context'],
      '#description' => $this->t('Optional extra label to provide info to the ARIA descriptions to provide context if the search block appears more than once on a page. (i.e. "footer" or "header")'),
    ];

    $views = Views::getEnabledViews();
    $allowedViews = ['' => '-- Select a View --'];
    foreach ($views as $viewId => $view) {
      if ($view->get('base_field') !== 'search_api_id' &&  !preg_match('/^search_api_index/', $view->get('base_table'))) {
        continue;
      }

      // Only create a keyword search block for search API page views.
      foreach ($view->get('display') as $displayId => $display) {
        if ($display['display_plugin'] === 'page') {
          $allowedViews["$viewId:$displayId"] = $this->t(':view (:display)', [
            ':view' => $view->label(),
            ':display' => $display['display_title'],
          ]);
        }
      }
    }

    $parentState = $form_state instanceof SubformState ? $form_state->getCompleteFormState() : $form_state;
    $viewId = $parentState->getValue(['settings', 'view']);
    $viewId = !isset($viewId) ? $this->configuration['view'] : $viewId;

    $form['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View to display referrals'),
      '#required' => TRUE,
      '#options' => $allowedViews,
      '#default_value' => $viewId,
      '#description' => $this->t('Only search_api views with a page display appear in this list.'),
      '#ajax' => [
        'wrapper' => 'views-display-ajax-wrapper',
        'callback' => static::class . '::ajaxUpdateViewDisplays',
      ],
    ];

    $form['filter'] = [
      '#prefix' => '<div id="views-display-ajax-wrapper">',
      '#suffix' => '</div>',
    ];

    if (isset($allowedViews[$viewId]) && $view = static::fetchView($viewId)) {
      foreach ($view->display_handler->getHandlers('filter') as $fieldName => $handler) {
        if ($handler instanceof SearchApiFullText && $handler->isExposed()) {
          $filterOpts[$fieldName] = $handler->pluginTitle();
        }
      }

      $form['filter'] += [
        '#type' => 'select',
        '#title' => t('Keyword textfield'),
        '#required' => TRUE,
        '#options' => $filterOpts,
        '#default_value' => $this->configuration['filter'],
        '#description' => $this->t('Select the filter which should be used for the keyword input. Only exposed fulltext filters will appear in this list.'),
      ];
    }

    $form['placeholder_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder text'),
      '#default_value' => $this->configuration['placeholder_text'],
      '#description' => $this->t("Text to appear in the search box when it's empty."),
    ];

    $form['submit_class_names'] = [
      '#type' => 'css_class',
      '#title' => $this->t('Button class names'),
      '#default_value' => $this->configuration['submit_class_names'],
      '#description' => $this->t('CSS class names to include with the submit button.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view'] = $form_state->getValue('view');
    $this->configuration['filter'] = $form_state->getValue('filter');
    $this->configuration['aria_context'] = $form_state->getValue('aria_context');
    $this->configuration['placeholder_text'] = $form_state->getValue('placeholder_text');
    $this->configuration['submit_class_names'] = $form_state->getValue('submit_class_names');

    if (!empty($this->configuration['aria_context'])) {
      $this->configuration['aria_context'] = ' ' . $this->configuration['aria_context'];
    }
  }

  /**
   * Update the block configuration form after the view has been selected.
   *
   * @param array $form
   *   Structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form and build information.
   *
   * @return array
   *   Renderable array of content to be replaced using AJAX.
   */
  public static function ajaxUpdateViewDisplays(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -1);
    $parents[] = 'filter';

    return NestedArray::getValue($form, $parents);
  }

}
