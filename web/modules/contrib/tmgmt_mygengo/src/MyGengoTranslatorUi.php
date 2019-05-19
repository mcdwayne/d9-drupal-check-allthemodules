<?php

/**
 * @file
 * Contains Drupal\tmgmt_mygengo\MyGengoTranslatorUi.
 */

namespace Drupal\tmgmt_mygengo;

use Drupal;
use Drupal\Core\Url;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\TMGMTException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\RemoteMappingInterface;

/**
 * @file
 * Provides Gengo translation plugin controller.
 */
class MyGengoTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function reviewDataItemElement(array $form, FormStateInterface $form_state, $data_item_key, $parent_key, array $data_item, JobItemInterface $item) {

    /** @var \Drupal\tmgmt\RemoteMappingInterface $mapping */
    $mapping = NULL;
    foreach ($item->getRemoteMappings() as $value) {
      if ($value->data_item_key->value == $data_item_key) {
        $mapping = $value;
        break;
      }
    }

    // If no mapping found or mapping is not yet complete return.
    if (empty($mapping) || !$mapping->getRemoteIdentifier2()) {
      return $form;
    }

    $gengo_job_id = $mapping->getRemoteIdentifier2();
    $target_key = str_replace('][', '|', $data_item_key);

    // For state pending we need to check also for type as empty value will
    // evaluate as 0.
    if ($data_item['#status'] == TMGMT_DATA_ITEM_STATE_REVIEWED || $data_item['#status'] === TMGMT_DATA_ITEM_STATE_PENDING) {
      $form['actions'][$gengo_job_id . '_comment_form'] = array(
        '#type' => 'submit',
        '#value' => '✉',
        '#attributes' => array('title' => t('Add new comment'), 'class' => array($gengo_job_id . '-gengo-id', 'new-comment-button', 'gengo-button')),
        '#submit' => array('tmgmt_mygengo_gengo_action_form_submit'),
        '#name' => $gengo_job_id . '_comment_form',
        '#limit_validation_errors' => [],
        '#gengo_action' => 'comment',
        '#gengo_job_id' => $gengo_job_id,
        '#target_key' => $target_key,
        '#ajax' => array(
          'callback' => 'tmgmt_mygengo_review_form_input_pane_ajax',
          'wrapper' => $gengo_job_id . '-input-wrapper',
        ),
      );
    }

    if ($data_item['#status'] == TMGMT_DATA_ITEM_STATE_TRANSLATED) {
      $form['actions'][$gengo_job_id . '_revision_form'] = array(
        '#type' => 'submit',
        '#value' => '✍',
        '#name' => $gengo_job_id . '_revision_form',
        '#limit_validation_errors' => [],
        '#attributes' => array('title' => t('Request new revision'), 'class' => array($gengo_job_id . '-gengo-id', 'request-revision-button', 'gengo-button')),
        '#submit' => array('tmgmt_mygengo_gengo_action_form_submit'),
        '#gengo_action' => 'revision',
        '#gengo_job_id' => $gengo_job_id,
        '#target_key' => $target_key,
        '#ajax' => array(
          'callback' => 'tmgmt_mygengo_review_form_input_pane_ajax',
          'wrapper' => $gengo_job_id . '-input-wrapper',
        ),
      );
    }

    $form['below'][$gengo_job_id . '_gengo'] = array(
      '#type' => 'fieldset',
      '#title' => t('Comments for gengo job #%gengo_job_id', array('%gengo_job_id' => $gengo_job_id)),
      '#prefix' => '<div class="gengo-pane" id="' . $gengo_job_id . '-gengo-pane">',
      '#suffix' => '</div>',
    );

    // Input pane.
    $form['below'][$gengo_job_id . '_gengo']['input_wrapper'] = array(
      '#prefix' => '<div class="input-wrapper" id="' . $gengo_job_id . '-input-wrapper">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    );

    if ($form_state->get('active_gengo_job_id') == $gengo_job_id) {
      $form['below'][$gengo_job_id . '_gengo']['input_wrapper'] +=
          $this->getCommentForm(tmgmt_review_form_element_ajaxid($parent_key), $gengo_job_id, $form_state->get('gengo_action'), $mapping);
    }
    // Input pane end.

    // Comments pane.
    $form['below'][$gengo_job_id . '_gengo']['comments_wrapper'] = array(
      '#prefix' => '<div class="comments-wrapper" id="' . $gengo_job_id . '-comments-wrapper">',
      '#suffix' => '</div>',
    );

    $thread = $this->fetchComments($item->getTranslator(), $gengo_job_id, !($form_state->get('submitted_gengo_action')));

    $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['comments'] = array(
      '#type' => 'markup',
      '#markup' => t('There are no comments for this item yet.'),
      '#tree' => FALSE,
    );

    if (!empty($thread)) {
      $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['comments'] = array(
        '#theme' => 'tmgmt_mygengo_comments_thread',
        '#thread' => $thread,
        '#gengo_job_id' => $gengo_job_id,
      );
      /*['#theme'] = 'tmgmt_mygengo_comments_thread';
      $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['comments']['#thread'] = $thread;
      $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['comments']['#gengo_job_id'] = $gengo_job_id;*/
    }

    if (!($form_state->get('submitted_gengo_action'))) {
      $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['submitted_comment_gengo_id'] = array(
        '#type' => 'hidden',
        '#value' => $gengo_job_id,
      );
      $form['below'][$gengo_job_id . '_gengo']['comments_wrapper']['submitted_gengo_action'] = array(
        '#type' => 'hidden',
        '#value' => $form_state->get('submitted_gengo_action'),
      );
    }
    // Comments pane end.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function reviewForm(array $form, FormStateInterface $form_state, JobItemInterface $item) {

    $form['#attached']['library'][] = 'tmgmt_mygengo/comments';

    return $form;
  }

  /**
   * Builds form comment form based on the action.
   *
   * @param string $ajax_id
   *   For ajax id.
   * @param int $gengo_job_id
   *   Gengo job id.
   * @param string $action
   *   Action requested by user (comment, revision)
   * @param object $mapping
   *   Data item mapping object.
   *
   * @return array
   *   Built form array.
   */
  protected function getCommentForm($ajax_id, $gengo_job_id, $action, $mapping) {

    $target_key = str_replace('][', '|', $mapping->data_item_key->value);

    $submit_base = array(
      '#type' => 'submit',
      '#name' => $gengo_job_id . '_submit',
      '#gengo_job_id' => $gengo_job_id,
      '#target_key' => $target_key,
      '#ajax' => array(
        'callback' => '::ajaxReviewForm',
        'wrapper' => $ajax_id,
      ),
    );

    if ($action == 'revision') {
      $form[$gengo_job_id . '_comment'] = array(
        '#type' => 'textarea',
        '#title' => t('Revision comment'),
        '#description' => t('Provide instructions for the translator.'),
      );
      $form[$gengo_job_id . '_data_item_key'] = array(
        '#type' => 'value',
        '#value' => $mapping->data_item_key->value,
      );

      $form[$gengo_job_id . '_submit_revision'] = $submit_base + array(
        '#value' => t('Request revision'),
        // Using same validator as for comment.
        '#validate' => array('tmgmt_mygengo_add_comment_form_validate'),
        '#submit' => array('tmgmt_mygengo_add_revision_form_submit'),
      );
    }
    elseif ($action == 'comment') {
      $form[$gengo_job_id . '_comment'] = array(
        '#type' => 'textarea',
        '#title' => t('New comment'),
      );

      $form[$gengo_job_id . '_submit_comment'] = $submit_base + array(
        '#value' => t('Submit comment'),
        '#validate' => array('tmgmt_mygengo_add_comment_form_validate'),
        '#submit' => array('tmgmt_mygengo_add_comment_form_submit'),
      );
    }

    $form[$gengo_job_id . '_cancel'] = array(
      '#type' => 'submit',
      '#name' => $gengo_job_id . '_cancel',
      '#value' => t('Cancel'),
      '#attributes' => array('class' => array( $gengo_job_id . '-gengo-id', 'cancel-comment-button')),
      '#submit' => array('tmgmt_mygengo_gengo_cancel_form_submit'),
      '#gengo_action' => '',
      '#gengo_job_id' => $gengo_job_id,
      '#target_key' => $target_key,
      '#ajax' => array(
        'callback' => 'tmgmt_mygengo_review_form_input_pane_ajax',
        'wrapper' => $gengo_job_id . '-input-wrapper',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $url = Url::fromUri('https://gengo.com/account/api_settings/');
    $gengo_key_url = \Drupal::l(t('Gengo Api Settings'), $url);
    $form['api_public_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Gengo API Public key'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('api_public_key'),
      '#description' => t('Please enter your Gengo API Public key or visit @link to create a new one', array('@link' => $gengo_key_url)),
    );
    $form['api_private_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Gengo API Private key'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('api_private_key'),
      '#description' => t('Please enter your Gengo API Private key or visit @link to create a new one', array('@link' => $gengo_key_url)),
    );
    $form['mygengo_auto_approve'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically approve jobs at Gengo side.'),
      '#default_value' => $translator->getSetting('mygengo_auto_approve'),
      '#description' => t('Check to auto approve translated jobs from Gengo. This setting will skip the review process and automatically mark jobs at Gengo side as approved by you.'),
      '#prefix' => '<div class="mygengo-auto-approve">',
      '#suffix' => '</div>',
    );
    $form['use_sandbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use the sandbox'),
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => t('Check to use the testing environment.'),
      '#prefix' => '<div class="mygengo-use-sandbox">',
      '#suffix' => '</div>',
    );
    $form['use_preferred'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use preferred translators'),
      '#default_value' => $translator->getSetting('use_preferred'),
      '#description' => t('Check to use translators from the preferred translators list associated with your Gengo account.'),
    );
    $form['show_remaining_credits_info'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show remaining credit info'),
      '#default_value' => $translator->getSetting('show_remaining_credits_info'),
      '#description' => t('Check to display remaining Gengo credit at the job checkout page.'),
    );
    $form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $translator = $job->getTranslator();

    // Set the quality setting from submitted vals - we need this for quote as
    // repetitive change of Quality select will not update the job settings.
    if ($form_state->getValue(['settings','quality'])) {
      $quality = array(
        'quality' => $form_state->getValue(['settings','quality']),
      );
      $job->settings = $quality;
    }

    // In case quality has not been set yet, init it to default.
    if (!($job->getSetting('quality'))) {
      $standard = array(
        'quality' => 'standard',
      );
      $job->settings = $standard;
    }

    $settings['quality'] = array(
      '#type' => 'select',
      '#title' => t('Quality'),
      '#options' => $this->getAvailableTiersOptions($job),
      '#default_value' => $job->getSetting('quality'),
      '#description' => t('Choose the level of quality for this translation job.'),
      '#ajax' => array(
        'callback' => '::ajaxTranslatorSelect',
        'wrapper' => 'tmgmt-ui-translator-wrapper',
      ),
    );

    if ($job->getSetting('quality') == 'machine') {
      return $settings;
    }

    $quote = $this->getQuoteInfo($job);
    $credit_info = $this->getRemainingCreditInfo($translator);

    $settings['price_quote'] = array(
      '#type' => 'item',
      '#title' => t('Price quote'),
      '#markup' => t('<strong>@sum_word_count</strong> words, <strong>@sum_credits @currency</strong>.', array(
        '@sum_word_count' => isset($quote['sum_word_count']) ? $quote['sum_word_count'] : t('Unknown'),
        '@sum_credits' => number_format($quote['sum_credits'], 2),
        '@currency' => $quote['currency'],
      )),
    );
    if ($translator->getSetting('show_remaining_credits_info')) {
      $settings['remaining_credits'] = array(
        '#type' => 'item',
        '#title' => t('Remaining Credits'),
        '#markup' => $credit_info['credits'] . ' ' . $credit_info['currency'],
      );
    }
    $settings['eta'] = array(
      '#type' => 'item',
      '#title' => t('ETA'),
      '#markup' => format_date(time() + $quote['highest_eta'], "long"),
    );

    $settings['comment'] = array(
      '#type' => 'textarea',
      '#title' => t('Instructions'),
      '#description' => t('You can provide a set of instructions so that the translator will better understand your requirements.'),
      '#default_value' => !($job->getSetting('comment')) ? $job->getSetting('comment') : NULL,
    );

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
        '#submit' => array('_tmgmt_mygengo_pull_submit'),
        '#weight' => -10,
      );
    }

    return $form;
  }

  /**
   * Get a quote from Gengo for the given job.
   *
   * @param Job $job
   *   Job for which to get a quote.
   *
   * @return array
   *   Array with the following keys: currency, estimates, highest_eta,
   *   sum_credits, sum_eta, sum_word_count, and unit_price.
   * @throws \Drupal\tmgmt\TMGMTException
   *   In case of error doing request to gengo service.
   */
  protected function getQuoteInfo(JobInterface $job) {
    $response = NULL;
    /* @var \Drupal\tmgmt_mygengo\Plugin\tmgmt\Translator\MyGengoTranslator $plugin */
    $plugin = $job->getTranslator()->getPlugin();

    try {
      $response = $plugin->sendJob($job, TRUE);
    }
    catch (TMGMTException $e) {
      watchdog_exception('tmgmt_mygengo', $e);
      drupal_set_message($e->getMessage(), 'error');
    }

    // Setup empty values.
    $quote = array(
      'currency' => '',
      'estimated' => FALSE,
      'highest_eta' => 0,
      'sum_credits' => 0,
      'sum_word_count' => 0,
    );

    if (!empty($response['jobs'])) {
      $jobs = (array) $response['jobs'];

      $quote['currency'] = reset($jobs)['currency'];

      // Sum up quotes from each job.
      foreach ($response['jobs'] as $job) {
        $quote['sum_word_count'] += $job['unit_count'];
        $quote['sum_credits'] += $job['credits'];

        if ($job['eta'] > $quote['highest_eta']) {
          $quote['highest_eta'] = $job['eta'];
        }
      }
    }

    return $quote;
  }

  /**
   * Gets remaining credit info at gengo account.
   *
   * @param \Drupal\tmgmt\Entity\Translator $translator
   *   Translator.
   *
   * @return array
   *   Associative array of currency and credits.
   */
  protected function getRemainingCreditInfo(Translator $translator) {
    $connector = new GengoConnector($translator, Drupal::httpClient());
    $credit_info = array(
      'credits' => NULL,
      'currency' => NULL,
    );
    try {
      $response = $connector->getRemainingCredit();
      $credit_info['credits'] = $response['credits'];
      $credit_info['currency'] = $response['currency'];
    }
    catch (TMGMTException $e) {
      watchdog_exception('tmgmt_mygengo', $e);
      drupal_set_message($e->getMessage(), 'error');
    }

    return $credit_info;
  }

  /**
   * Builds quality/tier options for src/tgt language pair of the job.
   *
   * @param \Drupal\tmgmt\Entity\Job $job
   *   Translation job.
   *
   * @return array
   *   Associative array of tiers with info.
   */
  protected function getAvailableTiersOptions(JobInterface $job) {

    $translator = $job->getTranslator();

    $tier_names = array(
      'machine' => t('Machine'),
      'standard' => t('Standard'),
      'pro' => t('Business'),
      'ultra' => t('Ultra'),
      'nonprofit' => t('Nonprofit'),
    );

    $available_tiers = array();
    // Machine translation is always available.
    $available_tiers['machine'] = $tier_names['machine'];

    $connector = new GengoConnector($translator, Drupal::httpClient());
    $gengo_language_pairs = array();

    try {
      $gengo_language_pairs = $connector->getLanguagePairs($translator);
    }
    catch (TMGMTException $e) {
      watchdog_exception('tmgmt_mygengo', $e);
      drupal_set_message($e->getMessage(), 'error');
    }

    foreach ($gengo_language_pairs as $tier) {
      // Skip if for other language pairs.
      if ($tier['lc_src'] != $translator->mapToRemoteLanguage($job->getSourceLangcode()) || $tier['lc_tgt'] != $translator->mapToRemoteLanguage($job->getTargetLangcode())) {
        continue;
      }

      $available_tiers[$tier['tier']] = t('@tier (@cost @currency per word)', array(
        '@tier' => empty($tier_names[$tier['tier']]) ? $tier['tier'] : $tier_names[$tier['tier']],
        '@cost' => number_format($tier['unit_price'], 2),
        '@currency' => $tier['currency'],
      ));
    }

    // @todo Gengo service does not support ultra quality for grouped jobs
    // and we send all jobs as grouped. We need to wait until gengo will support
    // grouped jobs for ultra quality as well.
    if (isset($available_tiers['ultra'])) {
      unset($available_tiers['ultra']);
    }

    return $available_tiers;
  }

  /**
   * Fetches comments from gengo service.
   *
   * @param \Drupal\tmgmt\Entity\Translator $translator
   *   Translator plugin.
   * @param int $gengo_job_id
   *   Gengo job id for which to fetch comments.
   * @param bool $reload
   *   Flag to reload cache.
   *
   * @return array
   *   List if comments or an empty array.
   */
  public function fetchComments(Translator $translator, $gengo_job_id, $reload = FALSE) {

    $cid = 'tmgmt_mygengo_comments_' . $gengo_job_id;
    $cache =  \Drupal::cache('data')->get($cid);

    if (isset($cache->data) && !$reload && $cache->expire > REQUEST_TIME) {
      return $cache->data;
    }

    $connector = new GengoConnector($translator, Drupal::httpClient());
    $response = NULL;

    try {
      $response = $connector->getComments($gengo_job_id);

      $data = isset($response['thread']) ? $response['thread'] : NULL;
      \Drupal::cache('data')->set($cid, $data, REQUEST_TIME + TMGMT_MYGENGO_COMMENTS_CACHE_EXPIRE);
      return $data;
    }
    catch (TMGMTException $e) {
      drupal_set_message($e->getMessage(), 'error');
      watchdog_exception('tmgmt_mygengo', $e);
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $statistics = $translator->getPlugin()->getStatistics($translator);
    if (empty($statistics)) {
      $form_state->setErrorByName('settings][api_public_key', t('The "Gengo API Public key" is not correct.'));
    }
  }

}
