<?php
/**
 * @file
 * Contains \Drupal\entityblock\Plugin\Block\EntityBlockBlock.
 */

namespace Drupal\entityblock\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides an entityblock block.
 *
 * @Block(
 *  id = "entityblock_block",
 *  admin_label = @Translation("EntityBlock"),
 *  deriver = "Drupal\entityblock\Plugin\Derivative\EntityBlockContent"
 * )
 */
class EntityBlockBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface.
   */
  protected $account;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account;
    $this->entityRepository = $entity_repository;
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
      $container->get('current_user'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_override' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block_form = [];
    $block_form['label_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override block title'),
      '#default_value' => $this->configuration['label_override'],
      '#description' => $this->t('Selecting this will allow you to override the block title that is set in the EntityBlock.')
    ];

    return $block_form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['label_override'] = $form_state->getValue('label_override');
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $field_value = $this->loadFieldValue();
    if ($field_value && (!isset($this->configuration['label_override']) || !$this->configuration['label_override'])) {
      $title = $field_value->title;

      $entity = $this->loadEntity();
      return \Drupal::token()->replace($title, [$entity->getEntityTypeId() => $entity]);
    }
    return parent::label();
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineNameSuggestion() {
    $transliterated = $this->transliteration()->transliterate($this->label(), LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = Unicode::strtolower($transliterated);
    $transliterated = preg_replace('@[^a-z0-9_.]+@', '', $transliterated);
    return $transliterated;
  }

  /**
   * Helper function to load the entity of this entityblock.
   */
  private function loadEntity() {
    $key = $this->getDerivativeId();

    list($field_uuid, $entity_uuid, $delta) = explode('|', $key);

    if ($field = $this->loadField()) {
      return $this->entityRepository->loadEntityByUuid($field->getTargetEntityTypeId(), $entity_uuid);
    }
  }

  /**
   * Helper function to load the field of this entityblock.
   */
  private function loadField() {
    $key = $this->getDerivativeId();
    list($field_uuid, $entity_uuid, $delta) = explode('|', $key);

    return $this->entityRepository->loadEntityByUuid('field_storage_config', $field_uuid);
  }

  /**
   * Helper function to load the field value for this entityblock.
   */
  private function loadFieldValue() {
    $key = $this->getDerivativeId();
    list($field_uuid, $entity_uuid, $delta) = explode('|', $key);

    $entity = $this->loadEntity();
    $field = $this->loadField();

    return $entity ? $entity->{$field->getName()}->get($delta) : NULL;
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $entity = $this->loadEntity();
    $field_value = $this->loadFieldValue();
    if ($entity) {
      if ($field_value) {
        $content = $this->entityTypeManager
          ->getViewBuilder($entity->getEntityTypeId())
          ->view($entity, $field_value->view_mode);
        $content['#title'] = $this->label();
        $content['#entityblock'] = TRUE;
        return $content;
      }
      else {
        return [
          '#markup' => $this->t('EntityBlock has been disabled for this <a href=":url">entity</a>.', [
            ':url' => $entity->toUrl()->toString(),
          ]),
          '#access' => $this->account->hasPermission('administer blocks'),
        ];
      }
    }
    return [
      '#markup' => $this->t('Block with key %key does not exist.', [
        '%key' => $this->getDerivativeId(),
      ]),
      '#access' => $this->account->hasPermission('administer blocks'),
    ];
  }

  /**
   * Implements \Drupal\block\BlockBase::access().
   */
  public function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('access content')) {
      $entity = $this->loadEntity();
      if ($entity) {
        return $entity->access('view', $account, TRUE);
      }
      return AccessResult::forbidden();
    }
  }
}
