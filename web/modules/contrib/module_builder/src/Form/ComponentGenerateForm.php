<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\module_builder\ExceptionHandler;
use DrupalCodeBuilder\Exception\SanityException;
use DrupalCodeBuilder\Task\Generate;
use DrupalCodeBuilder\Exception\InvalidInputException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form showing generated component code.
 */
class ComponentGenerateForm extends EntityForm {

  /**
   * The DCB Generate Task handler.
   */
  protected $codeBuilderTaskHandlerGenerate;

  /**
   * Construct a new form object
   *
   * @param \DrupalCodeBuilder\Task\Generate $generate_task
   *   The Drupal Code Builder generate Task object.
   *   This needs to be injected so that submissions after an AJAX operation
   *   work (plus it's good for testing too).
   */
  function __construct(Generate $generate_task) {
    $this->codeBuilderTaskHandlerGenerate = $generate_task;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Get the component data info.
    try {
      $generate_task = $container->get('module_builder.drupal_code_builder')->getTask('Generate', 'module');
    }
    catch (SanityException $e) {
      // Switch the form class so we don't try to build the form without DCB
      // in working order. The ComponentBrokenForm form class handles the
      // exception to show a message to the user.
      return new ComponentBrokenForm($e);
    }

    return new static($generate_task);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $module = $this->entity;
    $component_data = $module->get('data');
    //dsm($component_data);

    // Add in the component root name and readable name.
    $component_data['root_name'] = $module->id;
    $component_data['readable_name'] = $module->label;

    // Build list.
    // The UI always gets the full code.
    $component_data['requested_build'] = array('all' => TRUE);

    // Get the files.
    try {
      $files = $this->codeBuilderTaskHandlerGenerate->generateComponent($component_data);
    }
    catch (InvalidInputException $e) {
      drupal_set_message(t("Invalid input for code generator: @message", [
        '@message' => $e->getMessage(),
      ]), 'error');

      return $form;
    }

    // Get the path to the module if it's previously been written.
    $existing_module_path = $this->getExistingModule();

    $module_name = $this->entity->id();
    if (\Drupal::moduleHandler()->moduleExists($module_name)) {
      drupal_set_message(t("This module is currently ENABLED on this site. Writing files MAY CAUSE YOUR SITE TO CRASH."), 'warning');
    }

    $form['code'] = array(
      '#type' => 'vertical_tabs',
    );

    // Why can't these be nested under the vertical_tabs element?
    $form['files']['#tree'] = TRUE;

    ksort($files);
    foreach ($files as $filename => $code) {
      $form['files'][$filename] = array(
        '#type' => 'details',
        '#title' => $filename,
        '#group' => 'code',
      );

      $title = t('@filename code', [
        '@filename' => $filename,
      ]);
      if (file_exists($existing_module_path . '/' . $filename)) {
        $title .= ' ' . t("(File already exists)");
      }

      $form['files'][$filename]['module_code'] = array(
        '#type' => 'textarea',
        '#title' => $title,
        '#rows' => count(explode("\n", $code)),
        '#default_value' => $code,
        // This creates an item in form values that just contains the code, so
        // we don't need to filter out the values from button labels when
        // writing code.
        '#parents' => ['file_code', $filename],
        '#prefix' => '<div class="module-code">',
        '#suffix' => '</div>',
      );

      // We don't actually use the value from the form, as the POST process
      // seems to be turning unix line endings into Windows line endings! Store
      // it in the form state instead.
      $form_state->set(['files', $filename], $code);

      $form['files'][$filename]['write'] = array(
        '#type' => 'submit',
        // WTF: FormBuilder::buttonWasClicked() won't work with dots in the
        // button's #name!
        '#name' => 'write_single_' . str_replace('.', '_', $filename),
        '#value' => $this->t("Write file $filename"),
        '#submit' => array('::submitWriteSingle'),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = [];

    if (isset($form['files']) && count(Element::children($form['files']))) {
      $actions['write'] = array(
        '#type' => 'submit',
        '#name' => 'write_all',
        '#value' => $this->t('Write all files'),
        '#submit' => array('::write'),
      );
    }

    return $actions;
  }

  /**
   * Returns the path to the module if it has previously been written.
   *
   * @return
   *  A Drupal-relative path to the module folder, or NULL if the module
   *  does not already exist.
   */
  protected function getExistingModule() {
    $module_name = $this->entity->id();

    if (\Drupal::moduleHandler()->moduleExists($module_name)) {
      return drupal_get_path('module', $module_name);
    }

    // Account for a module that may have been written, but not yet enabled.
    if (file_exists('modules/custom/' . $module_name)) {
      return 'modules/custom/' . $module_name;
    }

    if (file_exists('modules/' . $module_name)) {
      return 'modules/' . $module_name;
    }
  }

  /**
   * Submit callback to write the module files.
   */
  public function write(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $drupal_relative_module_dir = $this->getRelativeModuleFolder();

    file_prepare_directory($path, FILE_CREATE_DIRECTORY);

    $count_written = 0;
    foreach (array_keys($values['file_code']) as $module_relative_filepath) {
      $file_contents = $form_state->get(['files', $module_relative_filepath]);

      $result = $this->writeSingleFile($drupal_relative_module_dir, $module_relative_filepath, $file_contents);

      if ($result) {
        $count_written++;
      }
      else {
        drupal_set_message(t("Problem writing file @file", [
          '@file' => $module_relative_filepath
        ]), 'error');
      }
    }

    if ($count_written) {
      drupal_set_message(t("Written @count files to folder @folder.", [
        '@count'  => $count_written,
        '@folder' => $drupal_relative_module_dir,
      ]));
    }
  }

  /**
   * Submit handler to write a single file.
   */
  public function submitWriteSingle(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $button_array_parents = $button['#array_parents'];

    $file_key = $button_array_parents[1];

    $file_contents = $form_state->get(['files', $file_key]);

    $drupal_relative_module_dir = $this->getRelativeModuleFolder();

    $result = $this->writeSingleFile($drupal_relative_module_dir, $file_key, $file_contents);

    if ($result) {
      drupal_set_message(t("Written file @file to folder @folder.", [
        '@file'  => $file_key,
        '@folder' => $drupal_relative_module_dir,
      ]));
    }
  }

  /**
   * Get the Drupal-relative path of the module folder to write to.
   *
   * @return string
   *   The Drupal-relative path.
   */
  protected function getRelativeModuleFolder() {
    // If the module folder already exists, write there.
    $existing_module_folder = $this->getExistingModule();
    if ($existing_module_folder) {
      return $existing_module_folder;
    }

    $module_name = $this->entity->id();

    if (file_exists('modules/custom')) {
      $modules_dir = 'modules/custom';
    }
    else {
      $modules_dir = 'modules';
    }

    $drupal_relative_module_dir = $modules_dir . '/' . $module_name;

    return $drupal_relative_module_dir;
  }

  /**
   * Writes a single file.
   *
   * @param string $drupal_relative_module_dir
   *   The module folder to write to, as a path relative to Drupal root.
   * @param string $module_relative_filepath
   *   The name of the file to write, as a path relative to the module folder,
   *   e.g. src/Plugins/Block/Foo.php.
   * @param string $file_contents
   *   The file contents to write.
   *
   * @return bool
   *   TRUE if writing succeeded, FALSE if it failed.
   */
  protected function writeSingleFile($drupal_relative_module_dir, $module_relative_filepath, $file_contents) {
    // The files are keyed by a filepath relative to the future module folder,
    // e.g. src/Plugins/Block/Foo.php.
    // Extract the directory.
    $module_relative_dir = dirname($module_relative_filepath);
    $filename = basename($module_relative_filepath);

    $drupal_relative_dir      = $drupal_relative_module_dir . '/' . $module_relative_dir;
    $drupal_relative_filepath = $drupal_relative_module_dir . '/' . $module_relative_filepath;
    file_prepare_directory($drupal_relative_dir, FILE_CREATE_DIRECTORY);

    $result = file_put_contents($drupal_relative_filepath, $file_contents);

    return (bool) $result;
  }

}
