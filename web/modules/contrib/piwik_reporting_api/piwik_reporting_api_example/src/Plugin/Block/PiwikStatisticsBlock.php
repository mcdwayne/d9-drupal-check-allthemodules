<?php

namespace Drupal\piwik_reporting_api_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\piwik_reporting_api\PiwikQueryFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block containing some statistics from Piwik.
 *
 * @Block(
 *   id = "piwik_statistics",
 *   admin_label = @Translation("Piwik statistics"),
 * )
 */
class PiwikStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Piwik query factory.
   *
   * @var \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface
   */
  protected $piwikQueryFactory;

  /**
   * Constructs a new PiwikStatisticsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface $piwik_query_factory
   *   The Piwik query factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PiwikQueryFactoryInterface $piwik_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->piwikQueryFactory = $piwik_query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('piwik.query_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'count' => 10,
      'period' => 'day',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items to show'),
      '#default_value' => $this->configuration['count'],
      '#weight' => 1,
    ];

    $form['period'] = [
      '#type' => 'select',
      '#title' => $this->t('The period for which to show the statistics'),
      '#options' => [
        'day' => $this->t('Past day'),
        'week' => $this->t('Past week'),
        'month' => $this->t('Past month'),
        'year' => $this->t('Past year'),
      ],
      '#weight' => 2,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['count'] = $form_state->getValue('count');
    $this->configuration['period'] = $form_state->getValue('period');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = [];

    // Get the list of most popular pages from the Piwik reporting API.
    // @see https://developer.piwik.org/api-reference/reporting-api#Actions
    $query = $this->piwikQueryFactory->getQuery('Actions.getPageUrls');
    $query->setParameters([
      'filter_limit' => $this->configuration['count'],
      'period' => $this->configuration['period'],
      'showColumns' => 'url,nb_visits',
      'date' => 'today',
      'flat' => 1,
    ]);
    $response = $query->execute()->getResponse();

    foreach ($response as $row) {
      $url = $row->label === 'index' ? '/' : '/' . ltrim($row->label, '/');
      $items[] = [
        '#type' => 'link',
        '#title' => $this->t("@label (@visits visits)", [
          '@label' => $url,
          '@visits' => $row->nb_visits,
        ]),
        '#url' => Url::fromUserInput($url),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#list_type' => 'ol',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Cache the block for 1 hour.
    return 60 * 60;
  }

}
