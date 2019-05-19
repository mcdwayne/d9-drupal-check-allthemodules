<?php

namespace Drupal\viewport\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class to configure viewport settings for this site.
 */
class ViewportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'viewport_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['viewport.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $viewportSettings = $this->configFactory()->get('viewport.settings');

    $form['viewport'] = array(
      '#type' => 'fieldset',
    );

    $form['viewport']['width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $viewportSettings->get('width'),
      '#description' => $this->t('You probably want this to be %device-width, but a fixed number of pixels (only the number) is accepted too.',
        array('%device-width' => 'device-width')),
    );
    $form['viewport']['height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $viewportSettings->get('height'),
      '#description' => $this->t('%device-height, or a fixed number of pixels (only the number).',
        array('%device-height' => 'device-height')),
    );
    $form['viewport']['initial_scale'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Initial Scale'),
      '#default_value' => $viewportSettings->get('initial_scale'),
      '#description' => $this->t('Any value in the range (0, 10.0]. Usually this is set to 1.0'),
    );
    $form['viewport']['minimum_scale'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Scale'),
      '#default_value' => $viewportSettings->get('minimum_scale'),
      '#description' => $this->t('Any value in the range (0, 10.0]. Usually this is set to the same value as the %initial-scale property',
        array('%initial-scale' => 'initial-scale')),
    );
    $form['viewport']['maximum_scale'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Scale'),
      '#default_value' => $viewportSettings->get('maximum_scale'),
      '#description' => $this->t('Any value in the range (0, 10.0]. Usually this is set to 1.0'),
    );
    $form['viewport']['user_scalable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('User Scalable'),
      '#default_value' => $viewportSettings->get('user_scalable'),
    );
    $form['viewport']['selected_pages'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Selected pages'),
      '#default_value' => $viewportSettings->get('selected_pages'),
      '#description' => $this->t("The viewport settings will be applied to the following paths. <br/>
        Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard.
        Example paths are %node for the first node of the site and %node-wildcard for every node.
        %front is the front page.<br>
        Note in Drupal 8 paths are preceded by a forward slash %slash.", array(
          '%node' => '/node',
          '%node-wildcard' => '/node*',
          '%front' => '<front>',
          '%slash' => '"/"',
        )),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Basic validation on viewport values to assure they're entered properly.
    foreach ($form_state->getValues() as $key => $value) {
      // User_scalable is a checkbox, no need to check for commas.
      if (in_array($key, Element::children($form['viewport'])) && $key != 'user_scalable') {
        if (strstr($value, ',')) {
          $form_state->setErrorByName($key, $this->t('Commas are not allowed for the %field_name field.
            Please, ensure you are using dots (".") when entering decimal values,
            and avoid any commas after the values', array(
              '%field_name' => $form['viewport'][$key]['#title'],
            )));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $viewportSettings = $this->configFactory()->getEditable('viewport.settings');

    // Store submitted values.
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, Element::children($form['viewport']))) {
        // Make sure user_scalable is treated as a boolean.
        $value = ($key == 'user_scalable') ? (bool) $value : $value;
        $viewportSettings->set($key, $value);
      }
    }
    $viewportSettings->save();
  }

}
