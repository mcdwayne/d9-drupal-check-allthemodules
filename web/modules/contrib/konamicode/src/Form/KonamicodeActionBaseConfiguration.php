<?php

namespace Drupal\konamicode\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KonamicodeActionBaseConfiguration.
 */
class KonamicodeActionBaseConfiguration extends KonamicodeMainConfiguration {

  /**
   * The action name, used in the tab title and text references.
   *
   * Example: Redirect.
   *
   * @var string
   */
  private $name;

  /**
   * The machine name of the action, concatenated by underscores.
   *
   * Example: image_spam.
   *
   * @var string
   */
  private $machineName;

  /**
   * Generated field group name to group fields.
   *
   * Example: konamicode_action_MACHINE_NAME.
   *
   * @var string
   */
  protected $fieldGroupName;

  /**
   * An array of dependencies that the action might have.
   *
   * Can be empty in case there are no dependencies on libraries.
   *
   * @var array
   */
  private $dependencies;

  /**
   * {@inheritdoc}
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The ConfigFactoryInterface.
   * @param string $name
   *   Action name.
   * @param string $machine_name
   *   Action machine_name.
   * @param array $dependencies
   *   Optional: An array of dependencies. The dependency name must match the
   *   name set in the konamicode.libraries.yml file.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $name, $machine_name, array $dependencies = []) {
    $this->setName($name);
    $this->setMachineName($machine_name);
    $this->setDependencies($dependencies);
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('konamicode.configuration');

    // Create a details element that will be further completed.
    $form[$this->getFieldGroupName()] = [
      '#type' => 'details',
      '#title' => $this->getName(),
      '#collapsible' => TRUE,
      '#group' => 'konamicode_action_settings',
    ];

    $unmet_dependencies = $this->validateDependencies();
    if (!empty($unmet_dependencies)) {
      $this->messenger()
        ->addError($this->t('There are unmet dependencies for the action %action. Please make sure all dependencies are installed by composer. Dependencies: %dependencies', [
          '%action' => $this->getName(),
          '%dependencies' => implode(', ', $unmet_dependencies),
        ]));
    }

    $action_enabled = $this->getUniqueFieldName('enabled');
    $form[$this->getFieldGroupName()][$action_enabled] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => empty($unmet_dependencies) ? $config->get($action_enabled) : FALSE,
      '#disabled' => empty($unmet_dependencies) ? FALSE : TRUE,
    ];

    $action_keycode_sequence = $this->getUniqueFieldName('keycode_sequence');
    $form[$this->getFieldGroupName()][$action_keycode_sequence] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key code sequence'),
      '#description' => $this->t('The <a href="@keycodes">key code sequence</a> used to activate the %name action, separated by commas. Provide the key code integer of the key. Defaults to the <a href="@konamicode">Konami Code</a>. Default: <em>38,38,40,40,37,39,37,39,66,65</em>', [
        '%name' => $this->getName(),
        '@konamicode' => 'https://www.drupal.org/docs/8/modules/konami-code/general-information-and-history',
        '@keycodes' => 'https://www.drupal.org/docs/8/modules/konami-code/configuring-key-code-sequence',
      ]),
      '#default_value' => empty($config->get($action_keycode_sequence)) ? '38,38,40,40,37,39,37,39,66,65' : $config->get($action_keycode_sequence),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action_keycode_sequence = $this->getUniqueFieldName('keycode_sequence');
    // Validate the Key Code Sequence.
    if (!$this->validateKeyCodeSequence($form_state->getValue($action_keycode_sequence))) {
      $form_state->setErrorByName($action_keycode_sequence, $this->t('There seems to be an error with your Key Code Sequence field for the action: %action', ['%action' => $this->getName()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Fetch the unique field names.
    $action_enabled = $this->getUniqueFieldName('enabled');
    $action_keycode_sequence = $this->getUniqueFieldName('keycode_sequence');
    // Save the values.
    $this->configFactory->getEditable('konamicode.configuration')
      ->set($action_enabled, $form_state->getValue($action_enabled))
      ->set($action_keycode_sequence, $form_state->getValue($action_keycode_sequence))
      ->save();
  }

  /**
   * Function that will validate the Key Code Sequence.
   *
   * @param string $sequence
   *   The sequence entered in the form.
   *
   * @return bool
   *   Returns the result of the validation.
   */
  public function validateKeyCodeSequence($sequence) {
    return (bool) preg_match('/^[0-9]{1,3}+(,[0-9]{1,3})*$/', $sequence);
  }

  /**
   * Function to validate if all the required dependencies are met.
   *
   * Currently we don't check for a specific version but just make sure that the
   * library is under the correct location.
   *
   * @return array
   *   Returns an array of missing JS files.
   */
  protected function validateDependencies() {
    $unmet_dependencies = [];

    // Loop over all the dependencies.
    foreach ($this->dependencies as $dependency) {
      $library = \Drupal::service('library.discovery')->getLibraryByName('konamicode', $dependency);

      // If we have a library and JS dependencies, we go and verify they are
      // installed at the requested location.
      if (!empty($library) && isset($library['js'])) {
        // It could be that we have more as 1 JS dependency.
        foreach ($library['js'] as $dependency_item) {
          // If the file doesn't exist we have a problem.
          if (!file_exists($dependency_item['data'])) {
            $unmet_dependencies[] = $dependency_item['data'];
          }
        }
      }
      else {
        // We didn't find any information about the required dependency.
        $this->messenger()->addError($this->t('Passed dependency %dependency not found as a library.', ['%dependency' => $dependency]));
      }
    }
    return $unmet_dependencies;
  }

  /**
   * Function to create a unique (read as for each action) field name.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @return string
   *   The generated field name.
   */
  public function getUniqueFieldName($field_name) {
    return 'konamicode_' . $this->getMachineName() . '_' . $field_name;
  }

  /**
   * Function to set the name of the action.
   *
   * @param string $name
   *   The name of the module you want to set.
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * Function to get the name of the action.
   *
   * @return string
   *   The name of the action.
   */
  public function getName() {
    return $this->t('@name', ['@name' => $this->name]);
  }

  /**
   * Function to set the machine name of the action.
   *
   * Should be all lowercase and concatenated with underscores.
   *
   * @param string $machineName
   *   The machine name you want to set.
   */
  public function setMachineName($machineName) {
    $this->machineName = $machineName;
    $this->setFieldGroupName('konamicode_action_' . $machineName);
  }

  /**
   * Function to get the machine name of the action.
   *
   * @return string
   *   The machine name.
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * Function so set the dependencies of the action.
   *
   * @param array $dependencies
   *   An array of dependencies.
   */
  public function setDependencies(array $dependencies) {
    $this->dependencies = $dependencies;
  }

  /**
   * Function to set the name of the fieldgroup.
   *
   * Used by the setMachineName function to set a value like:
   * konamicode_action_redirect.
   *
   * @param string $field_group_name
   *   The fieldgroup name to set.
   */
  private function setFieldGroupName($field_group_name) {
    $this->fieldGroupName = $field_group_name;
  }

  /**
   * Function to get the fieldgroup name.
   *
   * @return string
   *   The name of the fieldgroup.
   */
  public function getFieldGroupName() {
    return $this->fieldGroupName;
  }

}
