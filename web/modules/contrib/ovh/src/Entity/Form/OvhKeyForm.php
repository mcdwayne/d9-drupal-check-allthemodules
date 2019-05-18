<?php

namespace Drupal\ovh\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ovh\OvhHelper;

/**
 * Class OvhKeyForm.
 *
 * @package Drupal\ovh\Form
 *
 * @ingroup myx_newsreader
 */
class OvhKeyForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   *
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the robot add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);
    $config = OvhHelper::getConfig();

    $fields = self::getFields();
    foreach ($fields as $field => $label) {
      $form['fields'][$field] = [
        '#type' => 'textfield',
        '#title' => $this->t($label),
        '#maxlength' => 255,
        '#default_value' => $this->entity->get($field),
        '#required' => TRUE,
      ];
    }
    $form['fields']['endpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Endpoint'),
      '#options' => $config->get('endpoints'),
      '#default_value' => $this->entity->get('endpoint'),
      '#required' => TRUE,
      '#description' => $this->t('More on GitHub : <a href="@link">@link</a>', ['@link' => 'https://github.com/ovh/php-ovh/#supported-apis']),
    ];

    $link_gen = 'https://api.ovh.com/createToken/index.cgi?GET=/*&PUT=/*&POST=/*&DELETE=/*';
    $link_home = 'https://api.ovh.com';
    $args = [
      '@link_home' => $link_home,
      '@link_homet' => $link_home,
      '@link_gen' => $link_gen,
      '@link_gent' => $link_gen,
    ];
    $markup = '';
    $markup .= $this->t('Your can generate a new api key from <a target="_new" href="@link_gen">@link_gent</a>.', $args);
    $markup .= '<br>';
    $markup .= $this->t('Ovh API Home page : <a target="_new" href="@link_home">@link_homet</a>', $args);
    $form['text1'] = [
      '#type' => 'markup',
      '#title' => $this->t('Generate API KEY'),
      '#markup' => $markup,
    ];

    // Return the form.
    return $form;
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
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    // $actions['submit']['#value'] = $this->t('Save');
    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // Add code here to validate your config entity's form elements.
    // Nothing to do here.
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

    // Set Entity ID.
    $entity = $this->entity;
    if ($entity->isNew() && !$entity->id()) {
      $result = $this->entityQueryFactory->get('ovh_api_key')->execute();
      $id_auto = max($result) + 1;
      $this->entity->set('id', $id_auto);
    }

    // Save.
    parent::save($form, $form_state);

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('ovh.root');
  }

  /**
   *
   */
  private static function getFields() {
    return [
      'label' => 'Label',
      'app_key' => 'Application Key',
      'app_sec' => 'Application Secret',
      'con_key' => 'Consumer Key',
    ];
  }

}
