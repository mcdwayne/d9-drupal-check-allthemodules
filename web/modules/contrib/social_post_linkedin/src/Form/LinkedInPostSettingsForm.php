<?php

namespace Drupal\social_post_linkedin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Post LinkedIn.
 */
class LinkedInPostSettingsForm extends ConfigFormBase {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $request_context) {
    parent::__construct($config_factory);
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_post_linkedin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_post_linkedin.form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_post_linkedin.settings');

    $form['linkedin_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a LinkedIn App at <a href="@linkedin-dev">@linkedin-dev</a>', ['@linkedin-dev' => 'https://www.linkedin.com/secure/developer']),
    ];

    $form['linkedin_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID of your LinkedIn App here. This value can be found from your App Dashboard.'),
    ];

    $form['linkedin_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret of your LinkedIn App here. This value can be found from your App Dashboard.'),
    ];

    $form['linkedin_settings']['oauth_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your LinkedIn App settings.'),
      '#default_value' => $GLOBALS['base_url'] . '/user/social-post/linkedin/auth/callback',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_post_linkedin.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
