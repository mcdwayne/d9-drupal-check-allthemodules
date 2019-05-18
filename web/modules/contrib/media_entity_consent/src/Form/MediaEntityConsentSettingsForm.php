<?php

namespace Drupal\media_entity_consent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity_consent\ConsentHelper;

/**
 * Configure example settings for this site.
 */
class MediaEntityConsentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_entity_consent_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_entity_consent.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('media_entity_consent.settings');
    $roles = ConsentHelper::getUserRoles();
    $media_types = ConsentHelper::getMediaTypes();
    $media_display_modes = ConsentHelper::getDisplayModes();

    $form['roles'] = [
      '#type' => 'details',
      '#title' => $this->t('Consent bypass by role'),
    ];

    $form['roles']['desc'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Users with roles activated here will bypass media entity consent automatically. The media elements will be rendered as if media entity consent is disabled.') . '</p>',
    ];

    foreach ($roles as $role) {
      $form['roles']['role_' . $role->id() . '_bypass'] = [
        '#type' => 'checkbox',
        '#title' => $role->label(),
        '#default_value' => $config->get('access_bypass')[$role->id()],
      ];
    }

    $form['display_modes'] = [
      '#type' => 'details',
      '#title' => $this->t('Consent bypass by display mode'),
    ];

    $form['display_modes']['desc'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Display modes activated here will bypass media entity consent automatically. The media elements will be rendered as if media entity consent is disabled. Useful for media library view modes for example. Because of this, by default it\'s recommended to enable the bypass at least on the "media_library" view mode.') . '</p>',
    ];

    foreach ($media_display_modes as $display_mode) {
      $form['display_modes']['display_' . $display_mode . '_bypass'] = [
        '#type' => 'checkbox',
        '#title' => $display_mode,
        '#default_value' => $config->get('display_bypass')[$display_mode],
      ];
    }

    $form['media_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Media types'),
    ];

    $form['media_types']['desc'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Here you can configure which media types should use media entity consent and which hints the user should get.') . '</p>',
    ];

    foreach ($media_types as $media_type) {
      $form['media_types'][$media_type->id()] = [
        '#type' => 'details',
        '#title' => $media_type->label(),
      ];

      $form['media_types'][$media_type->id()][$media_type->id() . '_enable'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable media entity consent'),
        '#default_value' => $config->get('media_types')[$media_type->id()]['enabled'],
      ];

      $states = [
        'visible' => [
          ':input[name="' . $media_type->id() . '_enable"]' => [
            'checked' => TRUE,
          ],
        ],
      ];

      $form['media_types'][$media_type->id()][$media_type->id() . '_consent_question'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Consent question'),
        '#description' => $this->t('Question besides the checkbox, where the user is asked for consent on loading the media entiy.'),
        '#default_value' => $config->get('media_types')[$media_type->id()]['consent_question'],
        '#states' => $states,
      ];

      $form['media_types'][$media_type->id()][$media_type->id() . '_consent_footer'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Consent footer'),
        '#rows' => 2,
        '#format' => $config->get('media_types')[$media_type->id()]['consent_footer']['format'] ? $config->get('media_types')[$media_type->id()]['consent_footer']['format'] : 'full_html',
        '#description' => $this->t('Explanation to the user when the media entity is loaded. Normally it gives hints, which external service was used and links to the privacy policy page of your website, where the user can revoke his consent.'),
        '#default_value' => $config->get('media_types')[$media_type->id()]['consent_footer']['value'],
        '#states' => $states,
      ];

      $form['media_types'][$media_type->id()][$media_type->id() . '_privacy_policy'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Privacy policy text'),
        '#rows' => 2,
        '#format' => $config->get('media_types')[$media_type->id()]['consent_footer']['format'] ? $config->get('media_types')[$media_type->id()]['consent_footer']['format'] : 'full_html',
        '#description' => $this->t('The description that will be displayed in the privacy policy block / token for that media entity type. There the user can change decisions made according to media entity consent types.'),
        '#default_value' => $config->get('media_types')[$media_type->id()]['privacy_policy']['value'],
        '#states' => $states,
      ];

      $form['media_types'][$media_type->id()][$media_type->id() . '_excluded_files'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Excluded files from external Sources'),
        '#rows' => 3,
        '#description' => $this->t('<b>Since finding out which external JS files a media entity needs to load is tricky because of so many different implementations, you have to specify them by hand. </b><p>The defined files\' loading will be surpressed if the user did not give consent yet and will be injected, when the user gives consent.</p> <p>Please specify one file per line and use the exact same path style that gets rendered. </p><p>Also keep in mind, that it is possible, that a JS file from the media provider or field formatter may inject scripts dynamically. So it is recommended to look into those files to test it well after they were added to the above list.</p><p>F.e. for the module "media_entity_twitter" the excluded files would be "//platform.twitter.com/widgets.js" and "modules/contrib/media_entity_twitter/js/twitter.js". The file from media_entity_twitter is also adding the twitter script dynamically, so to exclude only the script from platform.twitter.com won\'t work.</p>'),
        '#default_value' => $config->get('media_types')[$media_type->id()]['excluded_files'],
        '#states' => $states,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('media_entity_consent.settings');
    $roles = ConsentHelper::getUserRoles();
    $media_types = ConsentHelper::getMediaTypes();
    $display_modes = ConsentHelper::getDisplayModes();

    $role_mapping = [];
    foreach ($roles as $role) {
      $role_mapping[$role->id()] = (boolean) $form_state->getValue('role_' . $role->id() . '_bypass', 0);
    }
    $config->set('access_bypass', $role_mapping);

    $display_mode_mapping = [];
    foreach ($display_modes as $display_mode) {
      $display_mode_mapping[$display_mode] = (boolean) $form_state->getValue('display_' . $display_mode . '_bypass', 0);
    }
    $config->set('display_bypass', $display_mode_mapping);

    $media_mapping = [];
    foreach ($media_types as $media_type) {

      $media_mapping[$media_type->id()] = [
        'enabled' => (boolean) $form_state->getValue($media_type->id() . '_enable', 0),
        'consent_question' => $form_state->getValue($media_type->id() . '_consent_question', ''),
        'consent_footer' => $form_state->getValue($media_type->id() . '_consent_footer', ''),
        'privacy_policy' => $form_state->getValue($media_type->id() . '_privacy_policy', ''),
        'excluded_files' => $form_state->getValue($media_type->id() . '_excluded_files', ''),
      ];
    }

    $config->set('media_types', $media_mapping);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
