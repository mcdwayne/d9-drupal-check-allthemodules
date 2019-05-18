<?php

namespace Drupal\access_conditions\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides edit form for access model instance forms.
 */
class AccessModelEditForm extends AccessModelAddForm {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a AccessModelEditForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The ConditionManager for building the access conditions UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(ConditionManager $condition_manager, ContextRepositoryInterface $context_repository, LanguageManagerInterface $language, FormBuilderInterface $form_builder) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
    $this->formBuilder = $form_builder;
    $this->languageManager = $language;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal add condition form.
   */
  public function addForm(array &$form, FormStateInterface $form_state) {
    $condition = $form_state->getValue('conditions');
    $content = $this->formBuilder->getForm(ConditionAddForm::class, $this->entity, $condition);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $route_parameters = [
      'access_model' => $this->entity->id(),
      'condition' => $condition,
    ];
    $options = ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]];
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = Url::fromRoute('entity.access_model.condition.add', $route_parameters, $options)->toString();

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(
      $this->t('Configure @label context', ['@label' => mb_strtolower($this->getContextOptionsForm()[$condition])]),
      $content,
      ['width' => '700'])
    );

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\access_conditions\Entity\AccessModelInterface $access_model */
    $access_model = $this->entity;

    $form = parent::form($form, $form_state);
    $form['#title'] = $this->t('Edit access model');
    $form['#attached']['library'][] = 'access_conditions/access_model.add_form';

    $form['access_model_options'] = [
      '#type' => 'container',
      '#prefix' => '<div class="access-condition-options-container-inline">',
      '#suffix' => '</div>',
    ];
    $form['access_model_options']['access_logic'] = [
      '#type' => 'select',
      '#options' => [
        'and' => $this->t('@logic (For all conditions)', ['@logic' => $this->t('AND')]),
        'or' => $this->t('@logic (For all conditions)', ['@logic' => $this->t('OR')]),
      ],
      '#default_value' => $access_model->getAccessLogic(),
      '#parents' => ['access_logic'],
    ];
    $form['access_model_options']['conditions'] = [
      '#type' => 'select',
      '#options' => $this->getContextOptionsForm(),
    ];
    $form['access_model_options']['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add Condition'),
      '#ajax' => [
        'callback' => [$this, 'addForm'],
        'event' => 'click',
      ],
      '#submit' => [
        'callback' => [$this, 'submitForm'],
      ],
    ];
    $form['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Plugin Id'),
        $this->t('Summary'),
        $this->t('Operations'),
      ],
      '#rows' => $this->renderRows(),
      '#empty' => $this->t('No conditions have been configured.'),
    ];
    $form['notice'] = [
      '#markup' => $this->t("Notice: Please note when adding conditions that this module does not consider whether the entity is enabled or not, only check this for entities that implements EntityPublishedInterface, as nodes."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (($triggering = $form_state->getTriggeringElement()) && $triggering['#name'] == 'add') {
      return;
    }

    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The access model configuration has been updated.'));
    $form_state->setRedirect('entity.access_model.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Save access model');
    $actions['delete']['#title'] = $this->t('Remove');

    return $actions;
  }

  /**
   * Get contexts array.
   *
   * @return array
   *   The available contexts array of key value pairs suitable as '#options'
   *   for form elements.
   */
  protected function getContextOptionsForm() {
    static $options = NULL;

    if ($options === NULL) {
      $contexts = $this->contextRepository->getAvailableContexts();
      foreach ($this->conditionManager->getDefinitionsForContexts($contexts) as $plugin_id => $definition) {
        // Don't display the language condition until we have multiple
        // languages.
        if ($plugin_id == 'language' && !$this->languageManager->isMultilingual()) {
          continue;
        }

        $options[str_replace(':', "-", $plugin_id)] = (string) $definition['label'];
      }
    }

    return $options;
  }

  /**
   * Returns an array of supported operations for the conditions.
   *
   * @param string $route_name_base
   *   The name of the route.
   * @param array $route_parameters
   *   An associative array of parameter names and values.
   *
   * @return array
   *   The supported operations for the conditions.
   */
  protected function getOperations($route_name_base, array $route_parameters = []) {
    $operations = [];

    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => new Url($route_name_base . '.edit', $route_parameters),
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
      'url' => new Url($route_name_base . '.delete', $route_parameters),
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

  /**
   * Renders the conditions rows.
   *
   * @return array
   *   The table rows render array.
   */
  protected function renderRows() {
    $configured_conditions = [];
    foreach ($this->entity->getAccessConditions() as $row => $condition) {
      $build = [
        '#type' => 'operations',
        '#links' => $this->getOperations('entity.access_model.condition', [
          'access_model' => $this->entity->id(),
          'id' => $row,
        ]),
      ];
      $configured_conditions[] = [
        $condition->getPluginId(),
        $condition->summary(),
        // @codingStandardsIgnoreStart
        'operations' => [
          'data' => $build,
        ],
        // @codingStandardsIgnoreEnd
      ];
    }

    return $configured_conditions;
  }

}
