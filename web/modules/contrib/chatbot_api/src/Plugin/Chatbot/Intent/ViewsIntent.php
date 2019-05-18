<?php

namespace Drupal\chatbot_api\Plugin\Chatbot\Intent;

use Drupal\chatbot_api\Plugin\IntentPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewExecutableFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Views block.
 *
 * @Intent(
 *   id = "views_intent",
 *   label = @Translation("Views Intent"),
 *   deriver = "Drupal\chatbot_api\Plugin\Derivative\ViewsIntent"
 * )
 */
class ViewsIntent extends IntentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The View executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The display ID being used for this View.
   *
   * @var string
   */
  protected $displayID;

  /**
   * Indicates whether the display was successfully set.
   *
   * @var bool
   */
  protected $displaySet;

  /**
   * Pager offset iterator.
   *
   * @var int
   */
  protected $iteration;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ViewsIntent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views\ViewExecutableFactory $executable_factory
   *   The view executable factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The views storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewExecutableFactory $executable_factory, RendererInterface $renderer, EntityStorageInterface $storage) {
    $this->pluginId = $plugin_id;
    $this->displayID = $plugin_definition['display_name'];
    // Load the view.
    $name = $plugin_definition['view_name'];
    $view = $storage->load($name);
    $this->view = $executable_factory->get($view);
    $this->displaySet = $this->view->setDisplay($this->displayID);

    $this->renderer = $renderer;

    // Run parent construct, it will setup request and response properties.
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Keep track of previous iteration.
    $this->iteration = (int) $this->request->getIntentAttribute($plugin_id . 'Iterator', 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('views.executable'),
      $container->get('renderer'),
      $container->get('entity.manager')->getStorage('view')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process() {

    // Initialise views handlers.
    $this->view->initHandlers();
    $this->view->initPager();

    // Process plugin filters and pager progress.
    $this->processFilters();
    $this->processIterationProgress();

    $output = $this->view->executeDisplay($this->displayID, []);
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $this->response->setIntentResponse(trim(preg_replace('/\s+/', ' ', strip_tags($this->renderer->render($output)))));

    $this->incrementIterationProgress();
  }

  /**
   * Apply Slot values as filters.
   */
  protected function processFilters() {

    foreach ($this->view->filter as $name => $instance) {
      $filter_value = NULL;

      // Check if current context has any filter.
      if ($attribute_filter = $this->request->getIntentAttribute($this->pluginId . 'Filter' . $name)) {
        $filter_value = $attribute_filter;
      }

      // Filter values in slots have priority.
      if ($slot_filter = $this->request->getIntentSlot($name)) {
        $filter_value = $slot_filter;
      }

      if ($filter_value) {
        // Set the value as input.
        $this->view->setExposedInput([$name => $filter_value]);

        // Store the value on context.
        $this->response->addIntentAttribute($this->pluginId . 'Filter' . $name, $filter_value);
      }
    }
  }

  /**
   * Set the pager to the right place.
   */
  protected function processIterationProgress() {
    if ($this->view->pager->getPluginId() !== 'none') {
      $items_per_page = (int) $this->view->pager->getItemsPerPage();
      $this->view->pager->setOffset($items_per_page * $this->iteration);
    }
  }

  /**
   * Increment the iterator.
   *
   * Increment the iterator/pager offset, so next time we pull the next
   * content item.
   */
  protected function incrementIterationProgress() {
    if ($this->view->pager->getPluginId() !== 'none') {
      $this->response->addIntentAttribute($this->pluginId . 'Iterator', $this->iteration + 1);
    }
  }

}
