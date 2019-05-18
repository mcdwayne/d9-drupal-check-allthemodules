<?php

namespace Drupal\tmgmt_powerling;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;

/**
 * Powerling translator UI.
 */
class PowerlingTranslatorUi extends TranslatorPluginUiBase
{
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState)
  {
    $form = parent::buildConfigurationForm($form, $formState);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $formState->getFormObject()->getEntity();

    $form['token'] = [
      '#type' => 'textfield',
      '#title' => 'Web Token',
      '#default_value' => $translator->getSetting('token'),
      '#description' => 'Please enter your Web Token',
    ];
    $form['use_sandbox'] = [
      '#type' => 'checkbox',
      '#title' => 'Use the sandbox',
      '#default_value' => $translator->getSetting('use_sandbox'),
      '#description' => 'Check to use the sandbox environment.',
    ];
    $form += parent::addConnectButton();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState)
  {
    parent::validateConfigurationForm($form, $formState);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $formState->getFormObject()->getEntity();
    if ($translator->getSetting('token')) {
      $account = $translator->getPlugin()->getAccount($translator);
      if (empty($account)) {
        $formState->setError($form['plugin_wrapper']['settings']['token'], 'Web token is not valid.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job)
  {
    $form = [];

    if ($job->isActive()) {
      $form['actions']['pull'] = [
        '#type' => 'submit',
        '#value' => 'Fetch translations',
        '#submit' => [[$this, 'submitFetchTranslations']],
        '#weight' => -10,
      ];

      $remoteMappings = $job->getRemoteMappings();
      $remote_mapping = reset($remoteMappings);

      if ($job->getTranslator()->getSetting('use_sandbox') && $remote_mapping) {
        $form['actions']['simulate_complete'] = [
          '#type' => 'submit',
          '#value' => 'Simulate complete',
          '#submit' => [[$this, 'submitSimulateOrderComplete']],
        ];
        $form['actions']['simulate_preview'] = [
          '#type' => 'submit',
          '#value' => 'Simulate preview',
          '#submit' => [[$this, 'submitSimulateTranslationPreview']],
          '#access' => $remote_mapping->getJobItem()->getSourcePlugin() instanceof SourcePreviewInterface,
        ];
      }
    }

    return $form;
  }

  /**
   * Submit callback to fetch translations from Powerling.
   */
  public function submitFetchTranslations(array $form, FormStateInterface $formState)
  {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $formState->getFormObject()->getEntity();

    /** @var \Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->fetchTranslations($job);
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutSettingsForm(array $form, FormStateInterface $formState, JobInterface $job)
  {
    $settings['name'] = [
      '#type' => 'textfield',
      '#title' => 'Name',
      '#default_value' => $job->getSetting('name'),
      '#description' => 'Set the name for the Powerling order. By default, a job label will be used.',
    ];
    $settings['comment'] = [
      '#type' => 'textfield',
      '#title' => 'Comment',
      '#default_value' => $job->getSetting('comment'),
      '#description' => 'Set the comment for the Powerling order.',
    ];
    if (!$job->isContinuous()) {
      $settings['duedate'] = [
        '#type' => 'date',
        '#title' => 'Due Date',
        '#default_value' => $job->getSetting('duedate'),
        '#description' => 'The deadline for providing a translation.',
      ];
    }

    return $settings;
  }

  /**
   * Submit callback to simulate completed orders from Powerling.
   */
  public function submitSimulateOrderComplete(array $form, FormStateInterface $formState)
  {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $formState->getFormObject()->getEntity();

    /** @var \Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->simulateCompleteOrder($job);
  }

  /**
   * Submit callback to simulate translation previews from Powerling.
   */
  public function submitSimulateTranslationPreview(array $form, FormStateInterface $formState)
  {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $job = $formState->getFormObject()->getEntity();
    /** @var \Drupal\tmgmt_powerling\Plugin\tmgmt\Translator\PowerlingTranslator $translator_plugin */
    $translator_plugin = $job->getTranslator()->getPlugin();
    $translator_plugin->simulateTranslationPreview($job);
  }
}

