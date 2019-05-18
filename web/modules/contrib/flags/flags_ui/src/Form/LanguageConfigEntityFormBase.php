<?php

namespace Drupal\flags_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flags\FullLanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\flags\Entity\FlagMapping;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigEntityFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity. Instead of duplicating our form code,
 * we create a base class. Drupal never routes to this class directly,
 * but instead through the child classes of ConfigAddForm and ConfigEditForm.
 *
 * @package Drupal\flags_languages\Form
 *
 * @ingroup flags_languages
 */
class LanguageConfigEntityFormBase extends EntityForm {

  /**
   * The configurable language manager.
   *
   * @var FullLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Array of all flags with their names.
   *
   * @var string[]
   */
  protected $flags;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param FullLanguageManagerInterface $language_manager
   *   The configurable language manager.
   * @param string[] $flags
   *   Array of all available flags with their names.
   */
  public function __construct($language_manager, $flags) {
    $this->languageManager = $language_manager;
    $this->flags = $flags;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flags.language_helper'),
      $container->get('flags.manager')->getList()
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An instance of FormStateInterface containing the current state of the form.
   *
   * @return array
   *   An associative array containing the FlagMapping add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    // Drupal provides the entity to us as a class variable. If this is an
    // existing entity, it will be populated with existing values as class
    // variables. If this is a new entity, it will be a new object with the
    // class of our entity. Drupal knows which class to call from the
    // annotation on our FlagMapping class.
    /** @var FlagMapping $mapping */
    $mapping = $this->entity;

    $languages = \Drupal::service('flags.language_helper')
      ->getAllDefinedLanguages();

    if (\Drupal::moduleHandler()->moduleExists('select_icons')) {
      $flagAttributes = $this->getAttributes(array_keys($this->flags));
      $languageAttributes = $this->getAttributes(array_keys($languages));

      $form['source'] = [
        '#type' => 'select_icons',
        '#options_attributes' => $languageAttributes,
      ];

      $form['flag'] = [
        '#type' => 'select_icons',
        '#options_attributes' => $flagAttributes,
        '#attached' => array('library' => array('flags/flags')),
      ];

    }
    else {
      $form['source']['#type'] = 'select';
      $form['flag']['#type'] = 'select';
    }

    $form['source'] += [
      '#title' => $this->t('Language'),
      '#default_value' => $mapping->getSource(),
      '#description' => $this->t('Select a source language.'),
      '#options' => $this->languageManager->getAllDefinedLanguages(),
      '#required' => TRUE,
    ];

    $form['flag'] += [
      '#title' => $this->t('Flag'),
      '#options' => $this->flags,
      '#empty_value' => '',
      '#default_value' => $mapping->getFlag(),
      '#description' => $this->t('Select a target territory flag.'),
      '#required' => TRUE,
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
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
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
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    /** @var FlagMapping $mapping */
    $mapping = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $mapping->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $mapping->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addStatus($this->t('Mapping %label has been updated.', array('%label' => $mapping->label())));
      $this->logger('flags_languages')->notice('Mapping %label has been updated.', ['%label' => $mapping->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addStatus($this->t('Mapping %label has been added.', array('%label' => $mapping->label())));
      $this->logger('flags_languages')->notice('Mapping %label has been added.', ['%label' => $mapping->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.language_flag_mapping.list');

    return $status;
  }

  /**
   * Gets array with attributes for each option element.
   *
   * @return Attribute[]
   */
  protected function getAttributes($items) {
    $mapper = \Drupal::service('flags.mapping.language');
    $attributes = $mapper->getOptionAttributes($items);
    return $attributes;
  }

}
