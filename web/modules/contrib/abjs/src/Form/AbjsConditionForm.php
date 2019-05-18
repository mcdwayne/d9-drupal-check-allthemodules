<?php

namespace Drupal\abjs\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\Time;

/**
 * Class for build form condition.
 */
class AbjsConditionForm extends FormBase {

  /**
   * Current account user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Provides database connection service.
   *
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * Provides a class for obtaining system time.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account, Database $database, Time $time) {
    $this->account = $account;
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abjs_condition';
  }

  /**
   * Building the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   * @param int $cid
   *   The ID of the item.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cid = NULL) {
    $form = [];
    $condition_name_default = "";
    $condition_script_default = "";
    if (!empty($cid)) {
      $condition_result = $this->database->getConnection()->query('SELECT name, script FROM {abjs_condition} WHERE cid = :cid', [':cid' => $cid]);
      $condition = $condition_result->fetchObject();
      if (empty($condition)) {
        $this->messenger()->addMessage($this->t('The requested condition does not exist.'), 'error');
        return $form;
      }
      $condition_name_default = $condition->name;
      $condition_script_default = $condition->script;
      $form['cid'] = ['#type' => 'value', '#value' => $cid];
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Condition Name'),
      '#default_value' => $condition_name_default,
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $form['script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Condition Script'),
      '#default_value' => $condition_script_default,
      '#description' => $this->t('Any valid javascript with a return statement at the end, returning true or false. Read the documentation for examples'),
      '#rows' => 3,
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 5,
      '#submit' => ['::saveCondition'],
      '#attributes' => ['class' => ["button button-action button--primary"]],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => 10,
      '#submit' => ['::cancelCondition'],
      '#limit_validation_errors' => [],
    ];
    if (!empty($cid)) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#weight' => 15,
        '#submit' => ['::deleteCondition'],
      ];
    }

    // Add ace code editor for syntax highlighting on the script field.
    if ($this->configFactory()->get('abjs.settings')->get('ace') == 1) {
      $form['#attached']['library'][] = 'abjs/ace-editor';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Save data.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function saveCondition(array &$form, FormStateInterface $form_state) {
    $user = $this->account;
    if ($form_state->hasValue('cid')) {
      // This is a modified condition, so use update.
      $this->database->getConnection()->update('abjs_condition')
        ->fields([
          'name' => $form_state->getValue('name'),
          'script' => $form_state->getValue('script'),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])
        ->condition('cid', $form_state->getValue('cid'), '=')
        ->execute();
      $this->messenger()->addMessage($this->t("Successfully updated condition"));

    }
    else {
      // This is a new condition, so use insert.
      $this->database->getConnection()->insert('abjs_condition')
        ->fields([
          'name' => $form_state->getValue('name'),
          'script' => $form_state->getValue('script'),
          'created' => $this->time->getRequestTime(),
          'created_by' => $user->id(),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])->execute();
      $this->messenger()->addMessage($this->t("Successfully saved new condition"));

    }
    $form_state->setRedirect('abjs.condition_admin');
  }

  /**
   * Cancel the action.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function cancelCondition(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.condition_admin');
  }

  /**
   * Delete item.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function deleteCondition(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.condition_delete_confirm_form', ['cid' => $form_state->getValue('cid')]);
  }

}
