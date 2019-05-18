<?php

/**
 * @file
 * Contains Drupal\isotope\Form\PresentationFormBase.
 */

namespace Drupal\isotope\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PresentationFormBase.
 *
 * @package Drupal\isotope\Form
 *
 * @ingroup isotope
 */
class PresentationFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Construct the PresentationFormBase.
   *
   * For simple entity forms, there's no need for a constructor. Our
   * Presentation form base, however, requires an entity query factory to be
   * injected into it from the container. We later use this query factory to
   * build an entity query for the exists() method.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory for the Presentation entity type.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * Factory method for PresentationFormBase.
   *
   * When Drupal builds this class it does not call the constructor directly.
   * Instead, it relies on this method to build the new object. Why? The class
   * constructor may take multiple arguments that are unknown to Drupal. The
   * create() method always takes one parameter -- the container. The purpose
   * of the create() method is twofold: It provides a standard way for Drupal
   * to construct the object, meanwhile it provides you a place to get needed
   * constructor parameters from the container.
   *
   * In this case, we ask the container for an entity query factory. We then
   * pass the factory to our class as a constructor parameter.
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
   *   An associative array containing the Presentation add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    // Drupal provides the entity to us as a class variable. If this is an
    // existing entity, it will be populated with existing values as class
    // variables. If this is a new entity, it will be a new object with the
    // class of our entity. Drupal knows which class to call from the
    // annotation on our Presentation class.
    $presentation = $this->entity;

    // Build the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $presentation->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $presentation->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$presentation->isNew(),
    ];

    // Layout Modes.
    $layouts = [
      'cellsByColumn' => t('cellsByColumn'),
      'cellsByRow' => t('cellsByRow'),
      'fitColumns' => t('fitColumns'),
      'fitRows' => t('fitRows'),
      'horiz' => t('horiz'),
      'masonry' => t('masonry'),
      'masonryHorizontal' => t('masonryHorizontal'),
      'packery' => t('packery'),
      'vertical' => t('vertical'),
    ];

    $form['layout_mode'] = [
      '#type' => 'radios',
      '#options' => $layouts,
      '#title' => t('Isotope Layout Mode'),
      // '#description' => t('If desired options are disabled, you may need to install additional JS libraries. Refer to installation instructions.'),
      '#default_value' => ($presentation->layout_mode) ? $presentation->layout_mode : 'masonry',
      '#required' => FALSE,
    ];

    // Non-layout plugins.
    $plugins = [
      'imagesloaded' => t('imagesloaded'),
    ];
    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#options' => $plugins,
      '#title' => t('Additional Plugins'),
      // '#description' => t('If desired options are disabled, you may need to install additional JS libraries.'),
      '#default_value' => ($presentation->plugins) ? $presentation->plugins : ['imagesloaded'],
      '#required' => FALSE,
      '#tree' => TRUE,
    ];

    $form['transition_duration'] = [
      '#type' => 'textfield',
      '#title' => t('Transition Duration'),
      '#description' => t('In a format suitable for CSS transition-duration (e.g. "0.2s"). To disable all transitions, set to "0".'),
      '#default_value' => $presentation->transition_duration,
      '#required' => FALSE,
    ];

    $form['url_filters'] = [
      '#type' => 'select',
      '#options' => [
        0 => 'False',
        1 => 'True',
      ],
      '#title' => t('Use URL for Filters.'),
      '#description' => t('Filters are represented in URL for benefit of browser history, bookmarking, etc.'),
      '#default_value' => $presentation->url_filters,
      '#required' => FALSE,
    ];

    $form['is_fit_width'] = [
      '#type' => 'select',
      '#options' => [
        0 => 'False',
        1 => 'True',
      ],
      '#title' => t('isFitWidth'),
      '#description' => t("Sets the width of the container to fit the available number of columns, based the size of container's parent element. When enabled, you can center the container with CSS."),
      '#default_value' => $presentation->is_fit_width,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="layoutMode"]' => ["value" => 'masonry'],
        ],
      ],
    ];

    $form['is_horizontal'] = [
      '#type' => 'select',
      '#options' => [
        0 => 'False',
        1 => 'True',
      ],
      '#title' => t('isHorizontal'),
      '#description' => t('Arranges items horizontally instead of vertically.'),
      '#default_value' => $presentation->is_horizontal,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="layoutMode"]' => ["value" => 'packery'],
        ],
      ],
    ];

    $form['stamp'] = [
      '#type' => 'textfield',
      '#title' => t('Stamp Selector'),
      '#description' => t('Specifies elements that are stamped within the layout. These are special layout elements which will not be laid out. Rather, Isotope will layout item elements below stamped elements.'),
      '#default_value' => $presentation->stamp,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          [
            [':input[name="layoutMode"]' => ['value' => 'masonry']],
            [':input[name="layoutMode"]' => ['value' => 'packery']],
            [':input[name="layoutMode"]' => ['value' => 'masonryHorizontal']],
          ],
        ],
      ],
    ];

    $form['horizontal_alignment'] = [
      '#type' => 'textfield',
      '#title' => t('Horizontal Alignment (decimal number 0 to 1)'),
      '#description' => t('Aligns items horizontally. 0 will align the origin edge. 1 will align the opposite edge. 0.5 will align center.'),
      '#default_value' => $presentation->horizontal_alignment,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="layoutMode"]' => ["value" => 'vertical'],
        ],
      ],
    ];

    $form['vertical_alignment'] = [
      '#type' => 'textfield',
      '#title' => t('Vertical Alignment (decimal number 0 to 1)'),
      '#description' => t('Aligns items vertically. 0 will align the origin edge. 1 will align the opposite edge. 0.5 will align center.'),
      '#default_value' => $presentation->vertical_alignment,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="layoutMode"]' => ["value" => 'horizontal'],
        ],
      ],
    ];

    $form['is_origin_left'] = [
      '#type' => 'select',
      '#options' => [
        1 => 'Left to Right',
        0 => 'Right to Left',
      ],
      '#title' => t('Direction'),
      '#description' => t('Layout direction (implements "isOriginLeft").'),
      '#default_value' => $presentation->is_origin_left,
      '#required' => FALSE,
    ];

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing Presentation.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new Presentation entity query.
    $query = $this->entityQueryFactory->get('presentation');

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
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);

    // @TODO: validate config entity's form elements.
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
    // EntityForm provides us with the entity we're working on.
    $presentation = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $presentation->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $presentation->toUrl();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('Presentation %label has been updated.', ['%label' => $presentation->label()]));
      $this->logger('contact')
        ->notice('Presentation %label has been updated.', [
          '%label' => $presentation->label(),
          'link' => $edit_link,
        ]);
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('Presentation %label has been added.', ['%label' => $presentation->label()]));
      $this->logger('contact')
        ->notice('Presentation %label has been added.', [
          '%label' => $presentation->label(),
          'link' => $edit_link,
        ]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.presentation.list');
  }

}
