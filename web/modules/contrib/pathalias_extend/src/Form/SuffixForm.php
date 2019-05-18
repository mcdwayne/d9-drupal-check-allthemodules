<?php

namespace Drupal\pathalias_extend\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the suffix add and edit forms.
 */
class SuffixForm extends EntityForm {

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SuffixForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle info service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Administrative label for the suffix.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => ['exists' => [$this, 'exist']],
      '#disabled' => !$entity->isNew(),
    ];

    $form['ajax'] = [
      '#type' => 'container',
      '#title' => $this->t('Target'),
      '#prefix' => '<div id="ajax-container">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    ];

    $entity_type_id = $entity->getTargetEntityTypeId();
    $form['ajax']['target_entity_type_id'] = [
      '#type' => 'select',
      '#default_value' => empty($entity_type_id) ? NULL : $entity_type_id,
      '#options' => $this->getEntityTypeIdOptions(),
      '#title' => $this->t('Target entity type'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateTargetBundles',
        'wrapper' => 'ajax-container',
      ],
    ];

    $bundle_id = $entity->getTargetBundleId();
    $options = $this->getBundleIdOptions($form_state);
    $form['ajax']['target_bundle_id'] = [
      '#type' => 'select',
      '#default_value' => empty($bundle_id) ? NULL : $bundle_id,
      '#options' => $options,
      '#title' => $this->t('Target bundle'),
      '#required' => TRUE,
      '#access' => !empty($options),
    ];

    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#default_value' => $entity->getPattern(),
      '#description' => $this->t('Enter a suffix pattern. You may use <code>*</code> as placeholders. The suffix pattern should start with a slash and match the extended part of the path alias. Example: For a suffix targeting content entity edit forms, use <code>/edit</code>, not <code>/node/*/edit</code>.'),
      '#required' => TRUE,
    ];

    $form['create_alias'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create alias, if missing. Created suffixes must still match criteria above.'),
      '#default_value' => $entity->getCreateAlias(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];

    return $form;
  }

  /**
   * Handles AJAX callbacks for AJAX container.
   *
   * @ingroup forms
   */
  public static function updateTargetBundles(array $form, FormStateInterface &$form_state): array {
    return $form['ajax'];
  }

  /**
   * Gets options for bundle id form element from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Options for bundle id form element.
   */
  protected function getBundleIdOptions(FormStateInterface $form_state): array {
    $return = [];

    $entity_type_id = $form_state->getValue('target_entity_type_id');
    if (empty($entity_type_id)) {
      $entity = $this->getEntity();
      $entity_type_id = $entity->getTargetEntityTypeId();
      if (empty($entity_type_id)) {
        return [];
      }
    }

    $bundles = $this->bundleInfo->getBundleInfo($entity_type_id);
    if (count($bundles) > 0) {
      foreach ($bundles as $bundle_id => $info) {
        $return[$bundle_id] = $info['label'];
      }
    }

    return $return;
  }

  /**
   * Gets options for entity type id form element.
   *
   * @return array
   *   Options for entity type id form element.
   */
  protected function getEntityTypeIdOptions(): array {
    $return = [];

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type => $info) {
      if ($info instanceof ContentEntityTypeInterface) {
        $return[$entity_type] = $info->getLabel();
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    if ($entity->save()) {
      $this->messenger->addMessage($this->t('The %label suffix has been saved.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger->addMessage($this->t('Unable to save %label suffix.', [
        '%label' => $entity->label(),
      ]), MessengerInterface::TYPE_ERROR);
    }

    $form_state->setRedirect('entity.pathalias_extend_suffix.collection');
  }

  /**
   * Checks, if a suffix configuration entity exists.
   *
   * @param string $id
   *   Suffix id of suffix to check.
   *
   * @return bool
   *   Whether suffix exists.
   */
  public function exist(string $id): bool {
    $entity = $this->entityTypeManager
      ->getStorage('pathalias_extend_suffix')
      ->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
