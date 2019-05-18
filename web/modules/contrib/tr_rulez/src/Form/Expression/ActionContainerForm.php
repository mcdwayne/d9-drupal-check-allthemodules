<?php

namespace Drupal\tr_rulez\Form\Expression;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rules\Form\Expression\ExpressionFormTrait;
use Drupal\rules\Form\Expression\ExpressionFormInterface;
use Drupal\rules\Ui\RulesUiHandlerTrait;
use Drupal\rules\Engine\ActionExpressionContainerInterface;

/**
 * Form handler for action containers.
 */
class ActionContainerForm implements ExpressionFormInterface {

  use StringTranslationTrait;
  use RulesUiHandlerTrait;
  use ExpressionFormTrait;

  /**
   * The rule expression object this form is for.
   *
   * @var \Drupal\rules\Engine\ActionExpressionContainerInterface
   */
  protected $actionSet;

  /**
   * Creates a new object of this class.
   */
  public function __construct(ActionExpressionContainerInterface $action_set) {
    $this->actionSet = $action_set;
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
    $form['actions-table'] = [
      '#type' => 'container',
    ];

    $form['actions-table']['table'] = [
      '#theme' => 'table',
      '#header' => [$this->t('Actions'), $this->t('Operations')],
      '#empty' => t('None'),
    ];

    foreach ($this->actionSet as $action) {
      $configuration = $action->getConfiguration();
      $parameters = $this->getParameters($configuration);
      $description = $this->t('Parameters: @name-value', ['@name-value' => implode(', ', $parameters)]);
      $form['actions-table']['table']['#rows'][] = [
        'element' => [
          'data' => [
            '#type' => 'item',
            '#markup' => $action->getLabel(),
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
                  'uuid' => $action->getUuid(),
                ]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.delete', [
                  'uuid' => $action->getUuid(),
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
        'title' => $this->t('Add action'),
        'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.add', [
          'expression_id' => 'rules_action',
      ])],
    ];
    $links[] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add loop'),
        'url' => $this->getRulesUiHandler()->getUrlFromRoute('expression.add', [
          'expression_id' => 'rules_loop',
      ])],
    ];

    $form['actions-table']['table']['#footer'][] = [[
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
