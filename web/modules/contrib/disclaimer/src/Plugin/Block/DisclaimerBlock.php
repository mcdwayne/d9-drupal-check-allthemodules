<?php

namespace Drupal\disclaimer\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'DisclaimerBlock' block.
 *
 * @Block(
 *  id = "disclaimer_block",
 *  admin_label = @Translation("Disclaimer block"),
 * )
 */
class DisclaimerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Overrides Drupal\Core\BlockBase::__construct().
   *
   * Creates a DisclaimerBlock instance.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PathValidatorInterface $path_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'cookies:disclaimer_' . $this->configuration['machine_name'],
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
      'machine_name' => 'disclaimer_block_' . time(),
      'redirect' => '/',
      'max_age' => 86400,
      'challenge' => [
        'format' => filter_fallback_format(),
        'value' => '',
      ],
      'agree' => $this->t('Yes'),
      'disagree' => $this->t('No'),
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
      '#description' => $this->t('The question the user must confirm. "Do you agree?" type of question. <em>Agree</em> = User stays on requested page. <em>Disagree</em> = User is redirected to <em>Redirect</em> url specified below.'),
      '#default_value' => $this->configuration['challenge']['value'],
      '#required' => TRUE,
      '#weight' => 30,
    ];
    $form['agree'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agree button'),
      '#description' => $this->t('Label for <em>Agree</em> button on challenge.'),
      '#default_value' => $this->configuration['agree'],
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => 40,
    ];
    $form['disagree'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disagree button'),
      '#description' => $this->t('Label for <em>Disagree</em> button on challenge.'),
      '#default_value' => $this->configuration['disagree'],
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#weight' => 50,
    ];
    $form['disclaimer'] = [
      '#type' => 'text_format',
      '#format' => $this->configuration['disclaimer']['format'],
      '#title' => $this->t('Disclaimer'),
      '#description' => $this->t('The text displayed to the user on a protected page when the user has JS turned off. (No popup with challenge is available.)'),
      '#default_value' => $this->configuration['disclaimer']['value'],
      '#weight' => 60,
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
    $this->configuration['agree'] = $form_state->getValue('agree');
    $this->configuration['disagree'] = $form_state->getValue('disagree');
    $this->configuration['disclaimer'] = $form_state->getValue('disclaimer');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $disclaimer_id = 'disclaimer_' . Html::escape($this->configuration['machine_name']);

    // Identify block by class with machine name.
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          $disclaimer_id,
          'disclaimer__noscript',
        ],
      ],
    ];

    // Include JS to handle popup and hiding.
    $build['#attached']['library'][] = 'disclaimer/disclaimer';
    // Pass settings to JS.
    $build['#attached']['drupalSettings']['disclaimer'][$disclaimer_id] = [
      'redirect' => $this->configuration['redirect'],
      'max_age' => Html::escape($this->configuration['max_age']),
      'agree' => Html::escape($this->configuration['agree']),
      'disagree' => Html::escape($this->configuration['disagree']),
    ];

    // Render disclaimer.
    $build['disclaimer_block_disclaimer'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'disclaimer__disclaimer',
        ],
      ],
      '#markup' => check_markup($this->configuration['disclaimer']['value'], $this->configuration['disclaimer']['format']),
    ];

    // Render popup HTML.
    $build['disclaimer_block_challenge'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'disclaimer__challenge',
          'hidden',
        ],
        'title' => [
          Html::escape($this->label()),
        ],
      ],
      '#markup' => check_markup($this->configuration['challenge']['value'], $this->configuration['challenge']['format']),
    ];

    return $build;
  }

}
