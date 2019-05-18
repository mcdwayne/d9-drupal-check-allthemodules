<?php

/**
 * @file
 * Contains \Drupal\droogle\Form\DroogleSettingsForm.
 */

namespace Drupal\droogle\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class DroogleSettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'droogle_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['droogle.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $droogle_config = $this->config('droogle.settings');

    $form['sitewide'] = array(
      '#type' => 'details',
      '#title' => t('Sitewide Droogle settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => t(
        'To create client IDs, secret: <ol>
          <li>Visit <a href="@url">Google Console</a> and create a project to use.</li>
          <li>Enable the Drive API and the Drive SDK under APIs tab</li>
          <li>Generate client IDs/Secret under the Credentials tab</li>
          <li>Add @url_refresh to "Redirect URIs" for the new client ID.</li>
          </ol>',
        [
          '@url' => 'https://cloud.google.com/console',
        ]
      ),
    );

    $form['sitewide']['droogle_title_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Droogle Page Title'),
      '#default_value' => $droogle_config->get('title_text'),
      '#required' => TRUE,
      '#size' => 60,
      '#maxlength' => 64,
      '#description' => t(
        'Enter the title to put at the top of the <a href="@url">droogle page</a> (when not within an Organic Groups context),
        default is: "DROOGLE: A list of your google docs"',
        [
          '@url' => 'droogle'
        ]
      ),
    );

    $form['sitewide']['droogle_client_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#default_value' => $droogle_config->get('client_id'),
      '#required' => TRUE,
      '#size' => 100,
      '#maxlength' => 150,
      '#description' => t('The site wide google client id.'),
    );

    $form['sitewide']['droogle_client_secret'] = array(
      '#type' => 'textfield',
      '#title' => t('Client secret'),
      '#default_value' => $droogle_config->get('client_secret'),
      '#required' => TRUE,
      '#description' => t('The site wide google client secret.'),
    );

    $default_uri = $droogle_config->get('droogle_redirect_callback') != '' ? $droogle_config->get('droogle_redirect_callback') : DROOGLE_BROWSER_URL;
    $form['sitewide']['droogle_redirect_callback'] = array(
      '#type' => 'textfield',
      '#title' => t('Droogle redirect callback'),
      '#description' => t(
        "Google returns user to uri '@default_url' after the successful authentication. It's need to setup the tokens.
        You can define your own callback and redefine DroogleController::droogleNavigator. You should use only internal urls without slash in the beginning.
        For example '@default_url', 'my/best/callback'. Just leave it as is if you don't need it.",
        [
          '@default_url' => DROOGLE_BROWSER_URL,
        ]
      ),
      '#default_value' => $default_uri,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('droogle.settings')
      ->set('title_text', $form_state->getValue('droogle_title_text'))
      ->set('client_id', $form_state->getValue('droogle_client_id'))
      ->set('client_secret', $form_state->getValue('droogle_client_secret'))
      ->set('droogle_redirect_callback', $form_state->getValue('droogle_redirect_callback'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
