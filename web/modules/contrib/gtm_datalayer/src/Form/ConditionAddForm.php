<?php

namespace Drupal\gtm_datalayer\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Url;
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
   * The entity.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $entity;

  /**
   * Constructs a ConditionAddForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   */
  function __construct(ConditionManager $condition_manager, ContextRepositoryInterface $context_repository) {
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
    return 'gtm_datalayer_condition_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $entity = NULL, $condition = NULL) {
    $this->entity = $entity;

    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    if (Uuid::isValid($condition)) {
      $id = $condition;
      $instance = $this->entity->getAccessCondition($id);
    }
    else {
      $instance = $this->conditionManager->createInstance(str_replace('-', ":", $condition), []);
    }

    /** @var \Drupal\Core\Condition\ConditionInterface $instance */
    $form = $instance->buildConfigurationForm($form, $form_state);

    if (isset($id)) {
      $form['id'] = [
        '#type' => 'value',
        '#value' => $id
      ];
    }
    $form['instance'] = [
      '#type' => 'value',
      '#value' => $instance
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
      $conditions = $this->entity->getAccessConditions()->getConfiguration();
      $conditions[$form_state->getValue('id')] = $instance->getConfiguration();
      $this->entity->set('access_conditions', $conditions);
    }
    else {
      drupal_set_message($this->t('The @label condition has been added.', ['@label' => Unicode::strtolower($this->entity->label())]));
      $this->entity->addAccessCondition($instance->getConfiguration());
    }
    $this->entity->save();

    $form_state->setRedirectUrl($this->getEditUrl());
  }

  /**
   * Callback for closing the modal condition form.
   */
  public function closeForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($this->getEditUrl()->toString()));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * Returns the entity edit route.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getEditUrl() {
    $entity = $this->entity;

    if ($entity->hasLinkTemplate('edit-form')) {
      // If available, return the edit URL.
      return $entity->toUrl('edit-form');
    }
    else {
      // Otherwise fall back to the default link template.
      return $entity->toUrl();
    }
  }

}
