<?php

namespace Drupal\login_notification\Form;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define login notification add/edit form.
 */
class LoginNotificationForm extends EntityForm {

  /**
   * @var \Drupal\login_notification\Form\ConditionManager
   */
  protected $conditionManager;

  /**
   * Define login notification constructor.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   */
  public function __construct(ConditionManager $condition_manager) {
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form =  parent::form($form, $form_state);

    /** @var \Drupal\login_notification\Entity\LoginNotification $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the login notification.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$entity, 'entityExist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Notification Type'),
      '#required' => TRUE,
      '#options' => [
        MessengerInterface::TYPE_ERROR => $this->t('Error'),
        MessengerInterface::TYPE_STATUS => $this->t('Status'),
        MessengerInterface::TYPE_WARNING => $this->t('Warning')
      ],
      '#default_value' => $entity->type,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification Message'),
      '#description' => $this->t('Input the notification message that should be 
        shown to the appropriate users. Tokens can be used.'),
      '#required' => TRUE,
      '#default_value' => $entity->message,
    ];
    $form['token_replacement'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user'],
    ];
    $form['notification_conditions'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Notification Conditions'),
      '#description' => $this->t('Define conditions that need to be met prior to 
        the notification being shown.'),
    ];
    $form['conditions'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $conditions = $entity->conditions;

    foreach ($this->getConditionDefinitions() as $plugin_id => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $form['conditions'][$plugin_id] = [
        '#type' => 'details',
        '#title' => $definition['label'],
        '#group' => 'notification_conditions'
      ];
      $condition = isset($conditions[$plugin_id])
        ? $conditions[$plugin_id]
        : [];

      /** @var ConditionInterface $instance */
      $instance = $this
        ->conditionManager
        ->createInstance($plugin_id);

      if ($instance instanceof ConditionInterface) {
        $subform = [];
        $configuration = isset($condition['configuration'])
          ? $condition['configuration']
          : [];
        $instance->setConfiguration($configuration);

        $form['conditions'][$plugin_id]['configuration'] = $instance
          ->buildConfigurationForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );

        /**
         * @todo Remove workaround once
         * https://www.drupal.org/project/drupal/issues/2783897 is fixed.
         */
        if ($plugin_id === 'current_theme') {
          $form['conditions'][$plugin_id]['configuration']['theme']['#empty_option'] = $this->t('- None -');
        }
      }
    }
    $form['conditions_met_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All conditions must be met'),
      '#description' => $this->t('If unchecked then only one condition needs to 
        be met.'),
      '#default_value' => $entity->conditions_met_all,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Clean up the condition actions.
    if ($conditions = $form_state->getValue('conditions')) {
      unset($conditions['actions']);
      $form_state->setValue('conditions', $conditions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Get condition definitions.
   *
   * @return array
   */
  protected function getConditionDefinitions() {
    return $this->conditionManager->getDefinitionsForContexts([
      new Context(ContextDefinition::create('entity:user')),
    ]);
  }

  /**
   * Get user role options.
   *
   * @param array $exclude_roles
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUserRoleOptions($exclude_roles = ['anonymous', 'authenticated']) {
    $options = [];

    /** @var \Drupal\user\Entity\Role $role */
    foreach ($this->getUserRoles() as $identifier => $role) {
      if (in_array($role->id(), $exclude_roles)) {
        continue;
      }
      $options[$identifier] = $role->label();
    }

    return $options;
  }

  /**
   * Get user roles.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUserRoles() {
    return $this->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();
  }
}
