<?php

/**
 * @file
 * Contains Drupal\naming\NamingCategoryForm.
 */

namespace Drupal\naming;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for NamingCategory add and edit forms.
 */
class NamingCategoryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\naming\Entity\NamingCategory $naming_category */
    $naming_category = $this->getEntity();
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $naming_category->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#machine_name' => [
        'exists' => ['Drupal\naming\Entity\NamingCategory', 'load'],
        'error' => $this->t('The id must contain only lowercase letters, numbers, and underscores.'),
      ],
      '#description' => 'The id must contain only lowercase letters, numbers, and underscores.',
      '#standalone' => TRUE,
      '#required' => TRUE,
      '#default_value' => $naming_category->id(),
    ];
    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#default_value' => $naming_category->getContent()['value'],
      '#format' => $naming_category->getContent()['format'] ?: filter_default_format(),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $naming_category->getWeight(),
      '#delta' => 10,
      '#description' => $this->t('Used to sort naming categories when generating documentation.'),
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\naming\Entity\NamingCategory $naming_category */
    $naming_category = $this->getEntity();
    $naming_category->save();

    $this->logger('naming')->notice('Naming category @label saved.', ['@label' => $naming_category->label()]);
    drupal_set_message($this->t('Naming category %label saved.', ['%label' => $naming_category->label()]));

    $form_state->setRedirect('entity.naming_category.collection');
  }

}
