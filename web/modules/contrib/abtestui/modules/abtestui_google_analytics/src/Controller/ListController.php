<?php

namespace Drupal\abtestui_google_analytics\Controller;

use Drupal\abtestui\Controller\ListController as OriginalListController;
use Drupal\abtestui\Service\TestStorage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ListController.
 *
 * @package Drupal\abtestui_google_analytics\Controller
 */
class ListController extends OriginalListController {

  protected $analyticsConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('abtestui.test_storage'),
      $container->get('renderer'),
      $container->get('date.formatter'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    TestStorage $testStorage,
    RendererInterface $renderer,
    DateFormatterInterface $dateFormatter,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($testStorage, $renderer, $dateFormatter);

    $this->analyticsConfig = $configFactory->get('google_analytics.settings');
  }

  /**
   * Return the list page.
   *
   * @return array
   *   Render array.
   *
   * @throws \LogicException
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  protected function testList() {
    $output = parent::testList();
    $tests = $this->testStorage->loadMultiple();
    $testCount = 0;
    foreach ($tests as $test) {
      if (!empty($test['analytics_url'])) {
        $prefield['analitycs_url'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'test-ga-link',
            ],
          ],
          'link' => [
            '#title'  => t('Analytics Results'),
            '#type'   => 'link',
            '#url'    => Url::fromUri($test['analytics_url']),
          ],
        ];
        $output['table']['#rows'][$testCount][0]['#rows'][1][0] = $prefield;
      }
      ++$testCount;
    }

    if (empty($this->analyticsConfig->get('account'))) {
      $gaAccountId = '<a href="' . Url::fromRoute('google_analytics.admin_settings_form')->toString() . '">' . $this->t('Set up') . '</a>';
    }
    else {
      $gaAccountId = '#' . $this->analyticsConfig->get('account');
    }

    $output['info_bar']['ga_check_results'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'ga-check-results',
        ],
      ],
      'link' => [
        '#title' => t('How to check the results in Google Analytics'),
        '#type' => 'link',
        '#url' => Url::fromUri('base:/' . $this->abtestuiPath . '/help/check-ga-results.html'),
        '#options' => [
          'attributes' => [
            'class' => [
              'help-modal',
            ],
          ],
        ],
      ],
    ];

    $output['info_bar']['ga_account'] = [
      '#markup' => '<span class="ga-info"><span class="label">'
      . t('Google Analytics ID:')
      . " </span><span class='ga-value'>$gaAccountId</span></span>",
    ];

    return $output;
  }

}
