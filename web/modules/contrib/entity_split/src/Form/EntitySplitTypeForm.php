<?php

namespace Drupal\entity_split\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Class EntitySplitTypeForm.
 */
class EntitySplitTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_split\Entity\EntitySplitType $entity_split_type */
    $entity_split_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_split_type->label(),
      '#description' => $this->t("Label for the Entity split type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_split_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_split\Entity\EntitySplitType::load',
      ],
      '#disabled' => !$entity_split_type->isNew(),
    ];

    $entity_type = $form_state->hasValue('entity_type') ? $form_state->getValue('entity_type') : $entity_split_type->getMasterEntityType();
    $bundle = $entity_split_type->getMasterBundle();

    if ($entity_split_type->isNew()) {
      $entity_types = [];
      $bundles = [];

      foreach ($this->entityTypeManager->getDefinitions() as $entity_type_info) {
        if (($entity_type_info instanceof ContentEntityType) && ($entity_type_info->id() !== 'entity_split')) {
          $entity_types[$entity_type_info->id()] = $entity_type_info->getLabel();
        }
      }

      if (!empty($entity_type)) {
        foreach (static::typeBundleInfoService()->getBundleInfo($entity_type) as $bundle_name => $bundle_info) {
          $bundles[$bundle_name] = $bundle_info['label'];
        }
      }

      $form['entity_type'] = [
        '#type' => 'select',
        '#default_value' => $entity_type,
        '#title' => $this->t('Entity to which this split is attached'),
        '#options' => $entity_types,
        '#description' => $this->t('Entity type can not be changed after the entity split type has been created.'),
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'updateFormElements'],
        ],
      ];

      $form['bundle_wrapper'] = [
        '#type' => 'container',
        'bundle' => [
          '#type' => 'select',
          '#default_value' => $bundle,
          '#title' => $this->t('Bundle to which this split is attached'),
          '#options' => $bundles,
          '#description' => $this->t('Bundle can not be changed after the entity split type has been created.'),
          '#required' => TRUE,
        ],
        '#attributes' => ['id' => 'entity-split-bundle-wrapper'],
      ];
    }
    else {
      $form['entity_type_label'] = [
        '#type' => 'item',
        '#plain_text' => $this->entityTypeManager->getDefinition($entity_split_type->getMasterEntityType())->getLabel(),
        '#title' => $this->t('Entity to which this split is attached'),
      ];

      $bundle_infos = static::typeBundleInfoService()->getBundleInfo($entity_split_type->getMasterEntityType());
      $bundle_label = isset($bundle_infos[$bundle]) ? $bundle_infos[$bundle]['label'] : '';

      $form['bundle_label'] = [
        '#type' => 'item',
        '#plain_text' => $bundle_label,
        '#title' => $this->t('Bundle to which this split is attached'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_split_type = $this->entity;
    $status = $entity_split_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label entity split type.', [
          '%label' => $entity_split_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label entity split type.', [
          '%label' => $entity_split_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_split_type->toUrl('collection'));
  }

  /**
   * AJAX callback to update the bundle list.
   */
  public function updateFormElements($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#entity-split-bundle-wrapper', $form['bundle_wrapper']));
    return $response;
  }

  /**
   * Returns entity type bundle service.
   *
   * @return \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   *   Entity type bundle service.
   */
  private static function typeBundleInfoService() {
    return \Drupal::service('entity_type.bundle.info');
  }

}
