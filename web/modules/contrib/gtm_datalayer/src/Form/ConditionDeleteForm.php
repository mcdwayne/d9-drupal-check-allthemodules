<?php

namespace Drupal\gtm_datalayer\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides delete form for condition instance forms.
 */
class ConditionDeleteForm extends ConfirmFormBase {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected $entity;

  /**
   * Constructs a ConditionAddForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $manager
   *   The condition plugin manager.
   */
  function __construct(ConditionManager $manager) {
    $this->conditionManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gtm_datalayer_condition_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion($id = NULL) {
    $condition = $this->entity->getAccessCondition($id);

    return $this->t('Are you sure you want to delete the @label condition?', [
      '@label' => $condition->getPluginId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.' . $this->entity->getEntityTypeId() . '.edit_form', [$this->entity->getEntityTypeId() => $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConfigEntityInterface $entity = NULL, $id = NULL) {
    $this->entity = $entity;

    $form['#title'] = $this->getQuestion($id);;
    $form['#theme'] = 'confirm_form';
    $form['#attributes']['class'][] = 'confirmation';

    $form['description'] = [
      '#markup' => $this->t('This action cannot be undone.'),
    ];
    $form['confirm'] = [
      '#type' => 'hidden',
      '#value' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $id
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conditions = $this->entity->getAccessConditions()->getConfiguration();

    $id = $form_state->getValue('id');
    if (array_key_exists($id, $conditions)) {
      unset($conditions[$id]);
      $this->entity->set('access_conditions', $conditions);
      $this->entity->save();
      drupal_set_message($this->t('The @label condition has been deleted.', ['@label' => Unicode::strtolower($this->entity->label())]));
    }

    $form_state->setRedirectUrl($this->getEditUrl());
  }

  /**
   * Returns the entity edit route.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  protected function getEditUrl() {
    $entity = $this->entity;

    if ($entity->hasLinkTemplate('edit-form')) {
      // If available, return the edit URL.
      return $entity->toUrl('edit-form');
    }
    else {
      // Otherwise fall back to the default link template.
      return $entity->toUrl();
    }
  }

}
