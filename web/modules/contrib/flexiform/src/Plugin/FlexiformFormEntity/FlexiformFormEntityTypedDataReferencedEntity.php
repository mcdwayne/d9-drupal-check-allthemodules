<?php

namespace Drupal\flexiform\Plugin\FlexiformFormEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityBase;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Entity plugin.
 *
 * For entities that are passed in through the configuration like the base
 * entity.
 *
 * @FlexiformFormEntity(
 *   id = "referenced_entity",
 *   deriver = "\Drupal\flexiform\Plugin\Deriver\FormEntityTypedDataReferencedEntityDeriver"
 * )
 */
class FlexiformFormEntityTypedDataReferencedEntity extends FlexiformFormEntityBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    try {
      $base = $this->getContextValue('base');
      if (!$base) {
        return NULL;
      }

      if ($entity = $base->{$this->pluginDefinition['property_name']}->entity) {
        return $entity;
      }
      elseif (!empty($this->configuration['create'])) {
        return $this->createEntity();
      }

      return NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Create a new entity ready for this situation.
   */
  protected function createEntity() {
    $values = [];
    if ($bundle_key = $this->entityTypeManager->getDefinition($this->getEntityType())->getKey('bundle')) {
      $values[$bundle_key] = $this->getBundle();
    }

    $entity = $this->entityTypeManager->getStorage($this->getEntityType())->create($values);

    $this->moduleHandler->invokeAll('flexiform_form_entity_entity_create', [$entity, $this]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave(EntityInterface $entity) {
    // Set the owner context if we can.
    if (($this->configuration['owner_context'] ?? '_none') != 'none') {
      if ($entity instanceof EntityOwnerInterface && !$entity->getOwnerId()) {
        if ($this->configuration['owner_context'] == '_current_user') {
          $entity->setOwnerId($this->currentUser->id());
        }
        else {
          $context = $this->formEntityManager->getContexts()[$this->configuration['owner_context']];
          if ($context->hasContextValue()) {
            $entity->setOwner($context->getContextValue());
          }
        }
      }
    }

    parent::doSave($entity);

    // Attach to the appropriate field.
    $base = $this->getContextValue('base');
    if ($base) {
      $base->{$this->pluginDefinition['property_name']}[0] = $entity;
      $this->formEntityManager->deferredSave($base);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::configurationForm($form, $form_state);
    $form['create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create New Entity'),
      '#description' => $this->t('If the property is empty, and new entity will be created.'),
      '#default_value' => !empty($this->configuration['create']),
    ];

    // Set up ownership, if relevant.
    if ($this->entityTypeManager->getDefinition($this->getEntityType())->entityClassImplements(EntityOwnerInterface::class)) {
      $form['owner_context'] = [
        '#type' => 'select',
        '#title' => $this->t('Set owner to'),
        '#description' => $this->t('This will not override an existing owner.'),
        '#default_value' => $this->configuration['owner_context'] ?? '_none',
        '#options' => [
          '_current_user' => $this->t('Current user'),
        ],
        '#empty_option' => $this->t('Do not set owner'),
        '#empty_value' => '_none',
        '#states' => [
          'visible' => [
            ':input[name="configuration[save_on_submit]"]' => ['checked' => TRUE],
            ':input[name="configuration[create]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $context_definition = new ContextDefinition('entity:user', NULL, TRUE, FALSE);
      $matching_contexts = $this->contextHandler()->getMatchingContexts($this->formEntityManager->getContexts(), $context_definition);
      foreach ($matching_contexts as $context) {
        $form['owner_context']['#options'][$context->getEntityNamespace()] = $context->getContextDefinition()->getLabel();
      }
    }

    return $form;
  }

}
