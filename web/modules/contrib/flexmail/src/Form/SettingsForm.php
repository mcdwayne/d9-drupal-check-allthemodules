<?php

/**
 * @file
 * Contains \Drupal\flexmail\Form\SettingsForm
 */

namespace Drupal\flexmail\Form;

use Behat\Mink\Exception\Exception;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\flexmail\Config\DrupalConfig;
use Finlet\flexmail\FlexmailAPI\FlexmailAPI;

class SettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flexmail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'flexmail.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('flexmail.settings');

    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => t('Global settings FlexMail'),
    ];

    $form['global']['wsdl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('WSDL URL'),
      '#default_value' => $config->get('wsdl'),
      '#required' => TRUE,
    );

    $form['global']['service'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Service URL'),
      '#default_value' => $config->get('service'),
      '#required' => TRUE,
    );

    $form['global']['user_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#default_value' => $config->get('user_id'),
      '#required' => TRUE,
    );

    $form['global']['user_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User token'),
      '#default_value' => $config->get('user_token'),
      '#required' => TRUE,
    );

    $form['global']['debug_mode'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#return_value' => 1,
      '#default_value' => $config->get('debug_mode'),
    );

    $languages = \Drupal::languageManager()->getLanguages();

    foreach ($languages as $ln => $language) {
      $form[$ln] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings for language: @name', array('@name' => $language->getName())),
        '#tree' => TRUE,
      ];

      // Create form for each language.
      $form[$ln]['list_id'] = array(
        '#type' => 'select',
        '#title' => $this->t('@name mailing list (ID)', array('@name' => $language->getName())),
        '#options' => $this->getMailingLists(),
        '#default_value' => $config->get($ln . '_list_id'),
      );

      $form[$ln]['optin_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $config->get($ln . '_optin_enabled'),
      ];


      $form[$ln]['optin_messageId'] = [
        '#type' => 'select',
        '#title' => $this->t('ID of the message to send for @name', array('@name' => $language->getName())),
        '#options' => $this->getOptinLists(),
        '#default_value' => $config->get($ln . '_optin_messageId'),
      ];

      $form[$ln]['optin_replyEmail'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Reply email address for the campaign'),
        '#default_value' => $config->get($ln . '_optin_replyEmail'),
      ];

      $form[$ln]['optin_senderEmail'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Sender email address for the campaign'),
        '#default_value' => $config->get($ln . '_optin_senderEmail'),
      ];

      $form[$ln]['optin_senderName'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Sender name for the campaign'),
        '#default_value' => $config->get($ln . '_optin_senderName'),
      ];

      $form[$ln]['optin_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject for the campaign'),
        '#default_value' => $config->get($ln . '_optin_subject'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $conf = $this->config('flexmail.settings')
      ->set('wsdl', $values['wsdl'])
      ->set('service', $values['service'])
      ->set('user_id', $values['user_id'])
      ->set('user_token', $values['user_token'])
      ->set('debug_mode', $values['debug_mode']);

    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $ln => $language) {
      foreach ($values[$ln] as $key => $value) {
        $conf->set($ln . '_' . $key, $value);
      }
    }
    $conf->save();

    parent::submitForm($form, $form_state);
  }

  /**
   *
   */
  private function getMailingLists() {
    $config = $this->config('flexmail.settings');
    if ($config->get('wsdl') && $config->get('service') && $config->get('user_id') && $config->get('user_token')) {
      $flexmail = new FlexmailAPI(new DrupalConfig($config));

      // Get all categories.
      try {
        $categories = $flexmail->service('Category')->getAll();

        $mailinglist_list = array();
        foreach ($categories->categoryTypeItems as $category) {
          $mailinglists = $flexmail->service('List')
            ->getAll(array('categoryId' => $category->categoryId));

          foreach ($mailinglists->mailingListTypeItems as $mailinglist) {
            $mailinglist_list[$mailinglist->mailingListId] = $mailinglist->mailingListName;
          }
        }

        return $mailinglist_list;
      }
      catch (Exception $e) {
        $message = MarkupTrait::create($e->getMessage());
        drupal_set_message($message);
      }
    }

    return array('--Configure credentials first--');
  }

  /**
   * Helper function to retrieve optinLists
   */
  private function getOptinLists() {
    $config = $this->config('flexmail.settings');
    if ($config->get('wsdl') && $config->get('service') && $config->get('user_id') && $config->get('user_token')) {
      $flexmail = new FlexmailAPI(new DrupalConfig($config));

      // Get all categories.
      try {
        $messages = $flexmail->service('Message')
          ->getAll([
            'archived' => TRUE,
            'optin' => TRUE,
            'archivedMessages' => TRUE
          ]);
        $messages_list = array();
        foreach ($messages->messageTypeItems as $message) {
          $messages_list[$message->messageId] = urldecode($message->messageName);
        }

        return $messages_list;
      }
      catch (Exception $e) {
        $message = MarkupTrait::create($e->getMessage());
        drupal_set_message($message);
      }
    }

    return array('--Configure credentials first--');
  }
}

?>