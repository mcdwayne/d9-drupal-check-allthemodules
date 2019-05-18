<?php

namespace Drupal\flexiform\Plugin\FlexiformFormEntity;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form Entity plugin.
 *
 * For entities that are passed in through the configuration
 * like the base entity.
 *
 * @FlexiformFormEntity(
 *   id = "load",
 *   label = @Translation("Load Entity"),
 * )
 */
class FlexiformFormEntityLoad extends FlexiformFormEntityProvided {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if (isset($this->configuration['id'])) {
      $entity = $this->entityTypeManager->getStorage($this->getEntityType())->load($this->configuration['id']);
      return $this->checkBundle($entity) ? $entity : NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::configurationForm($form, $form_state);

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('The ID of the entity.'),
      '#default_value' => !empty($this->configuration['id']) ? $this->configuration['id'] : '',
    ];

    return $form;
  }

}
