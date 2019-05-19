<?php

namespace Drupal\views_inject\Plugin\views\display_extender;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\views\Views;

/**
 * Display extender plugin to inject results from one display into another.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "inject_results",
 *   title = @Translation("Inject results"),
 *   help = @Translation("Inject results from another views display."),
 *   no_ui = FALSE,
 * )
 */
class InjectResults extends DisplayExtenderPluginBase implements CacheableDependencyInterface {

  /**
   * The views results from the view to be injected.
   *
   * @var array
   */
  protected $resultsToInject;

  /**
   * The view object used for injection.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $injectedView;

  /**
   * The page index that the host view was requested for.
   *
   * @var int
   */
  protected $currentPage;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return [
      'source_display' => ['default' => ''],
      'pass_arguments' => ['default' => FALSE],
      'offset' => ['default' => 0],
      'chunk_size' => ['default' => 0],
      'chunk_distance' => ['default' => 0],
    ] + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'inject_results') {
      $form['source_display'] = [
        '#type' => 'select',
        '#title' => $this->t('Source display'),
        '#description' => $this->t('Please specify the display from which to inject results.'),
        '#options' => Views::getViewsAsOptions(FALSE, 'enabled', $this->view, TRUE, TRUE),
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $this->options['source_display'],
      ];

      $should_inject = [':input[name="source_display"]' => ['!value' => '']];

      $form['pass_arguments'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Pass arguments to injecting view'),
        '#default_value' => $this->options['pass_arguments'],
        '#states' => ['visible' => $should_inject],
      ];

      $form['offset'] = [
        '#type' => 'number',
        '#title' => $this->t('Offset to first injected result'),
        '#default_value' => $this->options['offset'],
        '#min' => 0,
        '#states' => ['visible' => $should_inject],
      ];
      $form['chunk_size'] = [
        '#type' => 'number',
        '#title' => $this->t('Number of results to inject at a time'),
        '#default_value' => $this->options['chunk_size'],
        '#min' => 0,
        '#states' => ['visible' => $should_inject],
      ];
      $form['chunk_distance'] = [
        '#type' => 'number',
        '#title' => $this->t('Number of normal view results to show between batches'),
        '#default_value' => $this->options['chunk_distance'],
        '#min' => 0,
        '#states' => ['visible' => $should_inject],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    // @todo
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'inject_results') {
      $submitted = $form_state->cleanValues()->getValues();
      $this->options = $submitted;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['inject_results'] = [
      'title' => $this->t('Inject results'),
      'column' => 'second',
    ];
    $options['inject_results'] = [
      'category' => 'inject_results',
      'title' => $this->t('Inject results'),
      'value' => $this->t('No'),
    ];
    if ($this->options['source_display']) {
      list($view_id, $display_id) = explode(':', $this->options['source_display']);
      $inject_view = $view_id == $this->view->storage->id() ? $display_id : $this->options['source_display'];

      if ($this->options['pass_arguments']) {
        $options['inject_results']['value'] = $this->t('@view with arguments', [
          '@view' => $inject_view,
        ]);
      }
      else {
        $options['inject_results']['value'] = $inject_view;
      }
    }
  }

  /**
   * Determine whether the extender's functionality is active for this view.
   */
  public function shouldInject() {
    return !empty($this->options['source_display']);
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    if (!$this->shouldInject()) {
      return;
    }

    // Get results from other display for injecting using a new
    // ViewsExecutable object to prevent messing up the current view.
    list($view_id, $display_id) = explode(':', $this->options['source_display']);
    $inject_view = Views::getView($view_id);
    if (!is_object($inject_view)) {
      return;
    }
    $inject_view->setDisplay($display_id);

    if ($this->options['pass_arguments']) {
      $inject_view->setArguments($this->view->args);
    }

    // Force not using any paging to keep math logic intact.
    // @todo Set offset/limit dynamically based on injects-per-page.
    $inject_view->getDisplay()->setOverride('pager', TRUE);
    $inject_view->getDisplay()->setOption('pager', ['type' => 'none']);
    $inject_view->setItemsPerPage(NULL);
    $inject_view->setOffset(NULL);

    $inject_view->preExecute();
    $inject_view->execute();

    $this->injectedView = $inject_view;
    $this->resultsToInject = $inject_view->result;

    $offsets = $this->getPageItemOffsets();

    // Adjust view offset to account for the number of injected results
    // displayed on previous pages.
    $view_offset = $this->view->getOffset();
    $view_offset -= $offsets['skip_injected_results'];
    $this->view->setOffset($view_offset);

    // Also apply this offset to the views pager.
    if ($pager = $this->view->getPager()) {
      // @todo Check if we need to use -$offsets['skip_injected_results'] instead.
      $pager->setOffset($view_offset);
      // Store current page, because Views might unset it from the pager
      // if it thinks there aren't as many pages.
      $this->currentPage = $pager->getCurrentPage();
    }
  }

  /**
   * Modify the view's results before it is rendered.
   *
   * Called during hook_views_post_execute.
   */
  public function postExecute() {
    // In some cases, there are views that need an additional page
    // because of injected results. Views discards those as they are not
    // "regular" results and exceed the view page limit.
    // We stored the number of pages earlier and thus restore it now.
    $this->view->setCurrentPage($this->currentPage);

    $total_chunk_size = $this->options['chunk_size'] + $this->options['chunk_distance'];

    $offsets = $this->getPageItemOffsets();
    $total_view_results = $this->view->total_rows + $this->view->getOffset();

    if ($total_view_results < $offsets['skip_normal_results']) {
      // We're completely out of normal results and only have results for
      // injecting left. Offsets need to be adjusted accordingly, since
      // more injected results than calculated have already been shown.
      $overflow = $offsets['skip_normal_results'] - $total_view_results;
      $offsets['skip_normal_results'] -= $overflow;
      $offsets['skip_injected_results'] += $overflow;
    }

    $offset = $offsets['offset'];
    $skip_injected_results = $offsets['skip_injected_results'];
    $chunk_offset = $offsets['chunk_offset'];

    // Insert items within view results.
    $results = $this->view->result;
    while ($offset < $this->view->getItemsPerPage()) {
      if ($skip_injected_results >= count($this->resultsToInject)) {
        // Done inserting items.
        break;
      }
      elseif ($offset > count($results)) {
        // We've reached the end of the results list, just insert the rest.
        $results[] = $this->resultsToInject[$skip_injected_results];
        $skip_injected_results++;
      }
      elseif ($chunk_offset < $this->options['chunk_size']) {
        // Insert one result row.
        array_splice($results, $offset, 0, [$this->resultsToInject[$skip_injected_results]]);
        $skip_injected_results++;
        if ($skip_injected_results >= count($this->resultsToInject)) {
          break;
        }
      }

      $offset++;
      $chunk_offset = ($chunk_offset + 1) % $total_chunk_size;
    }

    $this->view->result = array_slice($results, 0, $this->view->getItemsPerPage());

    if ($pager = $this->view->getPager()) {
      // Update pager total to take into account injected items.
      $pager->total_items = count($this->resultsToInject) + $total_view_results;
      $pager->updatePageInfo();
    }
  }

  /**
   * Calculates the offset when displaying items on a page.
   *
   * @return array
   *   Offset information for the host view.
   *
   * @todo Properly document result values.
   * @todo Possibly add setting to reset inject offsets per page?
   */
  public function getPageItemOffsets() {
    // A chunk consist of a certain number of injected items, followed by
    // a number of normal results for the view.
    $total_chunk_size = $this->options['chunk_size'] + $this->options['chunk_distance'];

    // Amount of items to skip before normal "chunks" with injected content
    // are displayed. This is equal to the configured value on the first page.
    $offset = (int) $this->options['offset'];
    // Number of injected items that have already been displayed so far.
    // This is 0 on the first page.
    $skip_injected_results = 0;
    $skip_normal_results = 0;
    $current_chunk_offset = 0;

    // Pager might not be initialized yet, but it will be if we try to get it.
    $pager = $this->view->getPager();
    if ($this->view->usePager()) {
      // Calculate offsets depending on current page.
      $items_on_previous_pages = $this->view->getItemsPerPage() * $this->view->getCurrentPage();
      $offset -= $items_on_previous_pages;
      $skip_normal_results = min($items_on_previous_pages, $this->options['offset']);

      if ($offset < 0) {
        // We're past the initial offset and need to calculate how many
        // injected items have already been displayed on previous pages.
        // For example, offset is at -4 if there are 4 results have been
        // displayed as part of normal chunks on the previous pages.
        //
        // Determine where the chunk containing the first item from this page
        // started. For example, if offset is -4 and we have a chunk size
        // of 3, then 1 full chunk ($skipped_chunks) and
        // 1 single item has been displayed on previous pages.
        $skipped_chunks = floor(-$offset / $total_chunk_size);

        // Adjust our numbers according to the full chunks that were displayed
        // on previous pages.
        //
        // Calculate the number of injected results that have been displayed.
        $skip_injected_results += $skipped_chunks * $this->options['chunk_size'];
        // Calculate number of normal views results that have been displayed
        // as part of chunks.
        $skip_normal_results += $skipped_chunks * $this->options['chunk_distance'];
        // Update the offset.
        $offset += $skipped_chunks * $total_chunk_size;

        // Handle chunks that span across pages.
        if ($offset < 0) {
          // Up to chunk_size injected items have been displayed on the
          // previous page.
          $skip_injected_results += min(abs($offset), $this->options['chunk_size']);
          // If the partial chunk is big enough, some normal view items might
          // also have been displayed.
          $skip_normal_results += max(0, abs($offset) - $this->options['chunk_size']);

          // Note the partial chunk offset so those items can be skipped
          // when displaying the current page.
          $current_chunk_offset = abs($offset);

          // The "normal" offset is set to 0 because we immediately start
          // displaying chunks on these later pages. There is no normal items
          // to display before chunks start.
          $offset = 0;
        }
      }
    }

    // We can't skip more injected results than available.
    $skip_injected_results = min($skip_injected_results, count($this->resultsToInject));

    return [
      'offset' => $offset,
      'skip_injected_results' => $skip_injected_results,
      'chunk_offset' => $current_chunk_offset,
      'skip_normal_results' => $skip_normal_results,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    if ($this->shouldInject() && $this->injectedView) {
      $cache = $this->injectedView->getDisplay()->getCacheMetadata();
      return $cache->getCacheContexts();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($this->shouldInject() && $this->injectedView) {
      return $this->injectedView->getCacheTags();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if ($this->shouldInject() && $this->injectedView) {
      $cache = $this->injectedView->getDisplay()->getCacheMetadata();
      return $cache->getCacheMaxAge();
    }
    return -1;
  }

}
