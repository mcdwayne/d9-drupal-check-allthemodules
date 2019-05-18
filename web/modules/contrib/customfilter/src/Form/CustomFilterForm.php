<?php

namespace Drupal\customfilter\Form;

// Load base class for the controller.
use Drupal\Core\Entity\EntityForm;

// Load the Drupal interface for the current state of a form.
use Drupal\Core\Form\FormStateInterface;
use Drupal\customfilter\Entity\CustomFilter;

class CustomFilterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\customfilter\Entity\CustomFilter $filter */
    $filter = $this->entity;

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $filter->label(),
      '#description' => $this->t("Label for the filter."),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $filter->id(),
      '#description' => $this->t('The machine-readable name of the filter. This name
         must contain only lowercase letters, numbers, and underscores and
         can not be changed latter'),
      '#machine_name' => array(
        'exists' => [CustomFilter::class, 'load'],
        'source' => ['name'],
      ),
      '#disabled' => !$filter->isNew(),
      '#required' => TRUE,
    );

    $form['cache'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Cache'),
      '#description' => $this->t('If checked, the content will be cached.'),
      '#default_value' => $filter->getCache(),
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A description of the purpose of this filter.'),
      '#default_value' => $filter->getDescription(),
    );

    $form['shorttip'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Tips (short)'),
      '#default_value' => $filter->getShorttip(),
      '#rows' => 5,
      '#description' => $this->t('This tip will be show in edit content/comments forms.'),
    );

    $form['longtip'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Tips (full)'),
      '#default_value' => $filter->getLongtip(),
      '#rows' => 20,
    );

    $form['rules'] = array(
      '#type' => 'value',
      '#value' => $filter->rules,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $filter = $this->entity;
    $status = $filter->save();
    if ($status) {
      drupal_set_message($this->t('Saved the %label Custom Filter.', array(
          '%label' => $filter->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Custom Filter was not saved.', array(
          '%label' => $filter->label(),
      )));
    }
    $form_state->setRedirect('entity.customfilter.list');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $form_state->setRedirect('entity.customfilter.delete_form', array($this->entity->id()), array('query' => $destination));
  }

}
