<?php

namespace Drupal\views_random_seed\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views_random_seed\SeedCalculator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handle a random sort with seed.
 *
 * @ViewsSort("views_random_seed_random")
 */
class ViewsRandomSeedRandom extends SortPluginBase {

  /** @var \Drupal\views\Plugin\views\query\Sql */
  public $query;

  /**
   * The seed calculator.
   *
   * @var \Drupal\views_random_seed\SeedCalculator
   */
  protected $seedCalculator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SeedCalculator $seedCalculator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->seedCalculator = $seedCalculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_random_seed.seed_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['user_seed_type'] = ['default' => 'same_per_user'];
    $options['reset_seed_int'] = ['default' => '3600'];
    $options['reset_seed_custom'] = ['default' => '300'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['order']['#access'] = FALSE;

    // User seed type.
    $form['user_seed_type'] = [
      '#type' => 'radios',
      '#title' => t('User seed type'),
      '#options' => [
        'same_per_user' => t('Use the same seed for every user'),
        'diff_per_user' => t('Use a different seed per user'),
      ],
      '#default_value' => isset($this->options['user_seed_type']) ? $this->options['user_seed_type'] : 'same_per_user',
    ];

    // User seed type.
    $form['reset_seed_int'] = [
      '#type' => 'radios',
      '#title' => t('Reset seed'),
      '#options' => [
        'never' => t('Never'),
        'custom' => t('Custom'),
        '3600' => t('Every hour'),
        '28800' => t('Every day'),
      ],
      '#default_value' => isset($this->options['reset_seed_int']) ? $this->options['reset_seed_int'] : '3600',
    ];
    $form['reset_seed_custom'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Custom reset seed'),
      '#required' => TRUE,
      '#default_value' => isset($this->options['reset_seed_custom']) ? $this->options['reset_seed_custom'] : '300',
      '#description' => t('Define your own custom reset time, must be a number and is in seconds. Choose custom in the options above.'),
    ];

    // Caching strategy info.
    // @todo Work on that part.
//    $form['cache_strategy'] = [
//      '#type' => 'item',
//      '#title' => t('Cache exclude'),
//      '#description' => t('If you enable page caching, anonymous users might get duplicate items depending on their seed and which pages are allready cached by Drupal. If you wish to exclude paths from getting cached for anonymous users, install <a href="!url" target="_blank">Cache exclude</a>.', ['!url' => 'http://drupal.org/project/cacheexclude']),
//    ];

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $db_type = \Drupal::database()->driver();
    $seed = $this->seedCalculator->calculateSeed($this->options, $this->view->id(), $this->view->current_display, $db_type);
    switch ($db_type) {
      case 'mysql':
      case 'mysqli':
        $formula = 'RAND(' . $seed . ')';
        break;
      case 'pgsql':
        // For PgSQL we'll run an extra query with a integer between
        // 0 and 1 which will be used by the RANDOM() function.
        \Drupal::database()->query('select setseed(' . $seed . ')');
        \Drupal::database()->query("select random()");
        $formula = 'RANDOM()';
        break;
    }
    if (!empty($formula)) {
      $this->query->addOrderBy(NULL, $formula, $this->options['order'], '_' . $this->field);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    if ($this->options['user_seed_type'] === 'diff_per_user') {
      $contexts[] = 'user';
    }
    return $contexts;
  }

}

