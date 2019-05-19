<?php

namespace Drupal\translation_extractor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Settings.
 *
 * The module's settings form.
 *
 * @package Drupal\translation_extractor\Form
 */
class Settings extends ConfigFormBase {

  use StringTranslationTrait;
  use MultivalueRowTrait;

  /**
   * The name of the settings file.
   *
   * @var string
   */
  const SETTINGSFILE = 'translation_extractor.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::SETTINGSFILE];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return self::SETTINGSFILE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the actual configuration object.
    $config = $this->configFactory->get(self::SETTINGSFILE);

    $form['#tree'] = TRUE;

    // Create the container.
    $form['settings'] = [
      '#type' => 'fieldgroup',
      '#title' => $this->t('Search patterns'),

      'fileExtensions' => [
        '#type' => 'textarea',
        '#title' => $this->t('File extensions'),
        '#description' => $this->t('The file extensions to include in the scan. One per line including the leading dot.'),
        '#default_value' => implode(PHP_EOL, $config->get('fileExtensions')),
        '#element_validate' => [[$this, 'validateFileExtensions']],
        '#required' => TRUE,
      ],

      'searchPatterns' => [
        '#type' => 'container',
      ],
    ];

    // Add the form rows.
    $this->createMultivalueFormPortion(
      $form['settings']['searchPatterns'],
      'searchPatterns',
      $form_state,
      $config->get('searchPatterns'),
      'No patterns defined. Use the "Add pattern" to define new search patterns.'
    );

    // Return the complete form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate the file extensions entered.
   *
   * @param array $element
   *   The form element to validate.
   * @param FormStateInterface $form_state
   *   The FormStateInterface object.
   */
  public function validateFileExtensions(array $element, FormStateInterface $form_state) {
    $fileExtensions = $this->preprocessFileExtensions($element["#value"]);
    $faultyExtensionFound = FALSE;
    array_walk($fileExtensions, function ($extension) use (&$faultyExtensionFound) {
      if (!preg_match('~^\..+$~i', $extension)) {
        $faultyExtensionFound = TRUE;
      }
    });
    if ($faultyExtensionFound) {
      $form_state->setError(
        $element,
        sprintf('Please check the file extensions entered.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getInputRow($delta, array $row_defaults, FormStateInterface $form_state) {
    return [
      'pattern' => [
        '#type' => 'textfield',
        '#title' => $this->t('PCRE pattern'),
        '#default_value' => $row_defaults['pattern'],
        '#size' => 100,
        '#required' => TRUE,
        '#element_validate' => [[$this, 'validateRegularExpression']],
        '#inline' => TRUE,
      ],
      'match' => [
        '#type' => 'number',
        '#title' => $this->t('Match number'),
        '#default_value' => $row_defaults['match'],
        '#min' => 1,
        '#max' => 10,
        '#step' => 1,
        '#required' => TRUE,
        '#inline' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDataToAdd($property, array $current_state, array $user_input, $addSelectorValue, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function allowMultipleEmptyAdds($property) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getRowType($property) {
    return 'fieldgroup';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRowTitle($property) {
    return 'Pattern';
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddRowButtonTitle($property) {
    return 'Add pattern';
  }

  /**
   * Element validator function to make sure the input contains valid pattern.
   *
   * @param array $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateRegularExpression(array $element, FormStateInterface $form_state) {
    try {
      if (@preg_match($element["#value"], 'This is a string just for testing purporses.') === FALSE) {
        throw new \Exception();
      }
    }
    catch (\Exception $e) {
      $form_state->setError(
        $element,
        sprintf('The "%s" element does not contain valid PCRE pattern.', $element['#title'])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get an editable instance of the settings.
    $config = $this->configFactory->getEditable(static::SETTINGSFILE);

    // Extract the setting values from the form state.
    $formValues = $form_state->getValue('settings');

    // Prepare the configured file extensions.
    $fileExtensions = $this->preprocessFileExtensions($formValues['fileExtensions']);
    $config->set('fileExtensions', $fileExtensions);

    $searchPatterns = $this->getData('searchPatterns', $form_state->getUserInput());
    $config->set('searchPatterns', $searchPatterns);

    // Save the Configuration.
    $config->save();

    // Delegate to parent.
    parent::submitForm($form, $form_state);
  }

  /**
   * Transforms the value of the file extension fields into an array.
   *
   * @param string $fileExtensions
   *   The extensions as a string.
   *
   * @return array
   *   The extensions as an array.
   */
  private function preprocessFileExtensions($fileExtensions) {
    $fileExtensions = explode(PHP_EOL, $fileExtensions);
    array_walk($fileExtensions, function (&$item) {
      $item = trim($item, "\r\r\t");
    });
    array_filter($fileExtensions);
    return $fileExtensions;
  }

}
