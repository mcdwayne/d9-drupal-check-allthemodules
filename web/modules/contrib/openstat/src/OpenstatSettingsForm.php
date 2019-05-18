<?php
/**
 * @file
 * Contains \Drupal\openstat\OpenstatSettingsForm.
 */

namespace Drupal\openstat;

use Drupal\Core\Form\ConfigFormBase;
use \Drupal\Component\Utility\String;
use \Drupal\Component\Utility\Unicode;

class OpenstatSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'openstat_admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('openstat.settings');

    $user = \Drupal::currentUser();
    $email = $user->getEmail();

    $arg = array(
      '!Openstat' => l(t('Openstat'), 'https://www.openstat.ru'),
      '%email' => $email,
    );

    $form['openstat_general'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
      '#description' => t('The ID is unique to each site. If you do not have account or have not registered this site on !Openstat press the button below. It will register the site for %email email.', $arg),
      '#open' => TRUE,
    );

    $form['openstat_general']['openstat_button'] = array(
      '#type' => 'button',
      '#value' => t('Get Openstat counter ID'),
      '#submit' => array(array($this, 'getCounterIdSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'getCounterIdAjaxCallback'),
        'wrapper' => 'openstat-wrapper',
      ),
    );

    // If Ajax is done.
    if (isset($form_state['values']['openstat_id'])) {
      // Overwrite Openstat ID with new one.
      $form_state['input']['openstat_id'] = String::checkPlain(openstat_get_countet_id());
    }

    $form['openstat_general']['openstat_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Counter ID'),
      '#size' => 7,
      '#required' => TRUE,
      '#description' => t('If you already have registered your site you can put the ID here.'),
      '#default_value' => $config->get('id'),
      '#prefix' => '<div id="openstat-wrapper">',
      '#suffix' => '</div>',
    );

    $form['openstat_addition'] = array(
      '#type' => 'details',
      '#title' => t('Counter settings'),
      '#open' => TRUE,
    );

    $form['openstat_addition']['openstat_type'] = array(
      '#type' => 'radios',
      '#title' => t('Counter type'),
      '#options' => array(
        t('Invisible counter'),
        t('Counter with image'),
      ),
      '#description' => t('Select the counter type. You can control the visibility of counter through Openstat block configuration.'),
      '#default_value' => $config->get('type'),
    );

    $form['openstat_addition']['openstat_image_type'] = array(
      '#type' => 'radios',
      '#title' => t('Image type'),
      '#options' => array(
        87 => t('Big image with Openstat logo'),
        5081 => t('Big image with tracking data'),
        5085 => t('Small image with Openstat logo and tracking data'),
        91 => t('Small image with people logo and with border'),
        93 => t('Small image with people logo and without border'),
      ),
      '#description' => t('Select the counter image type.'),
      '#default_value' => $config->get('image_type'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_type"]' => array(
            'value' => 1,
          ),
        ),
      ),
    );

    $form['openstat_addition']['openstat_color'] = array(
      '#type' => 'select',
      '#title' => t('Counter color'),
      '#options' => array(
        'c3c3c3' => t('Silver'),
        '828282' => t('Gray'),
        '000000' => t('Black'),
        '3400cd' => t('Navy'),
        '458efc' => t('Blu'),
        '258559' => t('Green'),
        '00d43c' => t('Lime'),
        'c0f890' => t('Chartreuse'),
        'fdd127' => t('Gold'),
        'ff9822' => t('Orange'),
        'ff5f1e' => t('Orange-red'),
        'ff001c' => t('Red'),
        '9c0037' => t('Maroon'),
        '8f46b9' => t('Blue violet'),
        'c044b6' => t('Purple'),
        'ff86fb' => t('Magenta'),
        'custom' => t('Custom'),
      ),
      '#description' => t('Select the main counter color.'),
      '#default_value' => $config->get('color'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_type"]' => array(
            'value' => 1,
          ),
        ),
      ),
    );

    $form['openstat_addition']['openstat_custom_color'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom color'),
      '#size' => 6,
      '#description' => t('Enter color in hex format.'),
      '#field_prefix' => '#',
      '#default_value' => $config->get('custom_color'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_color"]' => array(
            'value' => 'custom',
          ),
        ),
      ),
    );

    $form['openstat_addition']['openstat_gradient'] = array(
      '#type' => 'checkbox',
      '#title' => t('Gradient'),
      '#description' => t('Select the gradient for counter image.'),
      '#default_value' => $config->get('gradient'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_type"]' => array(
            'value' => 1,
          ),
        ),
      ),
    );

    $form['openstat_addition']['openstat_font_color'] = array(
      '#type' => 'radios',
      '#title' => t('Font color'),
      '#options' => array(
        t('Dark'),
        t('Light'),
      ),
      '#description' => t('Select font color.'),
      '#default_value' => $config->get('font_color'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_type"]' => array(
            'value' => 1,
          ),
        ),
      ),
    );

    $form['openstat_addition']['openstat_track_links'] = array(
      '#type' => 'radios',
      '#title' => t('Track click on links'),
      '#options' => array(
        '0' => t('Not'),
        'ext' => t('Only on external links'),
        'all' => t('On external and internal link'),
      ),
      '#description' => t('Select type of tracking clicks on links.'),
      '#default_value' => $config->get('track_links'),
      '#states' => array(
        'visible' => array(
          ':input[name="openstat_type"]' => array(
            'value' => 1,
          ),
        ),
      ),
    );

    $form['openstat_page_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Tracking specific pages'),
    );

    $form['openstat_page_vis_settings']['openstat_visibility_pages'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking to specific pages'),
      '#options' => array(
        t('Every page except the listed pages'),
        t('The listed pages only'),
      ),
      '#default_value' => $config->get('visibility_pages'),
    );

    $form['openstat_page_vis_settings']['openstat_pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $config->get('pages'),
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        array(
          '%blog' => 'blog',
          '%blog-wildcard' => 'blog/*',
          '%front' => '<front>',
        )
      ),
      '#rows' => 10,
    );

    $form['openstat_role_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Tracking specific roles'),
    );

    $form['openstat_role_vis_settings']['openstat_visibility_roles'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking for specific roles'),
      '#options' => array(
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ),
      '#default_value' => $config->get('visibility_roles'),
    );

    $visibility_roles = $config->get('roles');
    $role_options = array_map('\Drupal\Component\Utility\String::checkPlain', user_role_names());
    $form['openstat_role_vis_settings']['openstat_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Roles'),
      '#default_value' => !empty($visibility_roles) ? $visibility_roles : array(),
      '#options' => $role_options,
      '#description' => t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    // Openstat ID validation.
    if (isset($form_state['values']['openstat_id'])) {
      if (!preg_match('/^\d{7}$/', $form_state['values']['openstat_id'])) {
        \Drupal::formBuilder()->setErrorByName('openstat_id', $form_state, t('The ID must contain 7 numbers.'));
      }
    }

    // Openstat custom color validation.
    if (($form_state['values']['openstat_color'] == 'custom') && (isset($form_state['values']['openstat_custom_color']))) {
      $subject = Unicode::strtolower($form_state['values']['openstat_custom_color']);
      if (!preg_match('/^[a-f0-9]{6}$/i', $subject)) {
        \Drupal::formBuilder()->setErrorByName('openstat_custom_color', $form_state, t('Custom color value must be in hex format.'));
      }
    }

  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('openstat.settings');
    $config
      ->set('id', $form_state['values']['openstat_id'])
      ->set('type', $form_state['values']['openstat_type'])
      ->set('image_type', $form_state['values']['openstat_image_type'])
      ->set('color', $form_state['values']['openstat_color'])
      ->set('custom_color', $form_state['values']['openstat_custom_color'])
      ->set('gradient', $form_state['values']['openstat_gradient'])
      ->set('font_color', $form_state['values']['openstat_font_color'])
      ->set('track_links', $form_state['values']['openstat_track_links'])
      ->set('visibility_pages', $form_state['values']['openstat_visibility_pages'])
      ->set('pages', $form_state['values']['openstat_pages'])
      ->set('visibility_roles', $form_state['values']['openstat_visibility_roles'])
      ->set('roles', $form_state['values']['openstat_roles'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get Openstat ID and rebuild form.
   */
  public function getCounterIdSubmit($form, &$form_state) {
    $form_state['input']['openstat_id'] = String::checkPlain(openstat_get_countet_id());
    $form_state['rebuild'] = TRUE;
  }

  /**
   * Return Openstat ID form element.
   *
   * @return array
   *   Renderable array (the textfields element)
   */
  public function getCounterIdAjaxCallback($form, $form_state) {
    return $form['openstat_general']['openstat_id'];
  }

}

