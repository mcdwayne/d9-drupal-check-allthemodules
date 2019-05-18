<?php

namespace Drupal\assembly\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\assembly\Entity\AssemblyInterface;
use Drupal\assembly\Entity\AssemblyType;

class AssemblyInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    $fields['status'] = [
      'type' => 'field',
      'label' => $this->t('Status'),
      'weight' => 100,
      'display_options' => [
        'settings' => [
          'format' => 'custom',
          'format_custom_false' => $this->t('Unpublished'),
          'format_custom_true' => $this->t('Published'),
        ],
      ],
    ];

    return $fields;

  }

  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    $entity = $entity_form['#entity'];
    if ($this->showRevisionUi($entity)) {
      $this->addRevisionableFormFields($entity_form, $entity);
      $entity_form['revision']['#attributes']['data-assembly-revision-checkbox'] = '';
      $entity_form['revision']['#weight'] = 99;
      $entity_form['revision_log_message']['#attributes']['data-assembly-revision-log'] = '';
      $entity_form['revision_log_message']['#weight'] = 100;
      $config = \Drupal::config('assembly.settings');
      if (!$config->get('inline_revision_message')) {
        $entity_form['revision_log_message']['#access'] = false;
      }

    }

    $bundle = AssemblyType::load($entity->bundle());
    if ($styles = $bundle->getVisualStylesParsed()) {
      $entity_form['visual_styles']['widget']['#description'] = ['#markup' => $bundle->getVisualStylesHelp($entity->getUuid())];
    }
    else {
      $entity_form['visual_styles']['#access'] = FALSE;
    }

    $entity_form['#attached']['library'][] = 'assembly/form';
    return $entity_form;

  }

  /**
   * Add revision form fields if the entity enabled the UI.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  protected function addRevisionableFormFields(array &$form, AssemblyInterface $entity) {
    $entity_type = $entity->getEntityType();
    $new_revision_default = $this->getNewRevisionDefault($entity);

    $form['revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $new_revision_default,
      '#access' => !$entity->isNew() && $entity->get($entity_type->getKey('revision'))->access('update'),
      '#weight' => 24,
    ];

  }

  /**
   * Returns the bundle entity of the entity, or NULL if there is none.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The bundle entity.
   */
  protected function getBundleEntity($entity) {
    if ($bundle_entity_type = $entity->getEntityType()->getBundleEntityType()) {
      return $this->entityTypeManager->getStorage($bundle_entity_type)->load($entity->bundle());
    }
    return NULL;
  }

  /**
   * Should new revisions created on default.
   *
   * @return bool
   *   New revision on default.
   */
  protected function getNewRevisionDefault(AssemblyInterface $entity) {
    $new_revision_default = FALSE;
    $bundle_entity = $this->getBundleEntity($entity);
    if ($bundle_entity instanceof RevisionableEntityBundleInterface) {
      // Always use the default revision setting.
      $new_revision_default = $bundle_entity->shouldCreateNewRevision();
    }
    return $new_revision_default;
  }

  /**
   * Checks whether the revision form fields should be added to the form.
   *
   * @return bool
   *   TRUE if the form field should be added, FALSE otherwise.
   */
  protected function showRevisionUi(AssemblyInterface $entity) {
    return $entity->getEntityType()->showRevisionUi();
  }

}
