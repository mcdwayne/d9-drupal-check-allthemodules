<?php

namespace Drupal\views_raw_sql\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a numeric range.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("argument_views_raw_sql")
 */
class RawSQLArgument extends ArgumentPluginBase {

  /**
   * Provides current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Instantiates this form class.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['where_raw_sql'] = ['default' => 0];
    return $options;
  }

  /**
   * Build the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if ($this->account->hasPermission('edit views raw sql')) {
      $form['where_raw_sql'] = [
        '#type' => 'textfield',
        '#title' => t('Raw SQL'),
        '#default_value' => $this->options['where_raw_sql'],
        '#weight' => -6,
      ];
    }
  }

  /**
   * Create the query.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $raw_sql = $this->options['where_raw_sql'];
    $argument = $this->argument;
    $raw_sql = str_replace('%argument%', $argument, $raw_sql);
    $this->query->addWhereExpression($this->options['group'], $raw_sql);
  }

}
