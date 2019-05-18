<?php

namespace Drupal\memory_limit_policy\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that lists out the constraints for the policy.
 */
class MemoryLimitPolicyConstraintForm extends FormBase {

  /**
   * Plugin manager for constraints.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Machine name for the form step.
   *
   * @var string
   */
  protected $machineName;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.memory_limit_policy.memory_limit_constraint'),
      $container->get('form_builder')
    );
  }

  /**
   * Overridden constructor to load the plugin.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   Plugin manager for constraints.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(PluginManagerInterface $manager, FormBuilderInterface $form_builder) {
    $this->manager = $manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'memory_limit_policy_constraint_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $this->machineName = $cached_values['id'];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $constraints = [];
    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $constraints[$plugin_id] = (string) $definition['title'];
    }

    $form['add_constraint_title'] = [
      '#markup' => '<h2>Add Constraint</h2>',
    ];

    if (!empty($constraints)) {
      $form['constraint'] = [
        '#type' => 'select',
        '#options' => $constraints,
        '#prefix' => '<table style="width=100%"><tr><td>',
        '#suffix' => '</td>',
      ];
      $form['add'] = [
        '#type' => 'submit',
        '#name' => 'add',
        '#value' => $this->t('Configure Constraint Settings'),
        '#ajax' => [
          'callback' => [$this, 'add'],
          'event' => 'click',
        ],
        '#prefix' => '<td>',
        '#suffix' => '</td></tr></table>',
      ];
    }
    else {
      $form['empty_constraint'] = [
        '#markup' => $this->t('No constraint available. Enable a module providing a constraint type.'),
        '#prefix' => '<b>',
        '#suffix' => '</b>',
      ];
    }

    $form['constraint_list'] = [
      '#markup' => '<h2>Policy Constraints</h2>',
    ];

    $form['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="configured-constraints">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [
        'plugin_id' => $this->t('Plugin Id'),
        'summary' => $this->t('Summary'),
        'operations' => $this->t('Operations'),
      ],
      '#rows' => $this->renderRows($cached_values),
      '#empty' => $this->t('No constraints have been configured.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form has no explicit submit action since it just shows constraints.
  }

  /**
   * Ajax callback that manages adding a constraint.
   *
   * @param array $form
   *   Form definition of parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns the valid Ajax response from a modal window.
   */
  public function add(array &$form, FormStateInterface $form_state) {
    $constraint = $form_state->getValue('constraint');

    $content = $this->formBuilder->getForm('\Drupal\memory_limit_policy\Form\ConstraintEdit', $constraint, $this->machineName);

    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $url = Url::fromRoute('entity.memory_limit_policy.constraint.add', [
      'machine_name' => $this->machineName,
      'constraint_id' => $constraint,
    ], ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]]);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $url->toString();

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure Required Context'), $content, ['width' => '700']));

    return $response;
  }

  /**
   * Helper function to render the constraint rows for the policy.
   *
   * @param array|array $cached_values
   *   Loading the cached metadata for the form wizard.
   *
   * @return array
   *   Constraint rows rendered for the policy.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function renderRows($cached_values) {
    /** @var \Drupal\memory_limit_policy\Entity\MemoryLimitPolicy $policy */
    $policy = $cached_values['memory_limit_policy'];

    $configured_conditions = [];

    foreach ($policy->getConstraints() as $row => $constraint) {
      /** @var \Drupal\memory_limit_policy\MemoryLimitConstraintInterface $instance */
      $instance = $this->manager->createInstance($constraint['id'], $constraint);

      $operations = $this->getOperations('entity.memory_limit_policy.constraint',
        ['machine_name' => $cached_values['id'], 'constraint_id' => $row]);

      $build = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $configured_conditions[] = [
        'plugin_id' => $instance->getPluginId(),
        'summary' => $instance->getSummary(),
        'operations' => [
          'data' => $build,
        ],
      ];
    }
    return $configured_conditions;
  }

  /**
   * Helper function to load edit operations for a constraint.
   *
   * @param string $route_name_base
   *   String representing the base of the route name for the constraints.
   * @param array $route_parameters
   *   Passing route parameter context to the helper function.
   *
   * @return array
   *   Set of operations associated with a constraint.
   */
  protected function getOperations($route_name_base, array $route_parameters = []) {
    $edit_url = new Url($route_name_base . '.edit', $route_parameters);
    $route_parameters['id'] = $route_parameters['constraint_id'];
    unset($route_parameters['constraint_id']);
    $delete_url = new Url($route_name_base . '.delete', $route_parameters);
    $operations = [];

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => $edit_url,
      'weight' => 10,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => $delete_url,
      'weight' => 100,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    return $operations;
  }

}
