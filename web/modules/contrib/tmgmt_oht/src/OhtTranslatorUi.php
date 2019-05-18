<?php

/**
 * @file
 * Contains Drupal\tmgmt_oht\OhtTranslatorUi.
 */

namespace Drupal\tmgmt_oht;

use Drupal;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\JobInterface;

/**
 * OHT translator UI.
 */
class OhtTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item) {
    /** @var Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $item->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($item->getTranslator());
    $mappings = $item->getRemoteMappings();
    /** @var Drupal\tmgmt\Entity\RemoteMapping $mapping */
    $mapping = array_shift($mappings);
    $comments = $translator_plugin->getProjectComments($mapping->getRemoteIdentifier1());
    $form['#attached'] = array('library' => array('tmgmt_oht/comments'));
    $form['oht_comments'] = array(
      '#type' => 'fieldset',
      '#title' => t('Comments'),
      '#prefix' => '<div id="tmgmt-oht-comments-wrapper">',
      '#suffix' => '</div>',
    );
    $form['oht_comments']['container'] = array(
      '#theme' => 'tmgmt_oht_comments',
      '#comments' => $comments,
    );
    $form['oht_comments']['comment'] = array(
      '#type' => 'textarea',
      '#title' => t('Comment text'),
      '#prefix' => '<a name="new-comment"></a>',
    );
    $form['oht_comments']['comment_submitted'] = array(
      '#type' => 'hidden',
      '#value' => $form_state->has('comment_submitted') ? $form_state->get('comment_submitted') : 0,
    );
    $form['oht_comments']['add_comment'] = array(
      '#type' => 'submit',
      '#value' => t('Add new comment'),
      '#submit' => array(array($this, 'submitAddComment')),
      '#validate' => array(array($this, 'validateComment')),
      '#ajax' => array(
        'callback' => array($this, 'updateReviewForm'),
        'wrapper' => 'tmgmt-oht-comments-wrapper',
      ),
    );

    return $form;
  }

  /**
   * Validates submitted OHT comment.
   */
  public function validateComment($form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('comment'))) {
      $form_state->setErrorByName('comment', t('The submitted comment cannot be empty.'));
    }
  }

  /**
   * Submit callback to add new comment to an OHT project.
   */
  public function submitAddComment(array $form, FormStateInterface $form_state) {
    /* @var JobItemInterface $job_item */
    $job_item = $form_state->getFormObject()->getEntity();

    /** @var Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $job_item->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($job_item->getTranslator());
    $mappings = $job_item->getRemoteMappings();

    try {
      /* @var Drupal\tmgmt\Entity\RemoteMapping $mapping */
      $mapping = array_shift($mappings);
      $translator_plugin->addProjectComment($mapping->getRemoteIdentifier1(), $form_state->getValue('comment'));
      $form_state->set('comment_submitted', 1);
      // Clear the value in the text comment field.
      if (!empty($form_state->getUserInput()['comment'])) {
        $user_input = $form_state->getUserInput();
        $user_input['comment'] = '';
        $form_state->setUserInput($user_input);
      }
      $form_state->setRebuild();
    }
    catch (Drupal\tmgmt\TMGMTException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['registration_link'] = [
      '#type' => 'markup',
      '#markup' => t('To support further development of this project, use the following link when registering for an account: <a href="@url" alt="OneHourTranslation">@url</a>.', ['@url' => 'http://www.onehourtranslation.com/affiliate/mirod']),
    ];
    $form['api_public_key'] = array(
      '#type' => 'textfield',
      '#title' => t('OHT API Public Key'),
      '#default_value' => $translator->getSetting('api_public_key'),
      '#description' => t('Please enter your public API key or visit <a href="@url">the API keys page</a> to get one.', ['@url' => 'https://www.onehourtranslation.com/profile/apiKeys']),
    );
    $form['api_secret_key'] = array(
      '#type' => 'textfield',
      '#title' => t('OHT API Secret key'),
      '#default_value' => $translator->getSetting('api_secret_key'),
      '#description' => t('Please enter your secret API key or visit <a href="@url">the API keys page</a> to get one.', ['@url' => 'https://www.onehourtranslation.com/profile/apiKeys']),
    );
    $form['use_sandbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the sandbox'),
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => t('Check to use the testing environment.'),
    );
    $form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    /** @var Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $translator->getPlugin();
    $translator_plugin->setTranslator($translator);
    $settings = $form['plugin_wrapper']['settings'];
    // Create account details service call to check if public and secret keys
    // are valid.
    $account_details = $translator_plugin->getAccountDetails();

    if (isset($settings['api_secret_key']) && !$account_details) {
      $form_state->setError($settings['api_secret_key'], t('The "OHT API Public key" or "OHT API Secret key" is not valid.'));
    }
  }

  /**
   * Ajax callback for the OHT comment form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   OHT comments.
   */
  public function updateReviewForm(array $form, FormStateInterface $form_state) {
    return $form['oht_comments'];
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    /** @var \Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->setTranslator($job->getTranslator());

    $settings['expertise'] = array(
      '#type' => 'select',
      '#title' => t('Expertise'),
      '#submit' => array('::submitBuildJob'),
      '#executes_submit_callback' => TRUE,
      '#description' => t('Select an expertise to identify the area of the text you will request to translate.'),
      '#empty_option' => ' - ',
      '#options' => $translator_plugin->getExpertise($job),
      '#default_value' => $job->getSetting('expertise') ? $job->getSetting('expertise') : '',
      '#ajax' => array(
        'callback' => '::ajaxTranslatorSelect',
        'wrapper' => 'tmgmt-ui-translator-wrapper',
      ),
    );
    $settings['notes'] = array(
      '#type' => 'textarea',
      '#title' => t('Instructions'),
      '#description' => t('You can provide a set of instructions so that the translator will better understand your requirements.'),
      '#default_value' => $job->getSetting('notes') ? $job->getSetting('notes') : '',
    );
    if ($price_quote = $translator_plugin->getQuotation($job)) {
      $currency = $price_quote['currency'] == 'EUR' ? '€' : $price_quote['currency'];
      $total = $price_quote['total'];
      $settings['price_quote'] = array(
        '#type' => 'item',
        '#title' => t('Price quote'),
        '#markup' => t('<strong>@word_count</strong> words, <strong>@credits</strong> credits (<strong>@total_price@currency</strong>)', [
          '@word_count' => $total['wordcount'],
          '@net_price' => $total['net_price'],
          '@transaction_fee' => $total['transaction_fee'],
          '@total_price' => $total['price'],
          '@credits' => $total['credits'],
          '@currency' => $currency,
        ]),
      );
    }
    if ($account_details = $translator_plugin->getAccountDetails()) {
      $settings['account_balance'] = array(
        '#type' => 'item',
        '#title' => t('Account balance'),
        '#markup' => t('<strong>@credits</strong> credits', array('@credits' => $account_details['credits'])),
      );
    }
    if (isset($account_details['credits']) && isset($total['price']) && $account_details['credits'] < $total['price']) {
      $settings['low_account_balance'] = array(
        '#type' => 'container',
        '#markup' => t('Your account balance is lower than quoted price and the translation will not be successful.'),
        '#attributes' => array(
          'class' => array('messages messages--warning'),
        ),
      );
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $form = array();

    if ($job->isActive()) {
      $form['actions']['pull'] = array(
        '#type' => 'submit',
        '#value' => t('Pull translations'),
        '#submit' => array(array($this, 'submitPullTranslations')),
        '#weight' => -10,
      );
    }

    return $form;
  }

  /**
   * Submit callback to pull translations form OHT.
   */
  public function submitPullTranslations(array $form, FormStateInterface $form_state) {
    /** @var Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();
    /** @var Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->fetchJobs($job);
    tmgmt_write_request_messages($job);
  }

}
