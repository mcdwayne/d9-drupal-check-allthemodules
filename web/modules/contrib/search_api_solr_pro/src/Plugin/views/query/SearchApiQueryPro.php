<?php

namespace Drupal\search_api_solr_pro\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\ViewExecutable;


/**
 * Defines a Views query class for searching on Search API indexes.
 *
 * @ViewsQuery(
 *   id = "search_api_query",
 *   title = @Translation("Search API Query"),
 *   help = @Translation("The query will be generated and run using the Search API.")
 * )
 */
class SearchApiQueryPro extends SearchApiQuery {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return parent::defineOptions() + [
      'bypass_access' => [
        'default' => FALSE,
      ],
      'skip_access' => [
        'default' => FALSE,
      ],
      'avoid_load_entity' => [
        'default' => FALSE,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['avoid_load_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip entity load'),
      '#description' => $this->t("By default, entities related with the solr results will be load. If you check this option the entities will not be load reducing the time response to generate the view."),
      '#default_value' => $this->options['avoid_load_entity'],
      '#weight' => -1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    parent::build($view);
    
    // Add the "search_api_avoid_load_entity" option to the query, if desired.
    if (!empty($this->options['avoid_load_entity'])) {
      $this->query->setOption('search_api_avoid_load_entity', TRUE);
      $this->query->setOption('search_api_add_fields', array_reduce($view->field, function($added, $current) {
        if (!$current->options['exclude']) $added[] = $current->options['id'];
        return $added;
      }, []));
    }

  }

}
