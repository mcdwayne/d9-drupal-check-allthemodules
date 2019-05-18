<?php

namespace Drupal\entity_switcher\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for add/edit entity switcher forms.
 */
class SwitcherForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_switcher\Entity\SwitcherInterface $entity */
    $entity = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add switcher setting');
    }
    else {
      $form['#title'] = $this->t('Edit %label switcher setting', ['%label' => $entity->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $entity->label(),
      '#description' => $this->t('The human-readable name of this switcher setting. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\entity_switcher\Entity\Switcher', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this switcher setting. It must only contain lowercase letters, numbers, and underscores.'),
    ];
    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity->getDescription(),
    ];
    $form['data_off'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for @value value', ['@value' => $this->t('off')]),
      '#default_value' => $entity->getDataOff(),
    ];
    $form['data_on'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label for @value value', ['@value' => $this->t('on')]),
      '#default_value' => $entity->getDataOn(),
    ];
    $form['default_value'] = [
      '#type' => 'select',
      '#options' => [
        'data_off' => $this->t('Off'),
        'data_on' => $this->t('On'),
      ],
      '#title' => $this->t('Default value'),
      '#default_value' => $entity->getDefaultValue(),
      '#required' => TRUE,
    ];
    $form['container_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes for @element', ['@element' => $this->t('container')]),
      '#default_value' => $entity->getContainerClasses(),
    ];
    $form['slider_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes for @element', ['@element' => $this->t('slider')]),
      '#default_value' => $entity->getSliderClasses(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t('Invalid machine-readable name. Enter a name other than %invalid.', ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_switcher\Entity\SwitcherInterface $entity */
    $entity = $this->entity;

    $entity->set('id', trim($entity->id()));
    $entity->setLabel(trim($entity->label()));
    $entity->setDescription(trim($entity->getDescription()));
    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The switcher setting %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The switcher setting %name has been added.', $t_args));
      $context = array_merge($t_args, [
        'link' => $entity->toLink($this->t('View'),
          'collection')->toString()
      ]);
      $this->logger('entity_switcher')->notice('Added switcher setting %name.', $context);
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['submit']['#value'] = $this->t('Save switcher setting');
    $actions['delete']['#value'] = $this->t('Delete switcher setting');

    return $actions;
  }

}
