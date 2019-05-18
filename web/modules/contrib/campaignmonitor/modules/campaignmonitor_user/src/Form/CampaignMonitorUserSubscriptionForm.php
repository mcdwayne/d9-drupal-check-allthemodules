<?php

namespace Drupal\campaignmonitor_user\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;

/**
 * Subscribe to a campaignmonitor list.
 */
class CampaignMonitorUserSubscriptionForm extends FormBase {

  /**
   * The ID for this form.
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'campaignmonitor_user_subscribe_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   *
   */
  public function setFormId($formId) {
    $this->formId = $formId;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor_user.subscribe_form'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {

    // Get the e-mail address from the user object.
    // This is not working - url is always user 1.
    $account = User::load($user);

    $current_user = \Drupal::currentUser();
    $account = User::load($current_user->id());
    $email = $account->get('mail')->getValue()[0]['value'];
    $config = \Drupal::config('campaignmonitor_user.settings');

    $form = [];

    $form['email'] = [
      '#type' => 'hidden',
      '#value' => $email,
    ];

    $form['name'] = [
      '#type' => 'hidden',
      '#value' => $account->get('name')->getValue()[0]['value'],
    ];

    $lists = campaignmonitor_get_lists();

    // Build options for the form selector.
    $options = [];
    $option_descriptions = [];
    $default = [];

    foreach ($lists as $list_id => $list) {
      // Check if the list is selected to be shown.
      $list_options = campaignmonitor_get_list_settings($list_id);

      if (campaignmonitor_is_list_enabled($list_id)) {
        $options[$list_id] = $list['name'];
        $defaults = campaignmonitor_get_list_settings($list_id);

        $option_descriptions[$list_id] = $defaults['display']['description'];

        // Check if the user is subscribed to the current list.
        $default[$list_id] = 0;
        if (campaignmonitor_is_subscribed($list_id, $email)) {
          $default[$list_id] = $list_id;
        }
      }
    }

    if (!empty($options)) {
      $form['subscription_text'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'subscription-text',
          ],
        ],
      ];
      $form['subscription_text']['text'] = [
        '#markup' => $config->get('subscription_text'),
      ];

      $form['lists'] = [
        '#type' => 'checkboxes',
        '#title' => $config->get('list_heading'),
      // '#description' => !empty($config['instructions']) ? t($config['instructions']) : t('Select the news lists
      //        that you want to subscribe to.'),.
        '#options' => $options,
        '#default_value' => $default,
        '#option_descriptions' => $option_descriptions,
        '#after_build' =>
        ['\Drupal\campaignmonitor_user\Form\CampaignMonitorUserSubscriptionForm::_option_descriptions'],
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Update subscriptions'),
        '#attached' => [
          'library' => ['campaignmonitor_user/campaignmonitor_user.subscriptions'],
        ],
      ];
    }
    else {
      drupal_set_message('There are no available lists to subscribe to at the moment.', 'warning');
    }

    return $form;
  }

  /**
   *
   */
  public static function _option_descriptions($element, &$form_state) {
    foreach (Element::children($element) as $key) {
      $element[$key]['#description'] = t('@description', [
        '@description' => $element['#option_descriptions'][$key],
      ]);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $lists = campaignmonitor_get_lists();

    $values = $form_state->getValues();

    $subscribed = FALSE;

    $config = $form_state->getValue('config');
    $config = unserialize($config);

    // Loop through the lists.
    foreach ($values['lists'] as $list_id => $selected) {

      if ($selected !== 0) {
        // Maybe this is an unsubscribe.
        if (campaignmonitor_subscribe($list_id, $values['email'], $values['name'])) {
          drupal_set_message(t('You are now subscribed to the "@list" list.', [
            '@list' => html_entity_decode($lists[$list_id]['name']),
          ]), 'status');
          $subscribed = TRUE;
        }
        else {
          drupal_set_message(t('You were not subscribed to the list. Please try again later.'));

        }
        // Check if the user should be sent to an unsubscribe page.
        //          if (isset($lists_info[$list_id]['details']['UnsubscribePage']) && !empty($lists_info[$list_id]['details']['UnsubscribePage'])) {
        //            drupal_goto($lists_info[$list_id]['details']['UnsubscribePage']);
        //          }
        //          else {
        //            drupal_set_message(t('You are now removed from the "@list" list.', array('@list' => $lists_info[$list_id]['name'])), 'status');
        //          }.
      }
      else {
        campaignmonitor_unsubscribe($list_id, $values['email']);
      }

    }
    if ($subscribed) {
      drupal_set_message(t('Changes to your preferences will take a minute or two to show on the site.'));
    }
    // Return to user page
    // If we stay on the form it will display the old information.
    $url = Url::fromRoute('user.page');
    $form_state->setRedirectUrl($url);

  }

}
