<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;

/**
 * This class defines the admin setting form for this module, available
 * at : admin/config/people/anonymous_publishing_cl
 */
class AnonymousPublishingClAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_publishing_cl_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['anonymous_publishing_cl.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('anonymous_publishing_cl.settings');

    // Retrieve an anonymous user: helpfull to control permissions on anons.
    $anonymous_user = new AnonymousUserSession();

    /* -----
     * Pre-requisite checks: will this module control comment too ?
     * Yes: if anons can post comments,
     * No: otherwise.
     */

    // Validate that currently setted content types can also enable comment
    // management.
    $enabled_content_types = $settings->get('allowed_content_types');
    if (!empty($enabled_content_types)) {
      foreach ($enabled_content_types as $enabled_content_type => $activated) {

        // 1. Case where managing comment is enabled but anon user has not the
        //    proper comment permission.
        if ('comment' == $enabled_content_type && $activated) {
          $anon_can_post_comment = $anonymous_user->hasPermission('post comments');
          if ($activated && !$anon_can_post_comment) {
            drupal_set_message(t('The module is set to manage comments, but anonymous users are not allowed to post comments.'), 'warning');
          }
        }
        // 2. Case where managing this content type is enabled but anon user
        //    cannot create such a content type.
        else {
          $anon_can_create_content_of_this_type = $anonymous_user->hasPermission('create ' . $enabled_content_type . ' content');
          if ($activated && !$anon_can_create_content_of_this_type) {
            drupal_set_message(t('The module is set to to manage @ctypes, but anonymous users are not allowed to create @ctypes.', [
              '@ctype' => $enabled_content_type
            ]), 'warning');
          }
        }
      }
    }

    $content_types = node_type_get_names();
    if (\Drupal::moduleHandler()->moduleExists('comment')) {
      $content_types['comment'] = t('Comment');
      $ctext = '(+ Comment)';
    }
    else {
      $ctext = '';
    }

    /* -----
     * Build the settings form.
     */

    $form['allowed_content_types'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#title' => t('Content types @comments where anonymous publishing is managed by this submodule:', array(
        '@comments' => $ctext
      )),
      '#default_value' => $enabled_content_types,
      '#options' => $content_types,
      '#description' => t('Note: You also need to use node permissions to enable anonymous publishing for the anonymous user role if you want this role to be able to create content.'),
    ];

    $anonymous_publishing_cl_options = $settings->get('general_options');
    $anonymous_publishing_cl_options_values = array();
    foreach ($anonymous_publishing_cl_options as $option => $value) {
      $anonymous_publishing_cl_options_values[$option] = $value ? $option : '';
    }
    $form['general_options'] = [
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#title' => t('Options:'),
      '#default_value' => $anonymous_publishing_cl_options_values,
      '#options' => array(
        'sactivate' => t('Allow self-activation.'),
        'sactstick' => t('Make self-activation sticky.'),
        'sactcomm' => t('Skip comment approval (set on @link).', array(
          '@link' => Link::createFromRoute(t('Administration » People » Permissions'), 'user.admin_permissions')->toString(),
        )),
        'modmail' => t('Send e-mail to administrator when anonymous content is created.'),
        'blockip' => t('Use IP-address for blocking.'),
        'aregist' => t('Allow registered e-mails to be used for anonymous posts.'),
      ),
      '#description' => t('Check the options you want to enable.'),
    ];

    $form['general_options']['sactcomm'] = [
      '#disabled' => TRUE,
      '#default_value' => $anonymous_user->hasPermission('skip comment approval'),
    ];

    $form['verification_persistency'] = array(
      '#type' => 'radios',
      '#title' => t('Verification persistency:'),
      '#options' => array(
        'persist' => t('Make verification persistent.'),
        'ip_duration' => t('Verification persists as long as the same IP is used.'),
        'every_post' => t('Require verification for each posting.'),
      ),
      '#description' => t('This determines whether users need to re-verify.'),
      '#default_value' => $settings->get('verification_persistency'),
    );

    // NOTE: Period is set on privacy tab, -1 = Indefinitely.
    $period = $settings->get('retain_period');
    if (-1 == $period) {
      $form['user_alias'] = array(
        '#type' => 'radios',
        '#title' => t('To whom should anonymous postings be attributed:'),
        '#options' => array(
          'anon' => t('Use "@anon" (the default alias for anonymous users).', array(
            '@anon' => \Drupal::config('user.settings')
              ->get('anonymous')
          )),
          'alias' => t('Use an autogenerated persistent alias (format "user<em>N</em>").'),
          'byline' => t('Allow the anonymous publisher to set the byline.'),
        ),
        '#description' => t('This determines what string to use as byline for anonymous posts.'),
        '#default_value' => $settings->get('user_alias'),
      );

      $form['byline_guidance'] = array(
        '#type' => 'textfield',
        '#title' => t('Guidelines for the byline:'),
        '#size' => 60,
        '#maxlength' => Email::EMAIL_MAX_LENGTH,
        '#default_value' => $settings->get('byline_guidance'),
        '#description' => t('If you want to provide guidance users who set their own byline, you can do it here.'),
      );
    }
    else {
      $form['user_alias'] = [
        '#markup' => '<p><b>' . t('To access the settings for the byline, you must set the retention period to "Indefinitely" (on the <em>Privacy</em> tab)') . '</b></p>',
      ];
    }

    $default_mail = $settings->get('notification_email_destination') ? $settings->get('notification_email_destination') : $this->config('system.site')
      ->get('mail');
    $form['notification_email_destination'] = array(
      '#type' => 'email',
      '#title' => t("Administrator's e-mail address:"),
      '#size' => 60,
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
      '#default_value' => $default_mail,
      '#description' => t('Address to use when the "Send e-mail to administrator…" option is checked.'),
    );


    $form['email_weight'] = array(
      '#type' => 'number',
      '#title' => t('Verification e-mail address field weight:'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $settings->get('email_weight'),
      '#description' => t('Weight of verification e-mail address field on create content form.'),
    );

    $form['autodelhours'] = array(
      '#type' => 'number',
      '#title' => t('Number of hours to retain unverified anonymous posts before auto-deletions removes them:'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $settings->get('autodelhours'),
      '#description' => t('Non-verified content will be automatically deleted after this time. Type "-1" for no limit.'),
    );

    $form['flood_limit'] = array(
      '#type' => 'number',
      '#title' => t('Number of anonymous posts allowed from a single user e-mail/ip allowed within an hour:'),
      '#size' => 3,
      '#maxlength' => 2,
      '#default_value' => $settings->get('flood_limit'),
      '#description' => t('Type "-1" for no limit.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('anonymous_publishing_cl.settings');

    // Save global configurations.
    $settings->set('allowed_content_types', $form_state->getValue('allowed_content_types'))
      ->set('general_options', $form_state->getValue('general_options'))
      ->set('verification_persistency', $form_state->getValue('verification_persistency'))
      ->set('notification_email_destination', $form_state->getValue('notification_email_destination'))
      ->set('email_weight', $form_state->getValue('email_weight'))
      ->set('autodelhours', $form_state->getValue('autodelhours'))
      ->set('flood_limit', $form_state->getValue('flood_limit'));

    if ($settings->get('retain_period') == -1) {
      $settings->set('user_alias', $form_state->getValue('user_alias'))
        ->set('byline_guidance', $form_state->getValue('byline_guidance'));
    }
    $settings->save();

    Cache::invalidateTags(['rendered']);

    parent::submitForm($form, $form_state);
  }

}