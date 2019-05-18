<?php

namespace Drupal\inmail_demo\Form;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\MessageProcessorInterface;
use Drupal\inmail\ProcessorResultInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for manually entering email source code to be processed.
 */
class PasteForm extends FormBase {

  /**
   * The injected message processor service.
   *
   * @var \Drupal\inmail\MessageProcessorInterface
   */
  protected $messageProcessor;

  /**
   * The injected deliverer config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $delivererStorage;

  /**
   * The injected module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new PasteForm object.
   */
  public function __construct(MessageProcessorInterface $message_processor, ConfigEntityStorageInterface $deliverer_storage, ModuleHandlerInterface $module_handler, TranslationInterface $translation, UrlGeneratorInterface $url_generator) {
    $this->messageProcessor = $message_processor;
    $this->delivererStorage = $deliverer_storage;
    $this->moduleHandler = $module_handler;
    $this->setStringTranslation($translation);
    $this->setUrlGenerator($url_generator);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('inmail.processor'),
      $container->get('entity.manager')->getStorage('inmail_deliverer'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('url_generator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'inmail_demo_paste';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $deliverer_options = array_map(function(DelivererConfig $deliverer_config) {
      return $deliverer_config->label();
    }, $this->getDelivererConfigs());

    $form['deliverer'] = [
      '#type' => 'select',
      '#title' => $this->t('Deliverer'),
      '#description' => $this->t('Choose one of the <a href="@deliverers_url">configured Paste deliverers</a>.', [
        '@deliverers_url' => $this->url('entity.inmail_deliverer.collection'),
      ]),
      '#options' => $deliverer_options,
      '#required' => TRUE,
      '#default_value' => count($deliverer_options) == 1 ? reset($deliverer_options) : $form_state->getValue('deliverer'),
    ];

    $form['example'] = [
      '#type' => 'select',
      '#title' => $this->t('Examples'),
      '#description' => $this->t('Load an example email to edit.'),
      '#options' => $this->getExampleOptions(),
      '#required' => FALSE,
    ];

    $form['example_load_button'] = [
      '#type' => 'submit',
      '#value' => t('Load example'),
      '#ajax' => [
        'wrapper' => 'raw-edit',
        'callback' => [$this, 'rawReplace'],
        'method' => 'replace',
      ],
      '#submit' => [[$this, 'loadExample']],
      '#limit_validation_errors' => [['example']],
    ];
    $form['raw'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email source'),
      '#prefix' => '<div id="raw-edit">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    ];

    $form['raw']['text'] = [
      '#type' => 'textarea',
      '#description' => $this->t('The source code of the email you want processed.'),
      '#rows' => 25,
    ];

    $form['actions'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process email'),
      '#button_type' => 'primary',
    ];

    if (empty($deliverer_options)) {
      $form['deliverer']['#disabled'] = TRUE;
      $form['raw']['#disabled'] = TRUE;
      drupal_set_message($this->t('Please <a href="@deliverers_url">create a Paste deliverer</a> to enable manual processing.', [
        '@deliverers_url' => $this->url('entity.inmail_deliverer.add_form'),
      ]));
    }

    return $form;
  }
  /**
   * Returns a properly formatted list of examples for the #options.
   *
   * @return array
   *   Example filenames keyed by file paths.
   */
  protected function getExampleOptions() {
    $collection = [];
    // Adding to the list simple mail example.
    $directory = drupal_get_path('module', 'inmail_demo') . '/eml';
    $mail_samples = $this->getExamples($directory);
    $directory = drupal_get_path('module', 'inmail') . '/tests/modules/inmail_test/eml/simple';
    $mail_samples += $this->getExamples($directory);
    $collection += ['Simple' => $mail_samples];

    // Adding attachment, malformed, bounce, address samples.
    $directory = drupal_get_path('module', 'inmail') . '/tests/modules/inmail_test/eml';
    $collection += ['Multipart / Attachment' => $this->getExamples($directory . '/attachments')];
    $collection += ['Malformed' => $this->getExamples($directory . '/malformed')];
    $collection += ['Bounce' => $this->getExamples($directory . '/bounce')];
    $collection += ['Addresses' => $this->getExamples($directory . '/addresses')];

    // Adding rest of email samples.
    $examples = $this->getExamples($directory, FALSE);
    $collection += ['Others' => $examples];

    return $collection;
  }

  /**
   * Returns collection of messages from given directory.
   *
   * @param string $directory
   *   Path from which to load messages.
   * @param bool $recursion
   *   Whether to skip folders in search.
   *
   * @return array
   *   Associative array of messages and keyed indexes.
   */
  protected function getExamples($directory , $recursion = TRUE) {
    // Filter only eml files.
    $examples = array_keys(file_scan_directory($directory, '/.eml/', array('recurse' => $recursion)));
    return array_map('basename', array_combine($examples, $examples));
  }

  /**
   * Returns the wrapper for the effected form element.
   */
  public function loadExample(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $file = file_get_contents($form_state->getValue('example'));
    $input = $form_state->getUserInput();
    $input['text'] = $file;
    $form_state->setUserInput($input);
  }

  /**
   * Returns the wrapper for the effected form element.
   *
   * @return array
   *   form element which will be replaced with the new element.
   */
  public function rawReplace(array $form, FormStateInterface $form_state) {
    return $form['raw'];
  }

  /**
   * Loads and returns all available deliverer configs using Paste deliverer.
   *
   * @return \Drupal\inmail\Entity\DelivererConfig[]
   *   All enabled and available Paste deliverer configs.
   */
  protected function getDelivererConfigs() {
    $ids = $this->delivererStorage->getQuery()
      ->condition('plugin', 'paste')
      ->condition('status', TRUE)
      ->execute();
    $deliverers = $this->delivererStorage->loadMultiple($ids);
    return array_filter($deliverers, function ($deliverer) {
      return $deliverer->isAvailable();
    });
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $deliverer_config = $this->delivererStorage->load($form_state->getValue('deliverer'));
    /** @var ProcessorResultInterface $result */
    $result = $this->messageProcessor->process('unique_key', $form_state->getValue('text'), $deliverer_config);
    if ($result->isSuccess()) {
      drupal_set_message($this->t('The message has been processed.'));
    }
    else {
      drupal_set_message($this->t('Error while processing message.'), 'error');
      // Display errors from processing.
      $messages = inmail_get_log_message($result, RfcLogLevel::ERROR);
      drupal_set_message(strip_tags(implode("\n", $messages)), 'error');
    }
    if ($this->moduleHandler->moduleExists('past_db')) {
      drupal_set_message($this->t('See the <a href="@log_url">Past log</a> for results.', ['@log_url' => $this->url('view.past_event_log.page_1')]));
    }
  }

}
