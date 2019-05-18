<?php

namespace Drupal\access_conditions\Form;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
use Drupal\access_conditions\Entity\AccessModelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides add form for condition instance forms.
 */
class ConditionAddForm extends FormBase {

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
   * The access model entity.
   *
   * @var \Drupal\access_conditions\Entity\AccessModelInterface
   */
  protected $accessModel;

  /**
   * Constructs a ConditionAddForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   */
  public function __construct(ConditionManager $condition_manager, ContextRepositoryInterface $context_repository) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'access_conditions_condition_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccessModelInterface $access_model = NULL, $condition = NULL) {
    $this->accessModel = $access_model;

    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    if (Uuid::isValid($condition)) {
      $id = $condition;
      $instance = $this->accessModel->getAccessCondition($id);
    }
    else {
      $instance = $this->conditionManager->createInstance(str_replace('-', ":", $condition), []);
    }

    /** @var \Drupal\Core\Condition\ConditionInterface $instance */
    $form = $instance->buildConfigurationForm($form, $form_state);

    if (isset($id)) {
      $form['id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }
    $form['instance'] = [
      '#type' => 'value',
      '#value' => $instance,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'closeForm'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Condition\ConditionInterface $instance */
    $instance = $form_state->getValue('instance');
    $instance->submitConfigurationForm($form, $form_state);

    if ($instance instanceof ContextAwarePluginInterface) {
      /** @var \Drupal\Core\Plugin\ContextAwarePluginInterface $instance */
      $context_mapping = $form_state->hasValue('context_mapping') ? $form_state->getValue('context_mapping') : [];
      $instance->setContextMapping($context_mapping);
    }

    if ($form_state->hasValue('id')) {
      $conditions = $this->accessModel->getAccessConditions()->getConfiguration();
      $conditions[$form_state->getValue('id')] = $instance->getConfiguration();
      $this->accessModel->set('access_conditions', $conditions);
    }
    else {
      drupal_set_message($this->t('The access model condition has been added.'));
      $this->accessModel->addAccessCondition($instance->getConfiguration());
    }
    $this->accessModel->save();

    $form_state->setRedirect('entity.access_model.edit_form', ['access_model' => $this->accessModel->id()]);
  }

  /**
   * Callback for closing the modal condition form.
   */
  public function closeForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand(Url::fromRoute('entity.access_model.edit_form', ['access_model' => $this->accessModel->id()])->toString()));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

}
