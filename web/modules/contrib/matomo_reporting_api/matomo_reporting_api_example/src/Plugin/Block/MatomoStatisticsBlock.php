<?php

namespace Drupal\matomo_reporting_api_example\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\matomo_reporting_api\MatomoQueryFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block containing some statistics from Matomo.
 *
 * @Block(
 *   id = "matomo_statistics",
 *   admin_label = @Translation("Matomo statistics"),
 * )
 */
class MatomoStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Matomo query factory.
   *
   * @var \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface
   */
  protected $matomoQueryFactory;

  /**
   * Constructs a new MatomoStatisticsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface $matomo_query_factory
   *   The Matomo query factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MatomoQueryFactoryInterface $matomo_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->matomoQueryFactory = $matomo_query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('matomo.query_factory')
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

    // Get the list of most popular pages from the Matomo reporting API.
    // @see https://developer.matomo.org/api-reference/reporting-api#Actions
    $query = $this->matomoQueryFactory->getQuery('Actions.getPageUrls');
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
