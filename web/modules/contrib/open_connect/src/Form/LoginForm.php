<?php

namespace Drupal\open_connect\Form;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\open_connect\Controller\RedirectController;
use Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface;
use Drupal\open_connect\UncacheableTrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface
   */
  protected $pluginManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a new LoginForm object.
   *
   * @param \Drupal\open_connect\Plugin\OpenConnect\ProviderManagerInterface $plugin_manager
   *   The plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(ProviderManagerInterface $plugin_manager, RequestStack $request_stack, CsrfTokenGenerator $csrf_token) {
    $this->pluginManager = $plugin_manager;
    $this->requestStack = $request_stack;
    $this->csrfToken= $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.open_connect.provider'),
      $container->get('request_stack'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'open_connect_login_form';
  }

  /**
   * {@inheritdoc}
   *
   * @todo: WeChat MP should only be available on WeChat Client.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugins = $this->pluginManager->getDefinitions();
    $config = $this->config('open_connect.settings');
    $enabled_providers = $config->get('providers');
    foreach ($plugins as $id => $definition) {
      if (!isset($enabled_providers[$id])) continue;
      $form['open_connect_login_' . $id] = [
        '#type' => 'submit',
        '#value' => $this->t('Log in with @label', ['@label' => $definition['label']]),
        '#name' => $id,
        '#prefix' => '<div class="open-connect-login">',
        '#suffix' => '</div>',
      ];
    }
    CacheableMetadata::createFromRenderArray($form)
      ->addCacheableDependency($config)
      ->applyTo($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#name'];
    $enabled_providers = $this->config('open_connect.settings')->get('providers');
    /** @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface $provider */
    $provider = $this->pluginManager->createInstance($id, $enabled_providers[$id]);

    $request = $this->requestStack->getCurrentRequest();
    // Save something in the session attribute bag, or the session could not be
    // persisted, see SessionManager::save().
    // By settings values in the session bag, the session will be automatically
    // started for the anonymous user.
    $configuration = $request->getSession()->get('open_connect', []);
    $configuration['operation'] = 'login';
    $configuration['return_uri'] = open_connect_get_return_uri($request->getRequestUri());
    $request->getSession()->set('open_connect', $configuration);

    $state = $this->csrfToken->get(RedirectController::TOKEN_KEY);
    $url = $provider->getAuthorizeUrl($state)->toString();
    // Uncacheable because the response depends on a dynamic crsf token.
    $form_state->setResponse(new UncacheableTrustedRedirectResponse($url));
  }

}
