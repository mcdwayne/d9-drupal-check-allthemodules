<?php
/**
 * @file
 * Contains \Drupal\tynt\Form\TyntSettingsForm.
 */

namespace Drupal\tynt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form to configure module settings.
 */
class TyntSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'tynt_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tynt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all settings
    $config = $this->config('tynt.settings');
    $settings = $config->get();

    $form['settings'] = array(
      '#tree' => TRUE,
    );

    $form['settings']['account'] = array(
      '#type'        => 'fieldset',
      '#title'       => $this->t('General Settings'),
      '#collapsible' => FALSE,
    );

    $form['settings']['account']['tynt_site_guid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site GUID'),
      '#description' => $this->t('Enter your site guid found on !link under \'Your API Key\'.', array(
        '!link' => $this->l($this->t('Tynt'), Url::fromUri('http://www.tynt.com/api')),
      )),
      '#default_value' => $settings['tynt_site_guid'],
    );

    $form['settings']['role_vis_settings'] = array(
      '#type'        => 'fieldset',
      '#title'       => $this->t('Role Specific Tracking Settings'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    );

    $form['settings']['role_vis_settings']['tynt_roles'] = array(
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Add tracking for specific roles'),
      '#default_value' => $settings['tynt_roles'],
      '#options'       => user_role_names(),
      '#description'   => $this->t('Add tracking only for the selected role(s). If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked.'),
    );

    $form['settings']['page_vis_settings'] = array(
      '#type'        => 'fieldset',
      '#title'       => $this->t('Page Specific Tracking Settings'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    );

    \Drupal::currentUser()->hasPermission('use PHP for tracking visibility');
    $account    = \Drupal::currentUser();
    $access     = $account->hasPermission('use PHP for tracking visibility');
    $visibility = $settings['tynt_visibility'];
    $pages      = $settings['tynt_pages'];

    if( $visibility == 2 && !$access ) {
      $form['settings']['page_vis_settings'] = array();

      $form['settings']['page_vis_settings']['tynt_visibility'] = array('#type' => 'value', '#value' => 2);
      $form['settings']['page_vis_settings']['tynt_pages']      = array('#type' => 'value', '#value' => $pages);
    } else {
      $options = array($this->t('Add to every page except the listed pages.'),$this->t('Add to the listed pages only.'));
      $description = $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
        '%blog'          => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front'         => '<front>',
      ));

      if ($access) {
        $options[] = $this->t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
        $description .= ' ' . $this->t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
      }

      $form['settings']['page_vis_settings']['tynt_visibility'] = array(
        '#type'          => 'radios',
        '#title'         => $this->t('Add tracking to specific pages'),
        '#options'       => $options,
        '#default_value' => $visibility,
      );

      $form['settings']['page_vis_settings']['tynt_pages'] = array(
        '#type'          => 'textarea',
        '#title'         => $this->t('Pages'),
        '#default_value' => $pages,
        '#description'   => $description,
        '#wysiwyg'       => FALSE,
      );
    }

    $form['buttons']['reset'] = array(
      '#type' => 'submit',
      '#submit' => array('::submitResetDefaults'),
      '#value' => t('Reset to defaults'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Compares the submitted settings to the defaults and unsets any that are equal. This was we only store overrides.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get config factory
    $config = $this->config('tynt.settings');
    $form_values = $form_state->getValue(['settings']);

    $config
      ->set('tynt_site_guid', $form_values['account']['tynt_site_guid'])
      ->set('tynt_roles', $form_values['role_vis_settings']['tynt_roles'])
      ->set('tynt_visibility', $form_values['page_vis_settings']['tynt_visibility'])
      ->set('tynt_pages', $form_values['page_vis_settings']['tynt_pages'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitResetDefaults(array &$form, FormStateInterface $form_state) {
    $config = $this->config('tynt.settings');

    // Get config factory
    $settingsDefault = $this->getDefaultSettings();

    $config
      ->set('tynt_site_guid', $settingsDefault['tynt_site_guid'])
      ->set('tynt_roles', $settingsDefault['tynt_roles'])
      ->set('tynt_visibility', $settingsDefault['tynt_visibility'])
      ->set('tynt_pages', $settingsDefault['tynt_pages'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns an associative array of default settings
   * @return array
   */
  public function getDefaultSettings() {

    $defaults = array(
      'tynt_site_guid' => '',
      'tynt_roles' => '',
      'tynt_visibility' => '',
      'tynt_pages' => '',
    );

    return $defaults;
  }

}
