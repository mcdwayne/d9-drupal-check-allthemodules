<?php

namespace Drupal\request_data_conditions\Plugin\Condition;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BaseCondition
 *
 * @package Drupal\request_data_conditions\Plugin\Condition
 */
abstract class BaseCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface
{

  const OP_EQUALS = 'equals';
  const OP_NOT_EQUALS = 'not_equals';
  const OP_EMPTY = 'empty';
  const OP_NOT_EMPTY = 'not_empty';
  const OP_SET = 'set';
  const OP_NOT_SET = 'not_set';
  const OP_REGEX = 'regex';

  /**
   * @var RequestStack
   */
  protected $requestStack;

  /**
   * @var RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * BaseCondition constructor.
   * @param RequestStack $request_stack
   * @param RouteMatchInterface $current_route_match
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   */
  public function __construct(RequestStack $request_stack, RouteMatchInterface $current_route_match, array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Get a key-value array providing context to check conditions against.
   *
   * @return array
   */
  abstract protected function getDataContext();

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $plugin_id = $this->getPluginId();
    $wrapper_key = $this->getFormWrapperKey();
    $num_conditions = 1;

    // Determine the number of conditions we currently have for this plugin. If
    // the sub-form has been submitted, i.e. not the main form which saves the
    // config, we need to get this from the form state. Otherwise it's a fresh
    // build and we can rely on configuration.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      $parents = $this->getConditionsParents($triggering_element);

      $val = $form_state->getValue($parents);
      if (is_array($val)) {
        $conditions = $this->removeEmptyConditions($val);
        $num_conditions = count($conditions) + 1;
      }
    } else {
      $num_conditions = count($this->configuration['conditions']) + 1;
    }

    $html_id = Html::getUniqueId("$plugin_id-condition-table");
    $form[$wrapper_key] = [
      '#type' => 'container',
      '#prefix' => '<div id="' . $html_id . '">',
      '#suffix' => '</div>',
    ];

    $form[$wrapper_key]['conditions'] = [
      '#type' => 'table',
      '#header' => ['Name', 'Operator', 'Value'],
    ];

    // Add the table rows.
    for ($delta = 0; $delta < $num_conditions; $delta++) {
      $form[$wrapper_key]['conditions'][$delta] = $this->buildRow($delta);
    }

    $delta = $num_conditions - 1;
    $input_html_id = "$plugin_id-condition-$delta-input-name";
    $selector = "#$input_html_id";
    $form[$wrapper_key]['add_another'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another'),
      '#submit' => [[$this, 'submitAddAnother']],
      '#ajax' => [
        'wrapper' => $html_id,
        'callback' => [$this, 'rebuildTableAjaxCallback'],
      ],
      '#name' => "request-data-condition-add-$plugin_id",
      '#states' => [
        'disabled' => [
          $selector => [
            ['value' => ''],
          ],
        ],
      ],
    ];

    // The context edit form doesn't use form API AJAX in the traditional way,
    // it uses a callback URL to add the condition and generate the resulting
    // config form which it returns and injects in the page. Unfortunately this
    // means that the AJAX callback here is bound to that URL, which doesn't
    // accept the form submission (and generates an "condition already exists"
    // error). We need to correct the URL for the initial AJAX submission,
    // otherwise the add another functionality won't work until the page is
    // reloaded.
    if ($this->currentRouteMatch->getRouteName() == 'context.condition_add') {
      $context_id = $this->currentRouteMatch
        ->getParameter('context')
        ->id();

      $form[$wrapper_key]['add_another']['#ajax'] += [
        'url' => Url::fromRoute('entity.context.edit_form', [
          'context' => $context_id,
        ]),
        'options' => [
          'query' => [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
          ],
        ],
      ];
    }

    $form[$wrapper_key]['require_all_params'] = array(
      '#type' => 'checkbox',
      '#title' => t('Require all'),
      '#default_value' => $this->configuration['require_all_params'],
    );

    $form_state->setCached(FALSE);

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Build a single table row of elements.
   *
   * @param $delta
   * @return array
   */
  protected function buildRow($delta)
  {
    $existing = isset($this->configuration['conditions'][$delta]) ? $this->configuration['conditions'][$delta] : NULL;
    $plugin_id = $this->getPluginId();

    $row = [
      '#type' => 'container',
    ];

    $val = isset($existing) ? $existing['name'] : '';
    $html_id = "$plugin_id-condition-$delta-input-name";
    $row['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#title_display' => 'invisible',
      '#default_value' => $val,
      // Quick hack to fix layout issues caused by the size attribute
      // overriding max-width (in at least webkit and gecko).
      '#attributes' => [
        'style' => 'width:100%',
        'id' => $html_id,
      ],
    ];

    $val = isset($existing) ? $existing['op'] : '';

    $html_id = "$plugin_id-condition-$delta-input-op";
    $row['op'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#title_display' => 'invisible',
      '#default_value' => $val,
      '#options' => [
        self::OP_EQUALS => $this->t('must equal'),
        self::OP_NOT_EQUALS => $this->t('must not equal'),
        self::OP_SET => $this->t('must be set'),
        self::OP_NOT_EMPTY => $this->t('must be set and have any value'),
        self::OP_EMPTY => $this->t('must be set and have no value'),
        self::OP_NOT_SET => $this->t('must not be set'),
        self::OP_REGEX => $this->t('matches regular expression'),
      ],
      '#attributes' => [
        'id' => $html_id,
      ],
    ];

    $selector = "#$html_id";

    $val = $existing ? $existing['value'] : '';
    $row['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#title_display' => 'invisible',
      '#default_value' => $val,
      '#attributes' => ['style' => 'width:100%'],
      '#states' => [
        'disabled' => [
          $selector => [
            ['value' => self::OP_SET],
            ['value' => self::OP_NOT_SET],
            ['value' => self::OP_EMPTY],
            ['value' => self::OP_NOT_EMPTY],
          ],
        ],
      ],
    ];

    return $row;
  }

  /**
   * Submit handler for adding another condition.
   *
   * @param $form
   * @param FormStateInterface $form_state
   */
  public function submitAddAnother($form, FormStateInterface $form_state)
  {
    $this->removeEmptyRows($form_state);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for adding a new condition.
   *
   * @param $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function rebuildTableAjaxCallback($form, FormStateInterface $form_state)
  {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Remove empty condition rows from this plugin's form state.
   *
   * @param FormStateInterface $form_state
   */
  protected function removeEmptyRows(FormStateInterface $form_state)
  {
    $parents = $this->getConditionsParents($form_state->getTriggeringElement());
    $val = $form_state->getValue($parents);

    if (is_array($val)) {
      $conditions = array_values($this->removeEmptyConditions($val));
      $form_state->setValue($parents, $conditions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    $this->configuration['conditions'] = [];

    foreach ($form_state->getValue($this->getFormWrapperKey())['conditions'] as $condition) {
      if (!$this->conditionIsEmpty($condition)) {
        $this->configuration['conditions'][] = [
          'name' => $condition['name'],
          'op' => $condition['op'],
          'value' => $condition['value'],
        ];
      }
    }

    $this->configuration['require_all_params'] = $form_state->getValue($this->getFormWrapperKey())['require_all_params'];

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Remove empty items from an array of conditions.
   *
   * @param array $conditions
   * @return array
   */
  protected function removeEmptyConditions(array $conditions)
  {
    $clean_conditions = [];
    foreach ($conditions as $condition) {
      if (!$this->conditionIsEmpty($condition)) {
        $clean_conditions[] = $condition;
      }
    }
    return $clean_conditions;
  }

  /**
   * Get an array of parents from a triggering element.
   *
   * Conditions can be used in a variety of forms so we need an automated method
   * to determine where we are.
   *
   * @param $triggering_element
   * @return 0|array
   */
  protected function getSubFormParents($triggering_element)
  {
    return array_slice($triggering_element['#parents'], 0, -3);
  }

  /**
   * Get the parent array leading to the conditions table element.
   *
   * @param $triggering_element
   * @return array
   */
  protected function getConditionsParents($triggering_element)
  {
    $parents = $this->getSubFormParents($triggering_element);
    $parents[] = $this->getPluginId();
    $parents[] = $this->getFormWrapperKey();
    $parents[] = 'conditions';

    return $parents;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate()
  {
    if (empty($this->configuration['conditions']) && !$this->isNegated()) {
      return TRUE;
    }

    // Get data from config.
    $conditions = $this->configuration['conditions'];
    $require_all = $this->configuration['require_all_params'];

    // Run through the conditions and count the number of passes.
    $passes = 0;
    $context = $this->getDataContext();
    foreach ($conditions as $condition) {
      if ($this->conditionPasses($condition, $context)) {
        $passes++;
      }
    }

    // If we require all conditions to have passed, count them up.
    $condition_count = count($conditions);
    return $require_all ?
      $condition_count && $passes === $condition_count :
      $passes > 0;
  }

  /**
   * Determine whether a condition is empty.
   *
   * @param array $condition
   * @return bool
   */
  protected function conditionIsEmpty(array $condition)
  {
    return !Unicode::strtolower($condition['name']);
  }

  /**
   * Check a condition passes against a given context.
   *
   * @param array $condition
   * @param array $context
   * @return bool
   */
  protected function conditionPasses(array $condition, array $context)
  {
    switch ($condition['op']) {
      // Parameter equals given value.
      case self::OP_EQUALS:
        $check = isset($context[$condition['name']]) ?
          $context[$condition['name']] :
          NULL;

        return is_array($check) ? in_array($condition['value'], $check) : $check == $condition['value'];
        break;

      // Parameter does not equal given value.
      case self::OP_NOT_EQUALS:
        $check = isset($context[$condition['name']]) ?
          $context[$condition['name']] :
          NULL;

        return is_array($check) ? !in_array($condition['value'], $check) : $check != $condition['value'];
        break;

      // Parameter is set with an empty value.
      case self::OP_EMPTY:
        $check = isset($context[$condition['name']]) ?
          (string)$context[$condition['name']] :
          NULL;

        return isset($check) && Unicode::strlen($check) === 0;
        break;

      // Parameter is set and has any value.
      case self::OP_NOT_EMPTY:
        $check = isset($context[$condition['name']]) ?
          (string)$context[$condition['name']] :
          NULL;

        return isset($check) && Unicode::strlen($check) > 0;
        break;

      // Basic isset.
      case self::OP_SET:
        return isset($context[$condition['name']]);
        break;

      // Parameter must not be set.
      case self::OP_NOT_SET:
        return !isset($context[$condition['name']]);
        break;

      // Regular expression match.
      case self::OP_REGEX:
        $pattern = '/' . preg_quote($condition['value']) . '/';
        $check = isset($context[$condition['name']]) ? $context[$condition['name']] : NULL;
        if (is_array($check)) {
          foreach ($check as $check_item) {
            if (preg_match($pattern, $check_item)) {
              return TRUE;
            }
          }
          return FALSE;
        }
        return isset($check) ?
          preg_match($pattern, $context[$condition['name']]) :
          FALSE;
        break;

      default:
        return FALSE;
    }
  }

  /**
   * Get the key to be used as the plugin's wrapping element's HTML ID.
   *
   * @return string
   */
  protected function getFormWrapperKey()
  {
    return $this->getPluginId() . '_wrapper';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
        'conditions' => [],
        'require_all_params' => TRUE,
      ] + parent::defaultConfiguration();
  }

}
