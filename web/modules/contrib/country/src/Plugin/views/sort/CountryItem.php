<?php

namespace Drupal\country\Plugin\views\sort;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler for country fields.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("country_item")
 */
class CountryItem extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['default_sort'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['default_sort'] = [
      '#type' => 'radios',
      '#title' => t('Sort by ISO code'),
      '#options' => [t('No'), t('Yes')],
      '#default_value' => $this->options['default_sort'],
    ];
  }

  /**
   * Called to add the sort to a query.
   *
   * Sort by index of country names using sql FIELD function.
   *
   * @see http://dev.mysql.com/doc/refman/5.5/en/string-functions.html#function_field
   */
  public function query() {
    // Fall back to default sort for sorting by country code.
    if ($this->options['default_sort']) {
      return parent::query();
    }

    $this->ensureMyTable();
    $country_codes = array_keys(\Drupal::service('country_manager')->getList());
    $connection = Database::getConnection();

    $formula = 'FIELD(' . $this->getField() . ', ' . implode(', ', array_map([$connection, 'quote'], $country_codes)) . ')';
    $this->query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_' . $this->field . '_country_name_sort');
  }

}
