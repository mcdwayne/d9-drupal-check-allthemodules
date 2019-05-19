<?php
/**
 * @file
 * Definition of Drupal\simple_global_filter\Plugin\views\filter\GlobalFilter.
 */
namespace Drupal\simple_global_filter\Plugin\views\filter;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\simple_global_filter\Entity\GlobalFilterInterface;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("simple_global_filter")
 */
class GlobalFilter extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Choose the global filter to which this field will match.');
  }

  /**
   * Set the global filter value in the query.
   */
  public function query() {
    if (!is_string($this->value)) {
      return;
    }
    $this->ensureMyTable();
    $where = "$this->tableAlias.$this->realField ";
    $filter = \Drupal::service('simple_global_filter.global_filter')->get($this->value);
    $this->value = $filter;
    if ($this->operator == '=') {
      $where .= "= " . $filter;
    }
    else {
      $where .= "!= " . $filter;
    }
    $this->query->addWhereExpression($this->options['group'], $where);
  }

  /**
   *
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    $options = [];
    $global_filters = \Drupal::entityQuery('global_filter')->execute();
    foreach($global_filters as $global_filter_name) {
      $global_filter = \Drupal::entityTypeManager()->getStorage('global_filter')
        ->load($global_filter_name);
      $options[$global_filter->id()] = $global_filter->label();
    }

    // List of existing global filters:
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the global filter.'),
      '#options' => $options,
      '#default_value' => $this->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    return ['=' => $operators['='], '!=' => $operators['!=']];
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $output = '';
    if (!empty($this->value)) {
      $global_filter = \Drupal::entityTypeManager()->getStorage('global_filter')
        ->load($this->value);
      if ($global_filter instanceof GlobalFilterInterface) {
        $name = $global_filter->label();
        if ($this->operator == '=') {
          $output = $this->t('equal to global filter @name', [
            '@name' => $name,
          ]);
        }
        else {
          $output = $this->t('not equal to global filter @name', [
            '@name' => $name,
          ]);
        }
      }
    }

    return $output;
  }
}

