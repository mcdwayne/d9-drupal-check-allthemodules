<?php

namespace Drupal\frontend;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

class ContainerForm extends EntityForm {

  public function form(array $form, FormStateInterface $form_state) {
    $container = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $container->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Container name'),
      '#default_value' => $container->id(),
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#description' => $this->t('A unique name must only contain lowercase letters, numbers and hyphens.'),
      '#machine_name' => [
        'exists' => [$this, 'nameExists'],
        'source' => ['label'],
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ],
      // A container's machine name cannot be changed.
      '#disabled' => !$container->isNew() || $container->isLocked(),
    ];

    $form = parent::form($form, $form_state);
    return $form;
  }

  /**
   * Return whether a container name already exists.
   *
   * @param string $value
   *   The name of the container.
   *
   * @return bool
   *   Returns TRUE if the container already exists, FALSE otherwise.
   */
  public function nameExists($value) {
    if ($this->entityTypeManager->getStorage('container')->getQuery()->condition('id', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }

    return FALSE;
  }

}
