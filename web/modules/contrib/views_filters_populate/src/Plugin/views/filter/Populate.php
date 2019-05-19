<?php

namespace Drupal\views_filters_populate\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Filter mock class that takes care of removing populated filters
 * from the view if the populated value is empty and exposed.
 */
class PopulateRemoveEmptyFilterMock extends HandlerBase {
  private $views_filters_populate_handler_caller;

  public function __construct($handler) {
    $this->views_filters_populate_handler_caller = $handler;
  }

  public function preQuery() {
    $handler = $this->views_filters_populate_handler_caller;
    foreach ($handler->options['filters'] as $id) {
      unset($handler->view->filter[$id]);
    }
    foreach ($handler->view->filter as $k => $filter) {
      if ($filter === $this) {
        unset($handler->view->filter[$k]);
      }
    }
  }
}


/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("views_filters_populate")
 */
class Populate extends FilterPluginBase {

  /**
   * Because access() on filters are run earlier than the preQuery(), we use
   * that method to add a mock filter array at the end so that the next iteration
   * of filters, will trigger our mocked filter and that filter will take care
   * of removing both itself and the filters that it should populate so that
   * they behave as optional exposed filters (no added where condition).
   *
   * We couldn't do this on preQuery() - we tried. The reason is that preQuery()
   * is not able to unset filters unless they are in the end, otherwise the
   * interation on the filters array will continue and a preQuery will bne run
   * on a null object.
   *
   * @see \Drupal\views\ViewExecutable::_initHandler()
   * @see \Drupal\views\ViewExecutable::build()
   */
  public function access(AccountInterface $account) {
    // $filters = $this->displayHandler->getHandlers('filter');
    // start with default value
    $value = $this->value;

    // If exposed, take data from input, as this hasn't reached the views
    // object yet
    if ($this->options['exposed']) {
      $input = $this->view->getExposedInput();
      $identifier = $this->options['expose']['identifier'];
      if (isset($input[$identifier])) {
        $value = $input[$identifier];
      }
    }

    // INTERNAL HACK: Add a mock filter to the view's filters array at the end
    // that will take care of removing the filters and itself on the next
    // preQuery run.
    if (empty($value) && $this->options['exposed']) {
      $mock_filter = new PopulateRemoveEmptyFilterMock($this);
      $this->view->filter[] = $mock_filter;
    }

    return parent::access($account);
  }

  /**
   * What filters are supported.
   */
  private function isFilterSupported(FilterPluginBase $filter) {
    if ($filter instanceof StringFilter) {
      return TRUE;
    }

    if ($filter instanceof NumericFilter) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['filters'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [];
    foreach ($this->displayHandler->getHandlers('filter') as $name => $filter) {
      if ($filter != $this && $this->isFilterSupported($filter) && !$filter->options['exposed']) {
        $options[$name] = $filter->adminLabel(TRUE);
      }
    }

    $form['filters'] = [
      '#type' => 'select',
      '#title' => $this->t('Available filters'),
      '#description' => $this->t("Only supported <i><b>non exposed</b></i> filters that accept a string value are shown here."),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $this->options['filters'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    $filters = $form_state->getValue(['options', 'filters']);
    if (empty($form['filters']['#options'])) {
      $form_state->setErrorByName('options][filters', $this->t('You have to add <b>supported non exposed</b> filters to be able to use this filter.'));
    }
    elseif (empty($filters)) {
      $form_state->setErrorByName('options][filters', $this->t('You have to select at least one filter to be populated.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preQuery() {
    // start with default value
    $value = $this->value;

    // If exposed, take data from input, as this hasn't reached the views
    // object yet
    if ($this->options['exposed']) {
      $input = $this->view->getExposedInput();
      $identifier = $this->options['expose']['identifier'];
      if (isset($input[$identifier])) {
        $value = $input[$identifier];
      }
    }

    foreach ($this->options['filters'] as $id) {
      $filter = $this->view->filter[$id];
      if (!empty($value)) {
        if ($filter instanceof StringFilter) {
          $filter->value = $value;
        }
        if ($filter instanceof NumericFilter) {
          $filter->value['value'] = $value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    $filters = $this->displayHandler->getHandlers('filter');

    // Only perform these validatiosn if the query is not being built
    // @see ViewExecutable::build()
    if (empty($this->view->build_info)) {
      foreach ($this->options['filters'] as $id) {
        // Make sure are populate filters are properly configured/available
        if (!isset($filters[$id])) {
          $errors[] = $this->t('%display: Filter %filter set in %name is not available.', ['%filter' => $id, '%name' => $this->adminLabel(), '%display' => $this->displayHandler->display['display_title']]);
        }
        else {
          $filter = $filters[$id];

          if ($filter->options['exposed']) {
            $errors[] = $this->t('%display: Filter %filter set in %name is not usable when exposed.', ['%filter' => $filter->adminLabel(), '%name' => $this->adminLabel(), '%display' => $this->displayHandler->display['display_title']]);
          }
        }
      }
    }

    return $errors;
  }

  /**
   * Provide a simple textfield for equality
   *
   * @see \Drupal\views\Plugin\views\filter\StringFilter;
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
    }

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];

    if (!empty($this->options['expose']['placeholder'])) {
      $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
    }

    $user_input = $form_state->getUserInput();
    if ($exposed && !isset($user_input[$identifier])) {
      $user_input[$identifier] = $this->value;
      $form_state->setUserInput($user_input);
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }
  }
}
