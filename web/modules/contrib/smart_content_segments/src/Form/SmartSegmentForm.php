<?php

namespace Drupal\smart_content_segments\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\ConditionManager;
use Drupal\smart_content\Entity\SmartVariationSet;
use Drupal\smart_content\Form\SmartVariationSetForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\smart_content\Condition\ConditionInterface;

/**
 * Class SmartSegmentForm.
 */
class SmartSegmentForm extends EntityForm {

  /**
   * Condition manager.
   *
   * @var \Drupal\smart_content\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Conditions.
   *
   * @var array
   */
  protected $conditions;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConditionManager $condition_manager) {
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('plugin.manager.smart_content.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#process'][] = [$this, 'buildWidget'];
    return $form;
  }



  /**
   * Render API callback: builds the formatter settings elements.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($smart_segment = $form_state->get('variation_set')) {
      $this->entity = $smart_segment;
    }
    $element = parent::form($element, $form_state);

    $smart_segment = $this->entity;

    $element['#prefix'] = '<div class="variations-container variation-container">';
    $element['#suffix'] = '</div>';
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $smart_segment->label(),
      '#required' => TRUE,
    ];

    $element['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $smart_segment->id(),
      '#machine_name' => [
        'exists' => '\Drupal\smart_content_segments\Entity\SmartSegment::load',
      ],
      '#disabled' => !$smart_segment->isNew(),
    ];

    // @todo: below was copied from Drupal\smart_content\Plugin\smart_content\Variation/VariationStandard, needs to be completed.
    // @todo: segments will be combinations of conditions, we should make sure we avoid recursion. maybe we should not allow segments to set other segments as conditions?
    $wrapper_id = 'segment-form';
    $wrapper_items_id = $wrapper_id . 'conditions';
    $element['conditions_config'] = [
      '#type' => 'container',
      '#title' => 'Conditions',
      '#prefix' => '<div id="' . $wrapper_id . '-conditions" class="conditions-container segment-conditions-container">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $element['conditions_config']['condition_items'] = [
      '#type' => 'table',
      '#header' => [t('Condition(s)'), t('Weight'), ''],
      '#prefix' => '<div id="' . $wrapper_items_id . '-conditions" class="conditions-container-items segment-conditions-container-items">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $wrapper_items_id . '-order-weight',
        ],
      ],
    ];

    // @todo: add draggable weights
    foreach ($smart_segment->getConditions() as $condition_id => $condition) {
      if ($condition instanceof PluginFormInterface) {
        $key = $condition_id;
        $plugin_id = $condition->getPluginId();
        SmartVariationSetForm::pluginForm($condition, $element, $form_state, [
          'conditions_config',
          'condition_items',
          $key,
          'condition_type_settings',
        ]);

        $element['conditions_config']['condition_items'][$key]['condition_type_settings']['#type'] = 'container';
        $element['conditions_config']['condition_items'][$key]['condition_type_settings']['#title'] = $condition->getPluginId();
        $element['conditions_config']['condition_items'][$key]['condition_type_settings']['#attributes']['class'][] = 'condition';
        $element['conditions_config']['condition_items'][$key]['#weight'] = $condition->getWeight();

        $element['conditions_config']['condition_items'][$key]['#attributes']['class'][] = 'draggable';
        $element['conditions_config']['condition_items'][$key]['#attributes']['class'][] = 'row-condition';

        $element['conditions_config']['condition_items'][$key]['weight'] = [
          '#type' => 'weight',
          '#title' => 'Weight',
          '#title_display' => 'invisible',
          '#default_value' => $condition->getWeight(),
          // Classify the weight element for #tabledrag.
          '#attributes' => ['class' => [$wrapper_items_id . '-order-weight']],
        ];

        $element['conditions_config']['condition_items'][$key]['remove_condition'] = [
          '#type' => 'submit',
          '#value' => t('Remove Condition'),
          '#name' => 'remove_condition_segment__' . $condition_id,
          '#submit' => [[$this, 'removeElementCondition']],
          '#attributes' => ['class' => ['align-right']],
          '#ajax' => [
            'callback' => [$this, 'removeElementConditionAjax'],
            'wrapper' => $wrapper_id . '-conditions',
          ],
          '#limit_validation_errors' => [],
        ];
      }
    }

    //@todo: Remove ability to add self.
    $element['conditions_config']['add_condition'] = [
      '#type' => 'container',
      '#title' => 'Add Condition',
      '#attributes' => ['class' => ['condition-add-container']],
    ];
    $element['conditions_config']['add_condition']['condition_type'] = [
      '#title' => 'Condition Type',
      '#title_display' => 'invisible',
      '#type' => 'select',
      '#options' => $this->conditionManager->getFormOptions(),
      '#empty_value' => '',
    ];
    $element['conditions_config']['add_condition']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Add Condition'),
      '#validate' => [[$this, 'addElementConditionValidate']],
      '#submit' => [[$this, 'addElementCondition']],
      '#name' => 'add_condition',
      '#ajax' => [
        'callback' => [$this, 'addElementConditionAjax'],
        'wrapper' => $wrapper_id . '-conditions',
      ],
      '#limit_validation_errors' => [['conditions_config', 'add_condition', 'condition_type']
      ],
    ];

    $element['#attached']['library'][] = 'smart_content/form';

    return $element;
  }

  /**
   * Sets weight.
   *
   * @param string $weight
   *   Weight.
   */
  public function setWeight($weight) {
    $configuration = $this->getConfiguration();
    $configuration['weight'] = $weight;
    $this->setConfiguration($configuration);
  }

  /**
   * Gets weight.
   *
   * @return mixed
   *   Weight.
   */
  public function getWeight() {
    return isset($this->configuration['weight']) ? $this->configuration['weight'] : 0;
  }


  /**
   * Attach weights.
   *
   * @param array $values
   *   Values.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function attachTableConditionWeight(array $values) {
    // @todo: determine if there is a better way to do this. copy better way to block form.
    foreach ($this->entity->getConditions() as $condition) {
      if (isset($values[$condition->id()]['weight'])) {
        $condition->setWeight($values[$condition->id()]['weight']);
      }
    }
    $this->entity->sortConditions();
  }

  /**
   * Validate condition element.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function addElementConditionValidate(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#array_parents'], 0, -1);
    $parents[] = 'condition_type';
    if (!$value = NestedArray::getValue($form_state->getUserInput(), $parents)) {
      $form_state->setError(NestedArray::getValue($form, $parents), 'Condition type required.');
    }
  }

  /**
   * Adds condition element.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function addElementCondition(array &$form, FormStateInterface $form_state) {
    // @todo: reorder conditions to account for drupal core issue.
    $button = $form_state->getTriggeringElement();
    // Save condition weight.
    $condition_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#array_parents'], 0, -2));
    if (isset($condition_values['condition_items'])) {
      $this->attachTableConditionWeight($condition_values['condition_items']);
    }

    $type = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#array_parents'], 0, -1))['condition_type'];

    $this->entity->addCondition($this->conditionManager->createInstance($type));
    $form_state->set('variation_set', $this->entity);
    $form_state->setRebuild();
  }

  /**
   * Ajax submit callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Form elements.
   */
  public function addElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -2));
  }


  /**
   * Remove condition submit handler.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function removeElementCondition(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();


    list($action, $name) = explode('__', $button['#name']);

    // Save condition weight.
    $condition_values = NestedArray::getValue($form_state->getUserInput(), array_slice($button['#array_parents'], 0, -3));
    if (isset($condition_values['condition_items'])) {
      $this->attachTableConditionWeight($condition_values['condition_items']);
    }

    $this->entity->removeCondition($name);

    $form_state->set('variation_set', $this->entity);

    $form_state->setRebuild();
  }

  /**
   * Remove condition ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Form elements.
   */
  public function removeElementConditionAjax(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -3));
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if (($condition_config = $form_state->getValue('conditions_config')) && !empty($condition_config['condition_items'])) {
      $this->attachTableConditionWeight($condition_config['condition_items']);
    }
    foreach ($this->entity->getConditions() as $condition_id => $condition) {
      SmartVariationSetForm::pluginFormSubmit($condition, $form, $form_state, [
        'conditions_config',
        'condition_items',
        $condition_id,
        'condition_type_settings',
      ]);
    }
    $status = $this->entity->save();

    $entity = \Drupal::entityTypeManager()
      ->getStorage('smart_segment')->load($this->entity->id());
    $form_state->set('variation_set', $entity);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Smart segment.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Smart segment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

}
