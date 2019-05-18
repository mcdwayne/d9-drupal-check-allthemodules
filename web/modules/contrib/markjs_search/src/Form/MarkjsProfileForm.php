<?php

namespace Drupal\markjs_search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define the MarkJS profile form.
 */
class MarkjsProfileForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\markjs_search\Entity\MarkjsProfileEntity $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t(
        "Input the label for the MarkJS profile."
      ),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\markjs_search\Entity\MarkjsProfileEntity::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration'),
      '#open' => TRUE,
      '#tree' => FALSE,
    ];
    $form['configuration']['element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element'),
      '#description' => $this->t('An HTML element to wrap matches.'),
      '#default_value' => $entity->element,
      '#required' => TRUE,
    ];
    $form['configuration']['class_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Class Name'),
      '#description' => $this->t('The class name that will be appended to 
        element.'),
      '#default_value' => $entity->class_name,
    ];
    $form['configuration']['exclude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude'),
      '#description' => $this->t('A comma delimited list of excluded selectors.'),
      '#default_value' => $entity->exclude,
    ];
    $form['configuration']['wildcard'] = [
      '#type' => 'select',
      '#title' => $this->t('Wildcard'),
      '#descriptions' => $this->t('Select the wildcard type.'),
      '#options' => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
        'withSpaces' => $this->t('With Spaces')
      ],
      '#default_value' => $entity->wildcard,
    ];
    $form['configuration']['accuracy'] = [
      '#type' => 'select',
      '#title' => $this->t('Accuracy'),
      '#description' => $this->t('Select the search term accuracy.'),
      '#options' => [
        'exactly' => $this->t('Exactly'),
        'partially' => $this->t('Partially'),
        'complementary' => $this->t('Complementary'),
      ],
      '#default_value' => $entity->accuracy,
    ];
    $form['configuration']['synonyms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Synonyms'),
      '#description' => $this->t('Define a JSON object with synonyms.'),
      '#default_value' => $entity->synonyms,
    ];
    $form['configuration']['iframes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('iFrames'),
      '#description' => $this->t('Search inside iframes.'),
      '#default_value' => $entity->iframes,
    ];
    $form['configuration']['iframes_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('iFrames Timeout'),
      '#description' => $this->t('The maximum ms to wait for a load event before 
        skipping an iframe.'),
      '#field_suffix' => 'ms',
      '#default_value' => $entity->iframes_timeout,
      '#states' => [
        'visible' => [
          ':input[name="iframes"]' => ['checked' => TRUE]
        ]
      ]
    ];
    $form['configuration']['diacritics'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Diacritics'),
      '#description' => $this->t('Search diacritic characters.'),
      '#default_value' => $entity->diacritics,
    ];
    $form['configuration']['case_sensitive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case Sensitive'),
      '#description' => $this->t('Search using case sensitive.'),
      '#default_value' => $entity->case_sensitive,
    ];
    $form['configuration']['across_elements'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Across Elements'),
      '#description' => $this->t('Search for matches across elements.'),
      '#default_value' => $entity->across_elements,
    ];
    $form['configuration']['separate_word_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Separate Word Search'),
      '#description' => $this->t('Search for each word separated by a blank 
        instead of the complete term.'),
      '#default_value' => $entity->separate_word_search,
    ];
    $form['configuration']['ignore_joiners'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Joiners'),
      '#description' => $this->t('Find matches that contain soft hyphen, zero 
        width space, zero width non-joiner and zero width joiner.'),
      '#default_value' => $entity->ignore_joiners,
    ];
    $form['configuration']['ignore_punctuation'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ignore Punctuation'),
      '#description' => $this->t('A comma delimited list of punctuation to ignore.'),
      '#default_value' => $entity->ignore_punctuation,
    ];
    $form['configuration']['callbacks'] = [
      '#type' => 'details',
      '#title' => $this->t('Callbacks'),
      '#description' => $this->t("Allow third-party functions to react on MarkJS 
        callbacks. You'll need to define the function using the same name 
        defined below on the window object."),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $form['configuration']['callbacks']['each'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Each'),
      '#description' => $this->t('This callback is called for each marked 
        element. <br/> <strong>Argument:</strong> node.'),
      '#default_value' => $entity->getCallback('each')
    ];
    $form['configuration']['callbacks']['filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter'),
      '#description' => $this->t('This callback is called to filter or limit 
        matches. <br/> <strong>Arguments:</strong> textNode, foundTerm, 
        totalCounter, counter.'),
      '#default_value' => $entity->getCallback('filter')
    ];
    $form['configuration']['callbacks']['done'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Done'),
      '#description' => $this->t('This callback is called after all marks are 
        done. <br/> <strong>Argument:</strong> counter.'),
      '#default_value' => $entity->getCallback('done')
    ];
    $form['configuration']['callbacks']['no_match'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No Match'),
      '#description' => $this->t('This callback is called when there are no 
        matches. <br/> <strong>Argument:</strong> term.'),
      '#default_value' => $entity->getCallback('no_match')
    ];
    $form['configuration']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('Check if you want to log messages.'),
      '#default_value' => $entity->debug,
    ];
    $form['configuration']['log'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Log'),
      '#description' => $this->t('Log messages to a specific object.'),
      '#states' => [
        'visible' => [
          ':input[name="debug"]' => ['checked' => TRUE]
        ]
      ],
      '#default_value' => $entity->log,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate the MarkJS synonyms option value.
    if ($form_state->hasValue(['synonyms'])) {
      $synonyms = $form_state->getValue('synonyms');

      if (!empty($synonyms) && json_decode($synonyms) === NULL) {
        $element = $form['configuration']['synonyms'];
        $form_state->setError(
          $element,
          $this->t('@title is incorrectly formatted.', ['@title' => $element['#title']])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirectUrl(
      $this->entity->toUrl('collection')
    );
  }
}
