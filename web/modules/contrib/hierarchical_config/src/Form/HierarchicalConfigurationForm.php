<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\Form\HierarchicalConfigurationForm.
 */

namespace Drupal\hierarchical_config\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Hierarchical configuration edit forms.
 *
 * @ingroup hierarchical_config
 */
class HierarchicalConfigurationForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\hierarchical_config\Entity\HierarchicalConfiguration */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Hierarchical configuration.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Hierarchical configuration.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.hierarchical_configuration.canonical', ['hierarchical_configuration' => $entity->id()]);
  }

}
