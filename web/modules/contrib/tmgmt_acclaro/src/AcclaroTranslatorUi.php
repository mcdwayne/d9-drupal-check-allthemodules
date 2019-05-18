<?php

namespace Drupal\tmgmt_acclaro;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * Acclaro translator UI.
 */
class AcclaroTranslatorUi extends TranslatorPluginUiBase {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Web Token'),
      '#default_value' => $translator->getSetting('token'),
      '#description' => $this->t('Please enter your Web Token or visit <a href="@url">My Acclaro Portal</a> to get one.', ['@url' => 'https://my.acclaro.com/portal/apireference.php']),
    ];
    $form['use_sandbox'] = [
      '#type' => 'checkbox',
      '#title' => t('Use the sandbox'),
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => t('Check to use the testing environment.'),
    ];
    $form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    if ($translator->getSetting('token')) {
      $account = $translator->getPlugin()->getAccount($translator);
      if (empty($account)) {
        $form_state->setError($form['plugin_wrapper']['settings']['token'], t('Web token is not valid.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {
    $form = [];

    if ($job->isActive()) {
      $form['actions']['pull'] = [
        '#type' => 'submit',
        '#value' => $this->t('Fetch translations'),
        '#submit' => [[$this, 'submitFetchTranslations']],
        '#weight' => -10,
      ];

      $remote_mappings = $job->getRemoteMappings();
      $remote_mapping = reset($remote_mappings);
      // Display simulate operations in case translator uses sandbox API key.
      if ($job->getTranslator()->getSetting('use_sandbox') && $remote_mapping) {
        $form['actions']['simulate_complete'] = [
          '#type' => 'submit',
          '#value' => $this->t('Simulate complete'),
          '#submit' => [[$this, 'submitSimulateOrderComplete']],
        ];

        // Check whether source plugin of the first item supports preview mode.
        $form['actions']['simulate_preview'] = [
          '#type' => 'submit',
          '#value' => $this->t('Simulate preview'),
          '#submit' => [[$this, 'submitSimulateTranslationPreview']],
          '#access' => $remote_mapping->getJobItem()->getSourcePlugin() instanceof SourcePreviewInterface,
        ];
      }
    }

    return $form;
  }

  /**
   * Submit callback to fetch translations from Acclaro.
   */
  public function submitFetchTranslations(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->fetchTranslations($job);
  }

  /**
   * Submit callback to simulate completed orders from Acclaro.
   */
  public function submitSimulateOrderComplete(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->simulateCompleteOrder($job);
  }

  /**
   * Submit callback to simulate translation previews from Acclaro.
   */
  public function submitSimulateTranslationPreview(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->simulateTranslationPreview($job);
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job) {
    $settings['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $job->getSetting('name'),
      '#description' => t('Set the name for the Acclaro order. By default, a job label will be used.'),
    ];
    $settings['comment'] = [
      '#type' => 'textfield',
      '#title' => t('Comment'),
      '#default_value' => $job->getSetting('comment'),
      '#description' => t('Set the comment for the Acclaro order.'),
    ];
    if (!$job->isContinuous()) {
      $settings['duedate'] = [
        '#type' => 'date',
        '#title' => t('Due Date'),
        '#default_value' => $job->getSetting('duedate'),
        '#description' => t('The deadline for providing a translation.'),
      ];
    }

    return $settings;
  }

}
