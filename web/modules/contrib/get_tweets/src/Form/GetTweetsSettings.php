<?php

namespace Drupal\get_tweets\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Build Get Tweets settings form.
 */
class GetTweetsSettings extends ConfigFormBase {

  /**
   * Date formatter.
   *
   * @var \Drupal\core\datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $date_formatter) {
    parent::__construct($config_factory);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'get_tweets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['get_tweets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('get_tweets.settings');
    $queries = $config->get('queries');

    if ($queries === NULL || $form_state->get('queries')) {
      $queries = $form_state->get('queries') ? $form_state->get('queries') : [['query' => '']];
    }
    $form_state->set('queries', $queries);

    $form['import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Import tweets'),
      '#default_value' => $config->get('import'),
    ];

    $form['queries'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [$this->t('Query'), $this->t('Operations')],
      '#title' => $this->t('Search Queries'),
      '#description' => $this->t('Input your search queries here.'),
      '#prefix' => '<div id="queries-table-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($queries as $key => $value) {
      $form['queries'][$key]['query'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Query'),
        '#default_value' => $value['query'],
      ];

      $form['queries'][$key]['remove_query-' . $key] = [
        '#type' => 'submit',
        '#name' => 'remove-query-' . $key,
        '#value' => $this->t('Remove'),
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'removeCallback']],
        '#ajax' => [
          'callback' => [$this, 'queries'],
          'wrapper' => 'queries-table-wrapper',
        ],
      ];
    }

    $form['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add More'),
      '#submit' => [[$this, 'addMore']],
      '#ajax' => [
        'callback' => [$this, 'queries'],
        'wrapper' => 'queries-table-wrapper',
      ],
    ];

    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Tweets count'),
      '#default_value' => $config->get('count'),
      '#min' => 1,
      '#max' => 200,
    ];

    $intervals = [604800, 2592000, 7776000, 31536000];
    $form['expire'] = [
      '#type' => 'select',
      '#title' => $this->t('Delete old statuses'),
      '#default_value' => $config->get('expire'),
      '#options' => [0 => $this->t('Never')] + array_map([
        $this->dateFormatter,
        'formatInterval',
      ], array_combine($intervals, $intervals)),
    ];

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth Settings'),
      '#description' => $this->t('To enable OAuth based access for twitter, you must <a href="@url">register your application</a> with Twitter and add the provided keys here.', ['@url' => 'https://apps.twitter.com/apps/new']),
    ];

    $form['oauth']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer key'),
      '#default_value' => $config->get('consumer_key'),
      '#required' => TRUE,
    ];

    $form['oauth']['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth Consumer secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#required' => TRUE,
    ];

    $form['oauth']['oauth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $config->get('oauth_token'),
    ];

    $form['oauth']['oauth_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token Secret'),
      '#default_value' => $config->get('oauth_token_secret'),
    ];
    $form_state->setCached(FALSE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Callback for adding more queries.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addMore(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $queries = $form_state->get('queries');
    array_push($queries, ['query' => '']);
    $form_state->set('queries', $queries);
  }

  /**
   * Callback for remove query.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $queries = $form_state->get('queries');
    $triggered_element = $form_state->getTriggeringElement();
    unset($queries[$triggered_element['#parents'][1]]);
    $form_state->set('queries', $queries);
  }

  /**
   * Callback for queries.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Render array for queries.
   */
  public function queries(array &$form, FormStateInterface $form_state) {
    return $form['queries'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $queries = $values['queries'];

    $connection = new TwitterOAuth($values['consumer_key'], $values['consumer_secret'], $values['oauth_token'], $values['oauth_token_secret']);

    foreach ($queries as $query) {
      $query = trim($query['query']);

      if (!$query) {
        return;
      }

      if (strpos($query, '@') === 0) {
        $connection->get("statuses/user_timeline", [
          "screen_name" => $query,
          "count" => 1,
        ]);
      }
      else {
        $connection->get("search/tweets", [
          "q" => $query,
          "count" => 1,
        ]);
      }

      if (isset($connection->getLastBody()->errors)) {
        $form_state->setErrorByName('queries', $this->t('Error: "@error" on query: "@query"', [
          '@error' => $connection->getLastBody()->errors[0]->message,
          '@query' => $query,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    foreach ($values['queries'] as &$query) {
      if (strpos($query['query'], '@') === 0) {
        $query['endpoint'] = 'statuses/user_timeline';
        $query['parameter'] = 'screen_name';
      }
      else {
        $query['endpoint'] = 'search/tweets';
        $query['parameter'] = 'q';
      }
    }

    $this->config('get_tweets.settings')
      ->setData($values)
      ->save();

    drupal_set_message($this->t('Changes saved.'));
  }

}
