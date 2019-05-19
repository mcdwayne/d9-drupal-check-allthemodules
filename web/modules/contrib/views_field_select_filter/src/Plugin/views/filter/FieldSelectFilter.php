<?php

namespace Drupal\views_field_select_filter\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;


/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("fieldselect")
 */
class FieldSelectFilter extends InOperator implements ContainerFactoryPluginInterface {

  protected $valueFormType = 'select';


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['sort_values'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }


  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['sort_values'] = 1;
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['sort_values'] = [
      '#type' => 'radios',
      '#title' => t('Value order'),
      '#description' => t('How to sort the values in the exposed form'),
      '#options' => [0 => $this->t('ASC'), 1 => $this->t('DESC')],
      '#default_value' => $this->options['expose']['sort_values'],
      '#weight' => 1,
    ];
  }

  /**
   * No operator options, it's just IN
   *
   * @return array
   */
  public function operatorOptions($wich = 'in') {
    return [];
  }


  /**
   * @inheritDoc
   * todo check for languages.
   */

  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $allFilters = $this->displayHandler->getOption('filters');
    $connection = \Drupal::database();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select($this->table, 'ft')
      ->fields('ft', [$this->realField])
      ->distinct(TRUE);
    $query->orderBy($this->realField, ($this->options['expose']['sort_values'] == '1') ? 'desc' : 'asc');
    if (isset($allFilters['type'])) {
      $types = array_keys($allFilters['type']['value']);
      $query->condition('bundle', $types, 'in');
    }
    $values = $query->execute()->fetchAllKeyed(0, 0);
    $this->valueOptions = $values;
  }


  /**
   * Only show the value form in the exposed form, for now there is no support
   * for limiting the number of selectable options.
   *
   * {@inheritdoc}
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];

    //$this->buildOptionsForm($form, $form_state);
    if ($form_state->get('exposed')) {
      parent::valueForm($form, $form_state);
    }
  }






  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function getCacheTags() {
  //    $cache_tags = Cache::mergeTags(parent::getCacheTags(), $this->view->storage->getCacheTags());
  //    $cache_tags = Cache::mergeTags($cache_tags, $this->targetEntityType->getListCacheTags());
  //
  //    return $cache_tags;
  //  }
  //
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function getCacheContexts() {
  //    $cache_contexts = Cache::mergeContexts(parent::getCacheContexts(), $this->view->storage->getCacheContexts());
  //    $cache_contexts = Cache::mergeContexts($cache_contexts, $this->targetEntityType->getListCacheContexts());
  //
  //    return $cache_contexts;
  //  }
  //
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function getCacheMaxAge() {
  //    $cache_max_age = Cache::mergeMaxAges(parent::getCacheMaxAge(), $this->view->storage->getCacheMaxAge());
  //
  //    return $cache_max_age;
  //  }

}
