<?php

namespace Drupal\business_rules\Form;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\Entity\Action;
use Drupal\business_rules\Entity\BusinessRule;
use Drupal\business_rules\Entity\Condition;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BusinessRuleForm.
 *
 * @package Drupal\business_rules\Form
 */
class BusinessRuleForm extends EntityForm {

  /**
   * The business rules Util.
   *
   * @var \Drupal\business_rules\Util\BusinessRulesUtil
   */
  public $util;

  /**
   * The reactsOnManager.
   *
   * @var \Drupal\business_rules\Plugin\BusinessRulesReactsOnManager
   */
  protected $reactsOnManager;

  /**
   * The form step.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * The Business Rule flowchart.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Flowchart
   */
  private $chart;

  /**
   * We don't want to use the same wait two times for an item.
   *
   * @var array
   */
  private $usedWeight = [];

  /**
   * BusinessRuleForm constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The services container.
   */
  public function __construct(ContainerInterface $container) {
    $this->util              = $container->get('business_rules.util');
    $this->entityTypeManager = $container->get('entity_type.manager');
    $this->reactsOnManager   = $container->get('plugin.manager.business_rules.reacts_on');
    $this->chart             = $container->get('business_rules.flowchart');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\business_rules\Entity\BusinessRule $business_rule */
    $business_rule = $this->entity;

    if ($this->step === 1 && $business_rule->isNew()) {
      $form['reacts_on'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Reacts on event'),
        '#description'   => $this->t('Whenever the event occurs, rule evaluation is triggered.'),
        '#options'       => BusinessRule::getEventTypes(),
        '#default_value' => $business_rule->getReactsOn(),
        '#required'      => TRUE,
      ];
    }

    if ($this->step > 1 || !$business_rule->isNew()) {

      $reactsOn    = $business_rule->getReactsOn() ? $business_rule->getReactsOn() : $form_state->getValue('reacts_on');
      $definition  = $this->reactsOnManager->getDefinition($reactsOn);
      $reflection  = new \ReflectionClass($definition['class']);
      $custom_rule = $reflection->newInstance($definition, $definition['id'], $definition);

      $form['label_reacts_on'] = [
        '#type'        => 'item',
        '#title'       => $this->t('Reacts on event'),
        '#markup'      => $definition['label'],
        '#description' => $definition['description'],
      ];

      $form['label'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Label'),
        '#maxlength'     => 255,
        '#default_value' => $business_rule->label(),
        '#description'   => $this->t("Label for the Rule."),
        '#required'      => TRUE,
      ];

      $form['id'] = [
        '#type'          => 'machine_name',
        '#default_value' => $business_rule->id(),
        '#machine_name'  => [
          'exists' => '\Drupal\business_rules\Entity\BusinessRule::load',
        ],
        '#disabled'      => !$business_rule->isNew(),
      ];

      $form['description'] = [
        '#type'          => 'textarea',
        '#title'         => $this->t('Description'),
        '#description'   => $this->t('A good description for Business Rule.'),
        '#required'      => TRUE,
        '#default_value' => $business_rule->getDescription(),
      ];

      $form['tags'] = [
        '#type'                          => 'textfield',
        '#title'                         => $this->t('Tags'),
        '#default_value'                 => implode(', ', $business_rule->getTags()),
        '#description'                   => $this->t('List of comma-separated tags.'),
        '#required'                      => FALSE,
        '#autocomplete_route_name'       => 'business_rules.autocomplete_tags',
        '#autocomplete_route_parameters' => [],

      ];

      $form['status'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enabled'),
        '#default_value' => $business_rule->isNew() ? 1 : $business_rule->isEnabled(),
      ];

      $form['entity'] = $this->getEntityInformationForm($definition);

      // Only show items if rule is already saved.
      if (!$business_rule->isNew()) {

        $form['items_container'] = [
          '#type'        => 'details',
          '#description' => $this->t('The items are evaluated on the presented order. Drag them to change the order.'),
          '#title'       => $this->t('Items'),
          '#open'        => TRUE,
        ];

        $form['items_container']['items'] = $this->formItems($form, $form_state);

        if (!$business_rule->isNew()) {
          $form['flowchart'] = [
            '#type'  => 'details',
            '#title' => $this->t('Flowchart'),
            '#open'  => TRUE,
          ];

          $form['flowchart']['graph']     = $this->chart->getGraph($business_rule);
          $form['#attached']['library'][] = 'business_rules/mxClient';
        }

      }

      // Process the form array by the Plugin.
      $custom_rule->processForm($form, $form_state);

    }

    $form['#validate'][] = '::validateForm';

    return $form;
  }

  /**
   * Get the fields for entity type and bundle.
   *
   * @param array $rule_definition
   *   The rule definition.
   *
   * @return array
   *   The render array.
   */
  public function getEntityInformationForm(array $rule_definition) {

    $form = [];

    /** @var \Drupal\business_rules\BusinessRuleInterface $rule */
    $rule = $this->entity;

    $show_entity = FALSE;
    $show_bundle = FALSE;

    if ($rule_definition['hasTargetBundle']) {
      $show_entity = TRUE;
      $show_bundle = TRUE;
    }
    elseif ($rule_definition['hasTargetEntity']) {
      $show_entity = TRUE;
    }

    if ($show_entity) {
      $form['context'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Context: This information cannot be changed after the business rule is saved.'),
      ];

      $form['context']['target_entity_type'] = [
        '#type'          => 'select',
        '#options'       => $this->util->getEntityTypes(),
        '#required'      => TRUE,
        '#title'         => $this->t('Target Entity Type'),
        '#description'   => $this->t('The Entity Type which this business rule is applicable.'),
        '#default_value' => $rule->getTargetEntityType(),
        '#disabled'      => $this->entity->isNew() ? FALSE : TRUE,
      ];
    }

    if ($show_bundle) {
      $form['context']['target_entity_type']['#ajax'] = [
        'callback' => [
          $this,
          'targetEntityTypeCallback',
        ],
      ];

      $form['context']['target_bundle'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Target Bundle'),
        '#description'   => $this->t('The Bundle which this business rule is applicable.'),
        '#options'       => $this->util->getBundles($rule->getTargetEntityType()),
        '#required'      => TRUE,
        '#default_value' => $rule->getTargetBundle(),
        '#disabled'      => $this->entity->isNew() ? FALSE : TRUE,
        '#prefix'        => '<div id="target_bundle-wrapper">',
        '#suffix'        => '</div>',
      ];
    }

    return $form;
  }

  /**
   * Provide the form fields for add Business Rule's Items.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The render array.
   */
  public function formItems(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    /** @var \Drupal\business_rules\Entity\BusinessRule $rule */
    $rule = $this->entity;

    $label        = $this->t('Item');
    $label_plural = $this->t('Items');
    $items        = $rule->getItems();

    uasort($items, function ($a, $b) {
      return $a->getWeight() < $b->getWeight() ? -1 : 1;
    });

    $header = [
      'type'                    => $this->t('Type'),
      'label'                   => $label,
      'weight'                  => $this->t('Weight'),
      'id'                      => $this->t('Machine name'),
      'subtype'                 => $this->t('Subtype'),
      'description'             => $this->t('Description'),
      'operations'              => $this->t('Operations'),
      'business_rule_item_type' => [
        'data'  => '',
        'width' => '0px',
      ],
    ];

    $table['items'] = [
      '#type'       => 'table',
      '#header'     => $header,
      '#attributes' => ['id' => 'business_rules-items'],
      '#empty'      => $this->t('There are currently no @label in this rule. Add one by selecting an option below.', ['@label' => $label_plural]),
      '#tabledrag'  => [
        [
          'action'       => 'order',
          'relationship' => 'sibling',
          'group'        => 'items-order-weight',
        ],
      ],
    ];

    if (is_array($items)) {
      foreach ($items as $value) {

        if ($value->getType() == 'condition') {
          $item = Condition::load($value->getId());
        }
        elseif ($value->getType() == 'action') {
          $item = Action::load($value->getId());
        }

        $item_weight       = !empty($item) ? $value->getWeight() : '';
        $route_remove_item = 'business_rules.items.remove';

        if (!empty($item)) {

          $key         = $item->id();
          $listBuilder = $this->entityTypeManager->getListBuilder($item->getEntityTypeId());
          $operations  = $listBuilder->buildOperations($item);

          $operations['#links']['remove'] = [
            'title'  => $this->t('Remove'),
            'url'    => Url::fromRoute($route_remove_item, [
              'business_rule' => $rule->id(),
              'item_type'     => $value->getType(),
              'item_id'       => $item->id(),
              'method'        => 'nojs',
            ],
              [
                'attributes' => [
                  'class' => ['use-ajax'],
                ],
              ]
            ),
            'weight' => 1,
          ];
          uasort($operations['#links'], function ($a, $b) {
            return $a['weight'] < $b['weight'] ? -1 : 1;
          });
          foreach ($operations['#links'] as $i => $link) {
            $uri = $this->util->getCurrentUri()->toString();
            $operations['#links'][$i]['url']->setRouteParameter('destination', $uri);
          }

          $item_weight = $this->generateItemWeight('item', $item_weight);

          $weight = [
            '#type'          => 'weight',
            '#title'         => $this->t('Weight for item'),
            '#title_display' => 'invisible',
            '#default_value' => $item_weight,
            '#delta'         => 100,
            '#attributes'    => [
              'class' => ['items-order-weight'],
            ],
          ];

          $table['items'][$key] = [
            '#attributes'             => [
              'class' => 'draggable',
              'id'    => $item->id(),
            ],
            '#weight'                 => isset($user_input['effects']) ? $user_input['effects'][$key]['weight'] : NULL,
            'type'                    => ['#markup' => $item->getBusinessRuleItemTranslatedType()],
            'item'                    => [
              '#tree' => FALSE,
              'label' => ['#markup' => $item->label()],
            ],
            'weight'                  => $weight,
            'id'                      => ['#markup' => $item->id()],
            'subtype'                 => ['#markup' => $item->getTypeLabel()],
            'description'             => ['#markup' => $item->getDescription()],
            'operations'              => $operations,
            'business_rule_item_type' => [
              '#type'  => 'value',
              '#value' => $value->getType(),
            ],
          ];

        }
      }
    }

    $add_condition = Link::createFromRoute($this->t('Add Condition'), 'business_rules.items.table', [
      'business_rule' => $rule->id(),
      'item_type'     => 'condition',
      'method'        => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);
    $add_action    = Link::createFromRoute($this->t('Add Action'), 'business_rules.items.table', [
      'business_rule' => $rule->id(),
      'item_type'     => 'action',
      'method'        => 'nojs',
    ], ['attributes' => ['class' => ['use-ajax']]]);

    $table['add'][] = [
      'data' => [
        'add' => [
          '#type'   => 'markup',
          '#markup' => $add_condition->toString() . ' | ' . $add_action->toString(),
          '#prefix' => '<div id="business_rule-add_buttons">',
          '#suffix' => '</div>',
        ],
      ],
    ];

    return $table;
  }

  /**
   * Generate the item weight.
   *
   * @param string $settings_type
   *   The settings type.
   * @param int $weight
   *   The item weight.
   *
   * @return int
   *   The generated weight.
   */
  private function generateItemWeight($settings_type, $weight) {

    if (!isset($this->usedWeight[$settings_type])) {
      $this->usedWeight[$settings_type][] = $weight;

      return $weight;
    }

    if (!in_array($weight, $this->usedWeight[$settings_type])) {
      $this->usedWeight[$settings_type][] = $weight;

      return $weight;
    }
    else {
      $weight++;

      return $this->generateItemWeight($settings_type, $weight);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $actions = parent::actionsElement($form, $form_state);

    if (!$this->entity->isNew()) {
      $actions['done'] = [
        '#type'     => 'submit',
        '#value'    => $this->t('Done'),
        '#submit'   => ['::submitForm', '::save'],
        '#op'       => 'done',
        '#weight'   => 7,
        '#validate' => ['::validateForm'],
      ];
    }
    elseif ($this->step === 1) {
      $actions['submit']['#value'] = $this->t('Continue');
    }

    $actions['submit']['#op'] = 'save';

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($this->step < 2 && $this->entity->isNew()) {
      $this->step++;
      $form_state->setRebuild();

      return $form;
    }
    else {
      /** @var \Drupal\business_rules\Entity\BusinessRule $business_rule */
      $business_rule = $this->entity;

      $items = $form_state->getValue('items');
      $business_rule->set('items', []);
      $br_items = [];
      if (is_array($items)) {
        foreach ($items as $key => $value) {
          $itemObj                    = new BusinessRulesItemObject($key, $value['business_rule_item_type'], $value['weight']);
          $br_items[$value['weight']] = $itemObj;
        }
      }

      ksort($br_items);
      foreach ($br_items as $item) {
        $business_rule->addItem($item);
      }

      $business_rule->setTags(explode(',', $form_state->getValue('tags')));
      $status = $business_rule->save();
      // As the rule may need to be executed under a cached hook, we need to
      // invalidate all rendered caches.
      Cache::invalidateTags(['rendered']);

      switch ($status) {
        case SAVED_NEW:
          drupal_set_message($this->t('Created the %label Rule.', [
            '%label' => $business_rule->label(),
          ]));
          break;

        default:
          drupal_set_message($this->t('Saved the %label Rule.', [
            '%label' => $business_rule->label(),
          ]));
      }

      if (isset($form_state->getTriggeringElement()['#op'])) {
        $op = $form_state->getTriggeringElement()['#op'];

        if ($op == 'save') {
          $form_state->setRedirectUrl($business_rule->urlInfo('edit-form', ['business_rule' => $business_rule->id()]));
        }
        else {
          $form_state->setRedirectUrl($business_rule->urlInfo('collection'));
        }
      }
    }

    return $status;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Validate de Business Rule Machine Name.
    $id = $form_state->getValue('id');
    if ($id && $this->entity->isNew()) {
      $br = BusinessRule::load($id);
      if (!empty($br)) {
        $form_state->setErrorByName('id', $this->t('The machine-readable name is already in use. It must be unique.'));
      }
    }
  }

  /**
   * Populates target_bundle options according to the selected entity type.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AjaxResponse.
   */
  public function targetEntityTypeCallback(array &$form, FormStateInterface $form_state) {
    $target_entity_type = $form_state->getValue('target_entity_type');
    if (!empty($target_entity_type)) {
      $form['entity']['context']['target_bundle']['#options'] = $this->util->getBundles($target_entity_type);
    }

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#target_bundle-wrapper', $form['entity']['context']['target_bundle']));

    return $response;
  }

}
