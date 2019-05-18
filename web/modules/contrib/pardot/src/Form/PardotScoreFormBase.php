<?php

namespace Drupal\pardot\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PardotScoreFormBase.
 *
 * @package Drupal\pardot\Form
 *
 * @ingroup pardot
 */
class PardotScoreFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $query_factory;

  /**
   * The path condition.
   *
   * @var \Drupal\system\Plugin\Condition\RequestPath
   */
  protected $path_condition;

  /**
   * Construct the PardotScoreFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory for the score entity type.
   */
  public function __construct(QueryFactory $query_factory, ExecutableManagerInterface $plugin_factory) {
    $this->query_factory = $query_factory;
    $this->path_condition = $plugin_factory->createInstance('request_path');
  }

  /**
   * Factory method for PardotScoreFormBase.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the score add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    $score = $this->entity;

    // Build the form.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $score->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $score->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
      '#disabled' => !$score->isNew(),
    );
    $form['score_value'] = array(
      '#type' => 'number',
      '#title' => $this->t('Score Value'),
      '#description' => $this->t('Assign a score value for the path configured below. This score will be assigned and reported Pardot.'),
      '#default_value' => $score->score_value,
    );

    // Set the path condition.
    if (isset($score->pages)) {
      $this->path_condition->setConfiguration($score->pages);
    }
    else {
      $this->path_condition->setConfiguration(array());
    }

    // Build the path_condition configuration form elements.
    $form += $this->path_condition->buildConfigurationForm($form, $form_state);
    unset($form['negate']);

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing Pardot Score.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this Pardot Score already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new Pardot Score entity query.
    $query = $this->query_factory->get('pardot_score');

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   *
   * @Todo: Validate pages are entered as <front> or with preceding slash.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Add code here to validate your config entity's form elements.
    // Nothing to do here...yet
  }

  /**
   * {@inheritdoc}
   *
   * Converts submitted form values into plugin configuration array.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submit condition plugin configurations.
    $this->path_condition->submitConfigurationForm($form, $form_state);

    $form_state->setValue('pages', $this->path_condition->getConfiguration());

    parent::submitForm($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $score = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $score->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $score->urlInfo();

    // Create an edit link.
    $edit_link = $this->l($this->t('Edit'), $url);

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('Pardot Score %label has been updated.', array('%label' => $score->label())));
      $this->logger('contact')->notice('Pardot Score %label has been updated.', ['%label' => $score->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('Pardot Score %label has been added.', array('%label' => $score->label())));
      $this->logger('contact')->notice('Pardot Score %label has been added.', ['%label' => $score->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('pardot.scores.list');
  }

}
