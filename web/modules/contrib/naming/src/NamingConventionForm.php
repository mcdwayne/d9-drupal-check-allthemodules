<?php

/**
 * @file
 * Contains Drupal\naming\NamingConventionForm.
 */

namespace Drupal\naming;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\naming\Entity\NamingCategory;

/**
 * Base form for NamingConvention add and edit forms.
 */
class NamingConventionForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\naming\Entity\NamingConvention $naming_convention */
    $naming_convention = $this->getEntity();
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $naming_convention->label(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Route/Custom ID'),
      '#machine_name' => [
        'exists' => ['Drupal\naming\Entity\NamingConvention', 'load'],
        'replace_pattern' => '[^a-z0-9_.]+',
        'error' => $this->t('The machine-readable name must contain only lowercase letters, numbers, periods, and underscores.'),
      ],
      '#description' => 'The route/custom id must contain only lowercase letters, numbers, periods, and underscores.',
      '#standalone' => TRUE,
      '#required' => TRUE,
      '#default_value' => $naming_convention->id(),
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('The main path that this naming convention should be associated with.'),
      '#maxlength' => 255,
      '#default_value' => $naming_convention->getPath(),
    ];
    $form['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#required' => TRUE,
      '#default_value' => $naming_convention->getContent()['value'],
      '#format' => $naming_convention->getContent()['format'] ?: filter_default_format(),
    ];
    $category_options = ['' => '<None>'];
    $categories = NamingCategory::loadMultiple();
    foreach ($categories as $category) {
      $category_options[$category->id()] = $category->label();
    }
    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $category_options,
      '#default_value' => $naming_convention->getCategory(),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $naming_convention->getWeight(),
      '#delta' => 10,
      '#description' => $this->t('Used to sort naming conventions when generating documentation.'),
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($path = $form_state->getValue('path')) {
      if (!\Drupal::service('path.validator')->isValid($path)) {
        $form_state->setErrorByName('path', $this->t('Please a valid path'));
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\naming\Entity\NamingConvention $naming_convention */
    $naming_convention = $this->getEntity();
    $naming_convention->save();

    $this->logger('naming')->notice('Naming convention @label saved.', ['@label' => $naming_convention->label()]);
    drupal_set_message($this->t('Naming convention %label saved.', ['%label' => $naming_convention->label()]));

    $form_state->setRedirect('entity.naming_convention.collection');
  }

}
