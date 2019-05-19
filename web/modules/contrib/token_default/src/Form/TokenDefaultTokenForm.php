<?php

namespace Drupal\token_default\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TokenDefaultTokenForm.
 */
class TokenDefaultTokenForm extends EntityForm {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * PatternEditForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $token_default_token = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $token_default_token->label(),
      '#description' => $this->t("Label for the Default token."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $token_default_token->id(),
      '#machine_name' => [
        'exists' => '\Drupal\token_default\Entity\TokenDefaultToken::load',
      ],
      '#disabled' => !$token_default_token->isNew(),
    ];

    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token pattern string'),
      '#maxlength' => 255,
      '#default_value' => $token_default_token->getPattern(),
      '#description' => $this->t("Pattern string of the token."),
      '#required' => TRUE,
    ];

    $form['replacement'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token pattern replacement'),
      '#maxlength' => 255,
      '#default_value' => $token_default_token->getReplacement(),
      '#description' => $this->t("Replacement string if the token cannot be matched."),
      '#required' => TRUE,
    ];

    // TODO: For now we are only working with content
    // expand to make entity type selectable.
    $entity_type = $this->entityTypeManager->getDefinition('node');
    $form['type'] = [
      '#type' => 'hidden',
      '#default_value' => 'node',
      '#required' => TRUE,
    ];

    if ($entity_type->hasKey('bundle') && $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id())) {
      $bundle_options = [];
      foreach ($bundles as $id => $info) {
        $bundle_options[$id] = $info['label'];
      }
      $form['bundle'] = [
        '#title' => $entity_type->getBundleLabel(),
        '#type' => 'select',
        '#options' => $bundle_options,
        '#empty_value' => '',
        '#default_value' => $token_default_token->getBundle(),
        '#description' => $this->t('Select to which type this pattern should be applied. Leave empty to allow any.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $token_default_token = $this->entity;
    $status = $token_default_token->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Default token.', [
          '%label' => $token_default_token->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Default token.', [
          '%label' => $token_default_token->label(),
        ]));
    }
    $form_state->setRedirectUrl($token_default_token->toUrl('collection'));
  }

}
