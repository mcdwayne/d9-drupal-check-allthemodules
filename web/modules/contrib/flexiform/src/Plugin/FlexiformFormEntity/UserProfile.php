<?php

namespace Drupal\flexiform\Plugin\FlexiformFormEntity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flexiform\FormEntity\FlexiformFormEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form Entity for getting profiles from a user object.
 *
 * @FlexiformFormEntity(
 *   id = "user_profile",
 *   deriver = "\Drupal\flexiform\Plugin\Deriver\FormEntityProfileTypeDeriver"
 * )
 */
class UserProfile extends FlexiformFormEntityBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'profile';
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->pluginDefinition['profile_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $user = $this->getContextValue('user');
    if (!$user) {
      return NULL;
    }

    $profile_storage = $this->entityTypeManager->getStorage('profile');
    try {
      if ($user->id() && ($entity = $profile_storage->loadDefaultByUser($user, $this->getBundle()))) {
        return $entity;
      }
      elseif (!empty($this->configuration['create'])) {
        $entity = $this->createEntity();
        return $entity;
      }
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Create a new entity ready for this situation.
   */
  protected function createEntity() {
    $values = [
      'type' => $this->getBundle(),
      'uid' => [
        'entity' => $this->getContextValue('user'),
      ],
    ];
    $entity = $this->entityTypeManager->getStorage('profile')->create($values);
    $this->moduleHandler->invokeAll('flexiform_form_entity_entity_create', [$entity, $this]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::configurationForm($form, $form_state);
    $form['create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create New Profile'),
      '#description' => $this->t('If the property is empty, and new profile will be created.'),
      '#default_value' => !empty($this->configuration['create']),
    ];

    return $form;
  }

}
