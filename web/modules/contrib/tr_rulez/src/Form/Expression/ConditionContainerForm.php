<?php

namespace Drupal\tr_rulez\Form\Expression;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rules\Form\Expression\ExpressionFormTrait;
use Drupal\rules\Form\Expression\ExpressionFormInterface;
use Drupal\rules\Ui\RulesUiHandlerTrait;
use Drupal\rules\Engine\ConditionExpressionContainerInterface;

/**
 * Form view structure for Rules condition containers.
 */
class ConditionContainerForm implements ExpressionFormInterface {

  use ExpressionFormTrait;
  use RulesUiHandlerTrait;
  use StringTranslationTrait;

  /**
   * The rule expression object this form is for.
   *
   * @var \Drupal\rules\Engine\ConditionExpressionContainerInterface
   */
  protected $conditionContainer;

  /**
   * Creates a new object of this class.
   */
  public function __construct(ConditionExpressionContainerInterface $condition_container) {
    $this->conditionContainer = $condition_container;
  }

  /**
   * Helper function to extract context parameter names/values from the config.
   *
   * @param array $configuration
   *   Configuration entity as a configuration array.
   *
   * @return array
   *   Associative array of context parameter values, keyed by parameter name.
   */
  protected function getParameters(array $configuration) {
    $parameters = [];
    // 'context_mapping' is for context parameters set in data selector mode.
    // 'context_values' is for context parameters set in direct input mode.
    $context = $configuration['context_mapping'] + $configuration['context_values'];
    foreach ($context as $key => $value) {
      if ($value === FALSE) {
        $value = 'FALSE';
      }
      elseif ($value === TRUE) {
        $value = 'TRUE';
      }
      elseif (is_array($value)) {
        $value = '[' . implode(', ', $value) . ']';
      }
      $parameters[] = $key . ': ' . $value;
    }
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['conditions'] = [
      '#type' => 'container',
    ];

    $form['conditions']['table'] = [
      '#theme' => 'table',
      '#header' => [$this->t('Conditions'), $this->t('Operations')],
      '#empty' => t('None'),
    ];

    foreach ($this->conditionContainer as $condition) {
      $configuration = $condition->getConfiguration();
      $parameters = $this->getParameters($configuration);
      $description = $this->t('Parameters: @name-value', ['@name-value' => implode(', ', $parameters)]);

      $form['conditions']['table']['#rows'][] = [
        'element' => [
          'data' => [
            '#type' => 'item',
            '#markup' => $condition->getLabel(),
            '#suffix' => '<div class="description">' . $description . '</div>',
          ],
          'title' => $description,
        ],
        'operations' => [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.edit', [
                  'uuid' => $condition->getUuid(),
                ]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.delete', [
                  'uuid' => $condition->getUuid(),
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    // @todo Put this into the table as last row - DONE! - and style it like it
    // was in Drupal 7 Rules - WHY?
    $links[] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add condition'),
        'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.add', [
          'expression_id' => 'rules_condition',
      ])],
    ];
    $links[] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add or'),
        'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.add', [
          'expression_id' => 'rules_or',
      ])],
    ];
    $links[] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add and'),
        'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.add', [
          'expression_id' => 'rules_and',
        ]),
        'localized_options' => ['set_active_class' => FALSE],
      ],
    ];

    $form['conditions']['table']['#footer'][] = [[
      'data' => [
        '#prefix' => '<ul class="action-links">',
        $links,
        '#suffix' => '</ul>',
      ],
      'colspan' => 2,
    ]];

    return $form;
  }

}
