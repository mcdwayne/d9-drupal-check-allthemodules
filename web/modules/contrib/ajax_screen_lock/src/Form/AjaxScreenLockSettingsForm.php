<?php

namespace Drupal\ajax_screen_lock\Form;

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
class AjaxScreenLockSettingsForm extends ConfigFormBase {

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
    return 'ajax_screen_lock_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ajax_screen_lock.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ajax_screen_lock.settings');

    $form['popup_title'] = array(
      '#title' => t('Title of screen lock popup'),
      '#type' => 'textfield',
      '#default_value' => $config->get('popup_title'),
    );

    $form['popup_timeout'] = array(
      '#title' => t('Timeout of displaying screen lock popup in ms'),
      '#type' => 'textfield',
      '#default_value' => $config->get('popup_timeout'),
    );

    $form['throbber_hide'] = array(
      '#title' => t('Hide ajax progress throbber'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('throbber_hide', FALSE),
    );

    // Per-page-path visibility.
    $form['visibility']['pages_path'] = array(
      '#type' => 'fieldset',
      '#title' => t('Pages condition'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 0,
    );

    // Per-request-path visibility.
    $form['visibility']['request_path'] = array(
      '#type' => 'fieldset',
      '#title' => t('Ajax request paths condition'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#group' => 'visibility',
      '#weight' => 1,
    );

    $form['visibility']['pages_path']['disable_in_admin'] = array(
      '#title' => t('Disable for admin pages'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('disable_in_admin', 1),
    );

    $form['visibility']['pages_path']['pages_path'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#description' => t("Allowed only relative urls. On pages listed here the AjaxScreenLock will be enabled. Enter one page per line as Drupal paths.
    The '*' character is a wildcard. Example paths are '<em>blog</em>' for the blog page and '<em>blog/*</em>' for every personal blog.
    '<em>&lt;front&gt;</em>' is the front page."),
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('pages_path', ''),
    );

    $form['visibility']['pages_path']['path_ignore'] = array(
      '#type' => 'textarea',
      '#title' => t('Ignore pages'),
      '#description' => t("Allowed only relative urls. On pages listed here the AjaxScreenLock will be disabled. Enter one page per line as Drupal paths.
    The '*' character is a wildcard. Example paths are '<em>blog</em>' for the blog page and '<em>blog/*</em>' for every personal blog.
    '<em>&lt;front&gt;</em>' is the front page."),
      '#cols' => 40,
      '#rows' => 5,
      '#default_value' => $config->get('path_ignore', ''),
    );

    $options = array(
      AJAX_SCREEN_LOCK_VISIBILITY_NOTLISTED => t('All pages except those listed'),
      AJAX_SCREEN_LOCK_VISIBILITY_LISTED => t('Only the listed pages'),
    );

    $form['visibility']['request_path']['request_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Add AjaxScreenLock for specific paths of the ajax requests.'),
      '#options' => $options,
      '#default_value' => $config->get('request_visibility', AJAX_SCREEN_LOCK_VISIBILITY_NOTLISTED),
    );
    $form['visibility']['request_path']['request_path'] = array(
      '#type' => 'textarea',
      '#description' => t("Allowed only relative urls. Enter one path per line. <b>Notice: no wildcard supported</b>
     <p>You may use only beginning of the URL. For example, <i>example/path</i> will block/allow all URLs like <i>example/path/1, example/path/2 and etc</i>"),
      '#default_value' => $config->get('request_path', ''),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $timeout = $form_state->getValue('popup_timeout');
    if (!is_numeric($timeout) || $timeout <= 0) {
      $form_state->setErrorByName('popup_timeout', t('The field Timeout must contain positive numeric value'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ajax_screen_lock.settings')
      ->set('popup_title', $form_state->getValue('popup_title'))
      ->set('popup_timeout', $form_state->getValue('popup_timeout'))
      ->set('throbber_hide', $form_state->getValue('throbber_hide'))
      ->set('disable_in_admin', $form_state->getValue('disable_in_admin'))
      ->set('pages_path', $form_state->getValue('pages_path'))
      ->set('path_ignore', $form_state->getValue('path_ignore'))
      ->set('request_visibility', $form_state->getValue('request_visibility'))
      ->set('request_path', $form_state->getValue('request_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
