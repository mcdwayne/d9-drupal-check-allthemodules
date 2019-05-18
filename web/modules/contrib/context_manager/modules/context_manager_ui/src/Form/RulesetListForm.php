<?php

namespace Drupal\context_manager_ui\Form;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Context Rulesets.
 */
class RulesetListForm extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  /*public function getFormId() {
    return 'context_manager_ui_ruleset_list_form';
  }*/

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['description'] = $this->t('Description');
    $header['tag'] = $this->t('Tag');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $ruleset) {
    $row['label'] = $ruleset->label() . '' . $ruleset->getOriginalId();
    $row['description'] = $ruleset->get('description');
    $row['tag'] = $ruleset->get('tag');
    return $row + parent::buildRow($ruleset);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit']['url'] = new Url('entity.context_ruleset.edit_form', ['machine_name' => $entity->id(), 'step' => 'general']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    // Change the default & ugly empty text line.
    $build['table']['#empty'] = $this->t('There are no rulesets yet.');
    return $build;
  }

  /**
   * Internal method that builds a filter form elements for the
   * Overview Page Form.
   *
   * Find a way to build a filter on top of the existing page.
   */
  /*protected function buildFilter(array &$form, FormStateInterface $form_state) {

    $form['filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#attributes' => ['class' => [
        'container-inline',
      ]],
      '#open' => TRUE,
    ];

    $form['filter']['tags'] = [
      '#type' => 'select',
      '#title' => $this->t('Tags'),
      '#title_display' => 'invisible',
      // TODO: Replace with real list of tags.
      '#options' => [
        1 => 'Front page',
        2 => 'Contacts',
      ],
      '#empty_option' => $this->t('- Filter by tag -'),
    ];

    $form['filter']['plugins'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugins'),
      '#title_display' => 'invisible',
      // TODO: Replace with real plugin info.
      '#options' => [
        1 => 'Breadcrumbs',
        2 => 'Metatags',
        3 => 'Lorem ipsum',
      ],
      '#empty_option' => $this->t('- Filter by plugin -'),
    ];

    $form['filter']['label'] = [
      '#type' => 'search',
      '#title' => $this->t('Label'),
      '#title_display' => 'invisible',
      '#size' => 20,
      '#attributes' => [
        'placeholder' => $this->t('Filter by label'),
      ],
    ];

    $form['filter']['actions']['#type'] = 'actions';
    $form['filter']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
  }*/

}
