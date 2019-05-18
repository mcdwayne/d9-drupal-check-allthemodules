<?php

/**
 * @file
 * Contains \Drupal\entity_jump_menu\Form\EntityJumpMenuForm.
 */

namespace Drupal\entity_jump_menu\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form with an entity jump menu.
 */
class EntityJumpMenuForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_jump_menu_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->getEntityTypes();

    // Get current request's entity type and id.
    list($entity_type, $entity_id) = $this->getCurrentRequestsEntity();

    // Add .entity-jump-menu-form since there can be multiple instance on the
    // page.
    $form['#attributes']['class'][] = 'entity-jump-menu-form';

    // Add theme name to form classes.
    $form['#attributes']['class'][] = \Drupal::theme()->getActiveTheme()->getName();

    $form['#prefix'] = '<div class="container-inline">';
    $form['#suffix'] = '</div>';

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#title_display' => 'invisible',
      '#options' => $entity_types,
      '#required' => TRUE,
      '#default_value' => $entity_type,
    ];
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity id'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#error_no_message' => TRUE,
      '#size' => 6,
      '#maxlength' => 10,
      '#default_value' => $entity_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
    ];

    $form['#attached']['library'][] = 'entity_jump_menu/entity_jump_menu.form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $entity_id = $form_state->getValue('entity_id');
    $entity = entity_load($entity_type, $entity_id);
    if (!$entity) {
      $entity_types = $this->getEntityTypes();
      $t_args = ['%entity_type' => $entity_types[$entity_type], '%entity_id' => $entity_id];
      // Manually display error message so the elements error message is
      // displayed inline.
      drupal_set_message($this->t('There are no entities matching "%entity_type:%entity_id".', $t_args), 'error');
      $form_state->setErrorByName('entity_id', '');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $entity_id = $form_state->getValue('entity_id');
    $entity = entity_load($entity_type, $entity_id);
    $form_state->setRedirectUrl($entity->toUrl());
  }

  /**
   * Get an associative array of entity types listed in the jump menu.
   *
   * @return array
   *   An associative array of entity types.
   */
  protected function getEntityTypes() {
    $entity_types = [];
    $entity_types['node'] = $this->t('node');
    $entity_types['user'] = $this->t('user');
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $entity_types['taxonomy_term'] = $this->t('term');
    }
    return $entity_types;
  }

  /**
   * Get current request's entity type and id.
   *
   * @return array
   *   An array containing the entity type and id.
   */
  protected function getCurrentRequestsEntity() {
    $entity_types = $this->getEntityTypes();
    foreach ($entity_types as $entity_type => $entity_label) {
      $entity = $this->getRouteMatch()->getParameter($entity_type);
      if ($entity instanceof EntityInterface) {
        return [$entity->getEntityTypeId(), $entity->id()];
      }
    }
    return ['node', NULL];
  }

}
