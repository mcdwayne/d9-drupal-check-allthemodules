<?php

namespace Drupal\abtestui\Controller;

use Drupal\abtestui\Service\TestStorage;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ListController.
 *
 * @package Drupal\abtestui\Controller
 */
class ListController extends ControllerBase {

  /**
   * THe custom abtestui test storage.
   *
   * @var \Drupal\abtestui\Service\TestStorage
   */
  protected $testStorage;

  protected $renderer;

  protected $dateFormatter;

  protected $abtestuiPath;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('abtestui.test_storage'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   *
   */
  public function __construct(
    TestStorage $testStorage,
    RendererInterface $renderer,
    DateFormatterInterface $dateFormatter
  ) {
    $this->testStorage = $testStorage;
    $this->renderer = $renderer;
    $this->dateFormatter = $dateFormatter;
    $this->abtestuiPath = drupal_get_path('module', 'abtestui');
  }

  /**
   * Pre-renders the necessary parts of the list.
   *
   * @throws \Exception
   */
  public function renderTestList() {
    $list = $this->testList();

    // The original code worked by pre-rendering a lot of stuff,
    // but we need to be able to freely alter the contents.
    // @note: This is an in-between state.
    // @todo: REFACTOR ME ASAP
    /** @var array $outerRow */
    foreach ($list['table']['#rows'] as $outerRowId => $outerRow) {
      /** @var array $b */
      foreach ($outerRow as $outerGroupId => $outerGroup) {
        /** @var array $innerRow */
        foreach ($outerGroup['#rows'] as $innerRowId => $innerRow) {
          /** @var array $innedGroup */
          foreach ($innerRow as $innedGroupId => $innedGroup) {
            $list['table']['#rows'][$outerRowId][$outerGroupId]['#rows'][$innerRowId][$innedGroupId] = $this->renderer->render($innedGroup);
          }
        }
        $list['table']['#rows'][$outerRowId][$outerGroupId] = $this->renderer->render($list['table']['#rows'][$outerRowId][$outerGroupId]);
      }

    }

    return $list;
  }

  /**
   * Return the list page as a render array.
   *
   * Note: This needs some processing before it can be used.
   *
   * @return array
   *   Render array.
   *
   * @throws \LogicException
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  protected function testList() {
    $tests = $this->testStorage->loadMultiple();
    $alphabets = range('A', 'Z');

    $list = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'test-content',
        ],
      ],
    ];
    $rows = [];

    foreach ($tests as $test) {
      $test_name = [
        'test_name_col' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'test-name-col',
            ],
          ],
          'test_name' => [
            '#title' => $test['name'],
            '#type' => 'link',
            '#url' => Url::fromRoute('abtestui.test_edit_form', ['ab_test_id' => $test['tid']]),
          ],
        ],
      ];
      $base_name = [
        'base_url' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'base-url',
              'ab-variations',
            ],
          ],
          'list_counter' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'list-counter',
              ],
            ],
            'markup' => [
              '#markup' => $alphabets[0],
            ],
          ],
          'variation_name' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'variation-name',
              ],
            ],
            'markup' => [
              '#markup' => t('Base URL') . '<a href="/' . $this->abtestuiPath . '/help/base-url.html" class="help-modal"><img width="20" height="20" src="/' . $this->abtestuiPath . '/img/help.svg"></a>',
            ],
          ],
        ],
      ];

      $base_url = [
        'variation_url' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'variation-url',
              'base-url',
            ],
          ],
          'link' => [
            '#title' => $test['base_url'],
            '#type' => 'link',
            '#url' => Url::fromUri($test['base_url']),
          ],
        ],
      ];

      $i = 1;
      $inner_table_rows = [];
      $row_group = [
        $test_name,
        $base_name,
        $base_url,
      ];
      $inner_table_rows[] = $row_group;
      /** @var array $variation */
      foreach ($test['variations'] as $variation) {
        $variation_name = [];
        $prefield = [];
        $variation_name['variation_' . $i] = [
          '#type'          => 'container',
          '#attributes'    => [
            'class' => [
              'ab-variations',
            ],
          ],
          'list_counter'   => [
            '#type'       => 'container',
            '#attributes' => [
              'class' => [
                'list-counter',
              ],
            ],
            'markup'      => [
              '#markup' => "$alphabets[$i]",
            ],
          ],
          'variation_name' => [
            '#type'       => 'container',
            '#attributes' => [
              'class' => [
                'variation-name',
              ],
            ],
            'markup'      => [
              '#markup' => t('@variation_name', [
                '@variation_name' => $variation['name'],
              ]),
            ],
          ],
        ];

        $variation_url = [
          'variation_url'  => [
            '#type'       => 'container',
            '#attributes' => [
              'class' => [
                'variation-url',
              ],
            ],
            'link'        => [
              '#title' => $variation['url'],
              '#type'  => 'link',
              '#url'   => Url::fromUri($variation['url']),
            ],
          ],
        ];

        $i++;
        $row_group = [
          $prefield,
          $variation_name,
          $variation_url,
        ];
        $inner_table_rows[] = $row_group;
      }

      $inner_table = [
        '#type' => 'table',
        '#header' => NULL,
        '#rows' => $inner_table_rows,
        '#attributes' => [
          'class' => [
            'ab-test-inner-table',
          ],
        ],
      ];

      $rows[] = [
        $inner_table,
      ];
    }

    $list['info_bar'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'info-bar',
        ],
      ],
      'title' => [
        '#markup' => '<h2>' . t('A/B Testing Tool') . '</h2>',
      ],
      'ab_test_tool_help' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'ab-test-tool-help',
          ],
        ],
        'link' => [
          '#title' => t('How does this tool work?'),
          '#type' => 'link',
          '#url' => Url::fromUri('base:/' . $this->abtestuiPath . '/help/ab-test-tool-help.html'),
          '#options' => [
            'attributes' => [
              'class' => [
                'help-modal',
              ],
            ],
          ],
        ],
      ],
    ];

    $list['table'] = [
      '#type' => 'table',
      '#header' => NULL,
      '#rows' => $rows,
      '#attributes' => [
        'class' => [
          'ab-test-table',
        ],
      ],
    ];

    $list['add_bottom'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'clearfix',
          'action-bar',
          'last',
        ],
      ],
      'link' => [
        '#title' => t('+ Add new test'),
        '#type' => 'link',
        '#url' => Url::fromRoute('abtestui.test_add_form'),
        '#options' => [
          'attributes' => [
            'class' => [
              'add-test-link',
              'last',
            ],
          ],
        ],
      ],
    ];

    // Add library.
    $list['#attached']['library'][] = 'abtestui/admin_design';

    return $list;
  }

}
