<?php

namespace Drupal\contacts_dbs\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contacts_dbs\Entity\DBSWorkforce;

/**
 * Form controller for workforce forms.
 */
class DBSWorkforceForm extends EntityForm {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\contacts_dbs\Entity\DBSWorkforceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    if ($this->operation === 'add') {
      $form['#title'] = $this->t('Add DBS workforce');
    }

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The human-readable name of this workforce.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength' => 32,
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => [DBSWorkforce::class, 'load'],
      ],
      '#description' => $this->t('A unique machine-readable name for this workforce.'),
    ];

    $form['valid'] = [
      '#title' => $this->t('Valid for'),
      '#type' => 'number',
      '#min' => 1,
      '#description' => $this->t('The number of years this workforce is valid for.'),
      '#default_value' => $this->entity->getValidity() ?? '5',
    ];

    $workforces = $this->entityTypeManager->getStorage('dbs_workforce')->loadMultiple();
    $options = [];
    foreach ($workforces as $id => $workforce) {
      // Skip if workforce is current entity.
      if ($id == $this->entity->id()) {
        continue;
      }
      $options[$id] = $workforce->label();
    }

    $form['alternatives'] = [
      '#title' => $this->t('Alternatives'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#description' => $this->t('Allow DBS checks for these workforces to count instead of this one.'),
      '#default_value' => $this->entity->getAlternatives() ?? [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    if ($form_state->hasValue('alternatives')) {
      $alternatives = array_values(array_filter($form_state->getValue('alternatives')));
      $entity->set('alternatives', $alternatives);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
    $actions['delete']['#value'] = $this->t('Delete');
    $actions['delete']['#access'] = $this->entity->access('delete');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $t_args = ['%name' => $this->entity->label()];
    if ($status === SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The workforce %name has been updated.', $t_args));
    }
    elseif ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The workforce %name has been added.', $t_args));
      $this->logger('contacts_dbs')->notice('Added workforce %name.', $t_args);
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
