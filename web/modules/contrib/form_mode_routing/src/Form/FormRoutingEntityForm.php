<?php

namespace Drupal\form_mode_routing\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormRoutingEntityForm.
 */
class FormRoutingEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form_routing_entity = $this->entity;
    $access = $form_routing_entity->getAccess();
    $form['info'] = [
      '#markup' => $this->t('<a href="/admin/structure/display-modes/form/add/node">Create New Form mode</a> then add to your content type.'),
    ];
    $form_modes = \Drupal::service('entity_display.repository')->getAllFormModes();
    if (!empty($form_modes['node'])) {
      //dump($form_modes['node']);
      $form_mode_options = [];
      foreach ($form_modes['node'] as $v) {
        $id = $v['id'];
        $form_mode_options[$id] = $v['id'];
      }
      $these_entities = \Drupal::service('entity_type.manager')->getStorage('form_routing_entity')->loadMultiple();
      if (count($these_entities) != 0) {
        foreach ($these_entities as $from_mode_entity) {
          $lab = $from_mode_entity->label();
          unset($form_mode_options[$lab]);
        }
      }

      if (empty($form_mode_options) && empty($from_mode_entity->label())) {
        drupal_set_message('You need to create more Form modes');
      }
      //$types = \Drupal::service('entity_typemanager')->getStorage('node')->loadMultiple();

      $bundle_label = \Drupal::entityTypeManager()
        ->getStorage('node_type')->loadMultiple();


      $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
      $role_op = [];
      foreach ($roles as $role_id => $role_obj) {
        $role_op[$role_id] = $role_obj->get('label');
      }

      if (!empty($from_mode_entity) && !empty($from_mode_entity->label())) {
        $form_mode_options[$from_mode_entity->label()]  = $from_mode_entity->label();
      }

      $form['label'] = [
        '#type' => 'select',
        '#options' => $form_mode_options,
        '#title' => $this->t('Form Mode'),
        '#maxlength' => 255,
        '#default_value' => $form_routing_entity->label(),
        '#description' => $this->t("Label for the Form routing entity."),
        '#required' => TRUE,
      ];

      $form['id'] = [
        '#type' => 'machine_name',
        '#default_value' => $form_routing_entity->id(),
        '#machine_name' => [
          'exists' => '\Drupal\form_mode_routing\Entity\FormRoutingEntity::load',
        ],
        '#disabled' => !$form_routing_entity->isNew(),
      ];

      $form['path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path'),
        '#description' => $this->t("must include {node} some where in it example /something/{node}/something"),
        '#required' => TRUE,
        '#default_value' => $form_routing_entity->path,
      ];

      $form['access']= [
        '#type' => 'checkboxes',
        '#options' => $role_op,
        '#title' => $this->t('Role that can Access'),
        '#description' => $this->t("what roles can access this form mode"),
        '#required' => TRUE,
      ];
      if (!empty($access)) {
        $form['access']['#default_value'] = array_values($access);
      }

    }
    else {
      drupal_set_message($this->t('Please create some form modes first.'));
    }
    return $form;
  }


  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    //$form_state->setErrorByName('path', 'Nope');

    $access = $form_state->getValue('access');
    $striped_access = [];
    foreach ($access as $key => $val) {
      if (!empty($val)) {
        $striped_access[$key] = $val;
      }
    }

   if (count($striped_access) == 0) {
     $form_state->setErrorByName('access', 'You need at least one access role.');
   }
   else {
     $form_state->setValue('access', $striped_access);
   }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_routing_entity = $this->entity;
    $access = $form_state->getValue('access');
    $form_routing_entity->setAccess($access);

    $status = $form_routing_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Form routing entity.', [
          '%label' => $form_routing_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Form routing entity.', [
          '%label' => $form_routing_entity->label(),
        ]));
    }
    // trigger cache rebuild.
    drupal_flush_all_caches();
    $form_state->setRedirectUrl($form_routing_entity->toUrl('collection'));
  }

}
