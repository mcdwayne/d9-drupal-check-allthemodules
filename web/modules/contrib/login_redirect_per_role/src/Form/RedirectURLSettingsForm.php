<?php

namespace Drupal\login_redirect_per_role\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RedirectURLSettingsForm.
 */
class RedirectURLSettingsForm extends ConfigFormBase {

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
      $container->get('config.factory'), $container->get('path.alias_manager'), $container->get('path.validator'), $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'login_redirect_per_role.redirecturlsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_url_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('login_redirect_per_role.redirecturlsettings');

    $form['default_site_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default URL'),
      '#required' => TRUE,
      '#default_value' => $config->get('default_site_url'),
    ];

    // Get available roles.
    $system_roles = user_role_names(TRUE);
    unset($system_roles['authenticated']);
    foreach ($system_roles as $key => $role) {
      $form['login_redirect_per_role_' . $key] = [
        '#type' => 'textfield',
        '#title' => 'Default URL for ' . $role,
        '#default_value' => $config->get('login_redirect_per_role_' . $key),
        '#description' => $this->t('Enter "/" at begin of URL.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (explode('login_redirect_per_role_', $key)[1] && $value) {
        if ($value[0] != '/') {
          $form_state->setErrorByName($key, 'Start URL with "/"');
        }
        if (!$this->pathValidator->isValid($value)) {
          $form_state->setErrorByName($key, $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $value]));
        }
      }

      if (!$this->pathValidator->isValid($values['default_site_url'])) {
        $form_state->setErrorByName('default_site_url', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $values['default_site_url']]));
      }
      $default_site_url = $values['default_site_url'];
      if ($default_site_url[0] != '/') {
        $form_state->setErrorByName('default_site_url', 'Start URL with "/"');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();

    $this->config('login_redirect_per_role.redirecturlsettings')
      ->set('default_site_url', $values['default_site_url'])
      ->save();

    foreach ($values as $key => $value) {
      if (explode('login_redirect_per_role_', $key)[1]) {
        $this->config('login_redirect_per_role.redirecturlsettings')
          ->set($key, $value)
          ->save();
      }
    }
  }

}
