<?php

namespace Drupal\access_conditions\Form;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\access_conditions\Entity\AccessModelInterface;
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
   * The access model entity.
   *
   * @var \Drupal\access_conditions\Entity\AccessModelInterface
   */
  protected $accessModel;

  /**
   * Constructs a ConditionAddForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $manager
   *   The condition plugin manager.
   */
  public function __construct(ConditionManager $manager) {
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
    return 'access_conditions_condition_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion($id = NULL) {
    $condition = $this->accessModel->getAccessCondition($id);

    return $this->t('Are you sure you want to delete the @label condition?', [
      '@label' => $condition->getPluginId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.access_model.edit_form', ['access_model' => $this->accessModel->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccessModelInterface $access_model = NULL, $id = NULL) {
    $this->accessModel = $access_model;

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
      '#value' => $id,
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
    $conditions = $this->accessModel->getAccessConditions()->getConfiguration();

    $id = $form_state->getValue('id');
    if (array_key_exists($id, $conditions)) {
      unset($conditions[$id]);
      $this->accessModel->set('access_conditions', $conditions);
      $this->accessModel->save();
      drupal_set_message($this->t('The access model condition has been deleted.'));
    }

    $form_state->setRedirect('entity.access_model.edit_form', ['access_model' => $this->accessModel->id()]);
  }

}
