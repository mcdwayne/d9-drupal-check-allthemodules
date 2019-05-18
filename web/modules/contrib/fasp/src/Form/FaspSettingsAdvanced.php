<?php

namespace Drupal\fasp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class FaspSettingsAdvanced extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fasp_settings_advanced';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'fasp.settings.advanced',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fasp.settings.advanced');

    $form['input_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Input names'),
      '#description' => $this->t('Specify which input names can be used for fake hidden inputs. They will be randomly selected during form generation.'),
      '#default_value' => $config->get('input_names'),
      '#required' => TRUE,
    ];

    $form['input_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Input classes'),
      '#description' => $this->t('Specify which classes can be used for hidden inputs. They will be randomly selected during form generation.'),
      '#default_value' => $config->get('input_classes'),
      '#required' => TRUE,
    ];

    $form['classes_style'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Classes style'),
      '#description' => $this->t('This style will be applied for entered classes.'),
      '#default_value' => $config->get('classes_style'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Generate stylesheet for module.
    $style_generator = \Drupal::service('fasp.style_generator');
    $style_fid = NULL;
    if ($stylesheet = $style_generator->generate()) {
      $style_fid = $stylesheet->fid->value;
    }

    // Clean up empty rows.
    $input_names = $form_state->getValue('input_names');
    $input_names_array = explode(PHP_EOL, $input_names);
    foreach ($input_names_array as $index => $item) {
      if (strlen($item) == 0) {
        unset($input_names_array[$index]);
      }
    }
    $input_names = implode(PHP_EOL, $input_names_array);
    // The same with classes.
    $input_classes = $form_state->getValue('input_classes');
    $input_classes_array = explode(PHP_EOL, $input_classes);
    foreach ($input_classes_array as $index => $item) {
      if (strlen($item) == 0) {
        unset($input_classes_array[$index]);
      }
    }
    $input_classes = implode(PHP_EOL, $input_classes_array);

    // Retrieve the configuration and set new values.
    \Drupal::configFactory()->getEditable('fasp.settings.advanced')
      ->set('enable_debugging', $form_state->getValue('enable_debugging'))
      ->set('input_names', $input_names)
      ->set('input_classes', $input_classes)
      ->set('classes_style', $form_state->getValue('classes_style'))
      ->set('hidden_fasp_field', $form_state->getValue('hidden_fasp_field'))
      ->save();

    \Drupal::state()->set('fasp_styles_fid', $style_fid);

    parent::submitForm($form, $form_state);
  }

}
