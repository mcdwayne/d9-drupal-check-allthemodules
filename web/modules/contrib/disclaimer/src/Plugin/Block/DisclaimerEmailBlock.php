<?php

namespace Drupal\disclaimer\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'DisclaimerEmailBlock' block.
 *
 * @Block(
 *  id = "disclaimer_email_block",
 *  admin_label = @Translation("Disclaimer E-mail block"),
 * )
 */
class DisclaimerEmailBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The FormBuilder object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Overrides Drupal\Core\BlockBase::__construct().
   *
   * Creates a DisclaimerEmailBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PathValidatorInterface $path_validator, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->pathValidator = $path_validator;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.validator'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'cookies:disclaimer_email_' . $this->configuration['machine_name'],
      'url.path',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->configuration['max_age'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'machine_name' => 'disclaimer_email_block_' . time(),
      'redirect' => '/',
      'max_age' => 86400,
      'challenge' => [
        'format' => filter_fallback_format(),
        'value' => '',
      ],
      'allowed_emails' => '*',
      'email_validation_fail' => 'Your address is not on the list of allowed e-mails.',
      'disclaimer' => [
        'format' => filter_fallback_format(),
        'value' => '',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect'),
      '#description' => $this->t('The URL a rejected user is sent to. eg. /content-for-unconfirmed-users. (relative, absolute, &lt;front&gt;)'),
      '#default_value' => $this->configuration['redirect'],
      '#maxlength' => 256,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max-age'),
      '#description' => $this->t('The time in seconds the user is confirmed. Set to 0 for no expiry. (86400 seconds = 24 hours)'),
      '#default_value' => $this->configuration['max_age'],
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => 20,
    ];
    $form['challenge'] = [
      '#type' => 'text_format',
      '#format' => $this->configuration['challenge']['format'],
      '#title' => $this->t('Challenge'),
      '#description' => $this->t('The question the user must confirm by entering an e-mail address. "Do you agree?" type of question. <em>Continue</em> = User stays on requested page. <em>Disagree</em> = User is redirected to <em>Redirect</em> url specified below.'),
      '#default_value' => $this->configuration['challenge']['value'],
      '#required' => TRUE,
      '#weight' => 30,
    ];
    $form['allowed_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed e-mails'),
      '#description' => $this->t('List of allowed e-mails. One rule per line. Supports <em>*</em> wildcard. For example: <em>*@example.com</em>'),
      '#default_value' => $this->configuration['allowed_emails'],
      '#weight' => 60,
      '#required' => TRUE,
    ];
    $form['email_validation_fail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail rejected message'),
      '#description' => $this->t("Error message displayed when form is submitted and user's e-mail is rejected."),
      '#default_value' => $this->configuration['email_validation_fail'],
      '#maxlength' => 512,
      '#size' => 512,
      '#required' => TRUE,
      '#weight' => 70,
    ];
    $form['disclaimer'] = [
      '#type' => 'text_format',
      '#format' => $this->configuration['disclaimer']['format'],
      '#title' => $this->t('Disclaimer'),
      '#description' => $this->t('The text displayed to the user on a protected page when the user has JS turned off. (No popup with challenge is available.)'),
      '#default_value' => $this->configuration['disclaimer']['value'],
      '#weight' => 80,
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $url_object = $this->pathValidator->getUrlIfValid($form_state->getValue('redirect'));
    if (!$url_object) {
      $form_state->setErrorByName('redirect', $this->t('Redirect URL must be valid path.'));
    }
    if (!preg_match('/^[0-9]+$/', $form_state->getValue('max_age'))) {
      $form_state->setErrorByName('max_age', $this->t('Max-age must be integer.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Form\SubformStateInterface $form_state */
    $this->configuration['machine_name'] = $form_state->getCompleteFormState()
      ->getValue('id');
    $this->configuration['redirect'] = $form_state->getValue('redirect');
    $this->configuration['max_age'] = $form_state->getValue('max_age');
    $this->configuration['challenge'] = $form_state->getValue('challenge');
    $this->configuration['allowed_emails'] = $form_state->getValue('allowed_emails');
    $this->configuration['email_validation_fail'] = $form_state->getValue('email_validation_fail');
    $this->configuration['disclaimer'] = $form_state->getValue('disclaimer');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $disclaimer_email_id = 'disclaimer_email_' . Html::escape($this->configuration['machine_name']);

    // Identify block by class with machine name.
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          $disclaimer_email_id,
          'disclaimer_email__noscript',
        ],
      ],
    ];

    // Include JS to handle popup and hiding.
    $build['#attached']['library'][] = 'disclaimer/disclaimer_email';
    // Pass settings to JS.
    $build['#attached']['drupalSettings']['disclaimer_email'][$disclaimer_email_id] = [
      'redirect' => $this->configuration['redirect'],
    ];

    // Render disclaimer.
    $build['disclaimer_email_block_disclaimer'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'disclaimer_email__disclaimer',
        ],
      ],
      '#markup' => check_markup($this->configuration['disclaimer']['value'], $this->configuration['disclaimer']['format']),
    ];

    // Render popup HTML.
    $build['disclaimer_email_block_challenge'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'disclaimer_email__challenge',
          'hidden',
        ],
        'title' => [
          Html::escape($this->label()),
        ],
      ],
      '#markup' => check_markup($this->configuration['challenge']['value'], $this->configuration['challenge']['format']),
    ];

    // Render E-mail challenge.
    $build['disclaimer_email_block_challenge']['disclaimer_email_block_email_match'] = $this->formBuilder
      ->getForm('\Drupal\disclaimer\Form\DisclaimerEmailMatchForm');
    $build['disclaimer_email_block_challenge']['disclaimer_email_block_email_match']['block_id']['#value'] = $this->configuration['machine_name'];

    return $build;
  }

}
