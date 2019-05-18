<?php

namespace Drupal\abjs\Form;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Class for build experience form.
 */
class AbjsExperienceForm extends FormBase {

  /**
   * Current account user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
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
  public function __construct(AccountInterface $account, Connection $database, Time $time) {
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
    return 'abjs_experience';
  }

  /**
   * Building form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   * @param int $eid
   *   The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $eid = NULL) {
    $form = [];
    $experience_name_default = "";
    $experience_script_default = "";
    if (!empty($eid)) {
      $experience_result = $this->database->query('SELECT name, script FROM {abjs_experience} WHERE eid = :eid', [':eid' => $eid]);
      $experience = $experience_result->fetchObject();
      if (empty($experience)) {
        $this->messenger()->addMessage($this->t('The requested experience does not exist.'), 'error');
        return $form;
      }
      $experience_name_default = $experience->name;
      $experience_script_default = $experience->script;
      $form['eid'] = ['#type' => 'value', '#value' => $eid];
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Experience Name'),
      '#default_value' => $experience_name_default,
      '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $form['script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Experience Script'),
      '#default_value' => $experience_script_default,
      '#description' => $this->t('Any valid javascript to load in head. Leave empty for a Control. Read the documentation for more examples.'),
      '#rows' => 3,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 5,
      '#submit' => ['::saveExperience'],
      '#attributes' => ['class' => ["button button-action button--primary"]],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#weight' => 10,
      '#submit' => ['::cancelExperience'],
      '#limit_validation_errors' => [],
    ];
    if (!empty($eid)) {
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#weight' => 15,
        '#submit' => ['::deleteExperience'],
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
  public function saveExperience(array &$form, FormStateInterface $form_state) {
    $user = $this->account;
    if ($form_state->hasValue('eid')) {
      // This is a modified experience, so use update.
      $this->database->update('abjs_experience')
        ->fields([
          'name' => $form_state->getValue('name'),
          'script' => $form_state->getValue('script'),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])
        ->condition('eid', $form_state->getValue('eid'), '=')
        ->execute();
      $this->messenger()->addMessage($this->t("Successfully updated experience"));

    }
    else {
      // This is a new experience, so use insert.
      $this->database->insert('abjs_experience')
        ->fields([
          'name' => $form_state->getValue('name'),
          'script' => $form_state->getValue('script'),
          'created' => $this->time->getRequestTime(),
          'created_by' => $user->id(),
          'changed' => $this->time->getRequestTime(),
          'changed_by' => $user->id(),
        ])->execute();
      $this->messenger()->addMessage($this->t("Successfully saved new experience"));

    }
    $form_state->setRedirect('abjs.experience_admin');
  }

  /**
   * Cancel the action.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function cancelExperience(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.experience_admin');
  }

  /**
   * Delete item.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of forms.
   */
  public function deleteExperience(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('abjs.experience_delete_confirm_form', ['eid' => $form_state->getValue('eid')]);
  }

}
