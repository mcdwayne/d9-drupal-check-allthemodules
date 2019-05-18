<?php

namespace Drupal\block_placeholder\Form;

use Drupal\block_content\Entity\BlockContentType;
use Drupal\block_placeholder\Entity\BlockPlaceholderReference;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define block placeholder form.
 */
class BlockPlaceholderForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var BlockPlaceholderReference $entity */
    $entity = $this->entity;

    $form['label'] = [
      "#type" => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Input a human-readable label for the block placeholder.'),
      '#default_value' => $entity->label(),
      '#maxlegnth' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$entity, 'entityExist']
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['placeholder_restriction'] = [
      '#type' => 'details',
      '#title' => $this->t('Placeholder Restrictions'),
      '#open' => TRUE,
    ];
    $form['placeholder_restriction']['reference_limit_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Limit Type'),
      '#description' => $this->t('Define the content reference limit type.'),
      '#required' => TRUE,
      '#options' => [
        'limited' => $this->t('Limited'),
        'unlimited' => $this->t('Unlimited'),
      ],
      '#default_value' => $entity->referenceLimitType(),
    ];
    $form['placeholder_restriction']['reference_limited_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Limited Value'),
      '#min' => 1,
      '#max' => 25,
      '#size' => 2,
      '#default_value' => $entity->referencedLimitedValue(),
      '#states' => [
        'visible' => [
          ':input[name="reference_limit_type"]' => ['value' => 'limited']
        ]
      ]
    ];
    $form['placeholder_restriction']['block_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Block Types'),
      '#description' => $this->t('Select block types that can be referenced. 
      <br/> <strong>Note:</strong> If none are selected, then all block types 
      are available.'),
      '#options' => $this->getBlockBundleTypeOptions(),
      '#multiple' => TRUE,
      '#default_value' => $entity->blockTypes(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var BlockPlaceholderReference $entity */
    $entity = $this->entity;

    if ($form_state->getValue('reference_limit_type') === 'limited') {
      $limited_value = $form_state->getValue('reference_limited_value');
      $reference_count = $entity->getReferenceCount();

      if ($limited_value < $reference_count) {
        $form_state->setError(
          $form['placeholder_restriction']['reference_limited_value'],
          $this->t('There is @count reference(s) defined. </br> 
          <strong>Note:</strong> The limited value needs to be a higher or equal 
          to the reference count.', [
             '@count' => $reference_count
          ]
        ));
      }
    }
    $block_types = $form_state->getValue('block_types');

    if (!empty($block_types)) {
      if ($invalided_types = $entity->invalidBlockTypes()) {
        $form_state->setError(
          $form['placeholder_restriction']['block_types'],
          $this->t('There is @count reference(s) that are associated with the
          following @list_types block types. <br/> <strong>Note:</strong> 
          Remove these reference(s) prior to changing the configurations.', [
            '@count' => count($invalided_types),
            '@list_types' => implode(', ', array_unique($invalided_types))
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $state = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $state;
  }

  /**
   * Get block bundle type options.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBlockBundleTypeOptions() {
    $options = [];

    foreach ($this->getBlockBundleTypes() as $name => $block_type) {
      if (!$block_type instanceof BlockContentType) {
        continue;
      }
      $options[$name] = $block_type->label();
    }

    return $options;
  }

  /**
   * Get content block bundle types.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getBlockBundleTypes() {
    return $this->entityTypeManager
      ->getStorage('block_content_type')
      ->loadMultiple();
  }
}
