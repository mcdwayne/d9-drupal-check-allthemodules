<?php

namespace Drupal\contacts_events\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * The event class form.
 */
class EventClassForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\contacts_events\Entity\EventClassInterface $class */
    $class = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $class->label(),
      '#description' => $this->t("Label for the Class."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $class->id(),
      '#machine_name' => [
        'exists' => '\Drupal\contacts_events\Entity\EventClass::load',
      ],
      '#disabled' => !$class->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $class->status(),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#empty_option' => $this->t('Global'),
      '#empty_value' => 'global',
      '#default_value' => $class->get('type'),
      '#options' => [],
    ];

    $order_item_types = $this->entityTypeManager
      ->getStorage('commerce_order_item_type')
      ->loadMultiple();
    foreach ($order_item_types as $type) {
      $form['type']['#options'][$type->id()] = $type->label();
    }

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $class->get('weight') ?? 0,
      '#delta' => 20,
    ];

    $form['selectable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This class is selectable'),
      '#description' => $this->t('If an item has multiple selectable types, the user will be presented with a choice.'),
      '#default_value' => $class->get('selectable'),
    ];

    $expression = $class->get('expression');
    $form['expression'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition configuration'),
      '#default_value' => $expression ? Yaml::encode($expression) : NULL,
      '#rows' => 20,
      '#value_callback' => [$this, 'decodeYaml'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // FormBuilder uses the submitted value if it gets a NULL response from a
    // #value_callback, but we want NULL, not an empty string.
    if (!$form_state->getValue('expression')) {
      $entity->set('expression', NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contacts_events_class = $this->entity;
    $status = $contacts_events_class->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Class.', [
          '%label' => $contacts_events_class->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Class.', [
          '%label' => $contacts_events_class->label(),
        ]));
    }
    $form_state->setRedirectUrl($contacts_events_class->toUrl('collection'));
  }

  /**
   * Element value callback to decode YAML into a config array.
   *
   * @param array $element
   *   The element.
   * @param string|false|null $input
   *   The input.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|null
   *   The decoded array or NULL if there is no value
   */
  public function decodeYaml(array $element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE && $input !== NULL) {
      return Yaml::decode($input);
    }
    return NULL;
  }

}
