<?php

namespace Drupal\js;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use Drupal\js\Ajax\JsRedirectCommand;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * JS Callback Handler service.
 */
class Js implements ContainerAwareInterface, ContainerInjectionInterface {

  use ContainerAwareTrait;
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Any captured content from output buffering.
   *
   * @var string
   */
  public $capturedContent;

  /**
   * The current request's callback, if any.
   *
   * @var \Drupal\js\Plugin\Js\JsCallbackInterface
   */
  protected $callback;

  /**
   * @var \Drupal\js\JsCallbackManager
   */
  protected $callbackManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The response to the current request.
   *
   * @var \Drupal\js\JsResponse
   */
  protected $response;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The provided token from the request to be validated.
   *
   * @var string
   */
  protected $token;

  /**
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * Js constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack.
   * @param \Drupal\js\JsCallbackManager $callback_manager
   *   The callbacks plugin manager.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The token token generator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager service.
   */
  public function __construct(RequestStack $request_stack, JsCallbackManager $callback_manager, CsrfTokenGenerator $csrf_token, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager) {
    $this->requestStack = $request_stack;
    $this->callbackManager = $callback_manager;
    $this->tokenGenerator = $csrf_token;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->settings = \Drupal::config('js.settings');
  }

  /**
   * Passes alterable variables to specific EXTENSION_TYPE_alter().
   *
   * @param string|array $type
   *   A string describing the type of the alterable $data.
   * @param mixed $data
   *   The data that will be passed to EXTENSION_TYPE_alter() implementations
   *   to be altered.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::alter()
   * @see \Drupal\Core\Theme\ThemeManagerInterface::alter()
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    $this->moduleHandler->alter($type, $data, $context1, $context2);
    $this->themeManager->alter($type, $data, $context1, $context2);
  }

  /**
   * Checks the result from a callback to determine if it's redirection.
   *
   * @param mixed $result
   *   The result from the callback.
   *
   * @return \Drupal\js\Ajax\JsRedirectCommand|null
   *   Returns redirection command or NULL if not a redirection.
   */
  public function checkForRedirection($result = NULL) {
    $redirection = NULL;
    if ($this->isExecuting() && $result instanceOf RedirectResponse && $result->isRedirect()) {
      $this->getResponse()->setStatusCode($result->getStatusCode());
      $redirection = new JsRedirectCommand($result->getTargetUrl(), $result instanceof JsRedirectResponse && $result->isForced());
    }
    return $redirection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('plugin.manager.js.callback'),
      $container->get('csrf_token'),
      $container->get('module_handler'),
      $container->get('theme.manager')
    );
  }

  /**
   * Decodes an exception and retrieves the correct caller.
   *
   * @param \Exception|\Throwable $exception
   *   The exception object that was thrown.
   *
   * @return array
   *   An error in the format expected by \Drupal\js\Error::logError.
   */
  public function decodeException($exception) {
    return Error::decodeException($exception);
  }

  /**
   * Sends content to the browser via the delivery callback.
   *
   * @param mixed $result
   *   The content to pass to the delivery callback.
   * @param int $status_code
   *   A status code to set for the response.
   *
   * @return \Drupal\js\JsResponse
   *   A JsResponse object.
   */
  public function deliver($result = [], $status_code = NULL) {
    $response = $this->getResponse();

    if (isset($status_code)) {
      $response->setStatusCode($status_code);
    }

    // Capture buffered content.
    if ($captured_content = ob_get_clean()) {
      // If the callback has "capture_output" enabled, then allow extensions a
      // chance to alter the response via hook_js_captured_content_alter().
      if ($this->getCallback()->captureOutput()) {
        $this->alter('js_captured_content', $captured_content, $response, $this);
      }
      else {
        print $captured_content;
      }
    }

    // Check for redirection.
    if ($redirection = $this->checkForRedirection($result)) {
      $response->addCommand($redirection);
      $result = [];
    }

    // Set the result as the data and return the response.
    return $response->setData($result);
  }

  /**
   * Executes the requested JS Callback.
   *
   * @return \Drupal\js\JsResponse
   */
  public function execute() {
    // Normalize "ajax_page_state" by manually setting a custom JS parameter.
    // This is required since AjaxBasePageNegotiator::applies() only checks for
    // "request" parameters (which does not include query parameters from GET).
    $this->getRequest()->request->set('ajax_page_state', $this->getJsParameter('ajax_page_state'));

    // Retrieve any provided CSRF token.
    $this->token = $this->getJsParameter('token');

    $callback = $this->getCallback();
    $response = $this->getResponse();

    $result = [];
    try {
      // Check callback's allowed methods.
      $allowed_methods = $callback->getAllowedMethods();
      if (!in_array($this->getRequest()->getMethod(), $allowed_methods)) {
        throw new MethodNotAllowedHttpException($allowed_methods);
      }

      // Determine if a provided CSRF token should be validated.
      if ($callback->csrfToken()) {
        // The current user must also not be anonymous as tokens would be the
        // same for all anonymous users. This is a security requirement.
        if (\Drupal::currentUser()->isAnonymous()) {
          drupal_set_message($callback->anonymousUserMessage(), 'error', FALSE);
          throw new AccessDeniedHttpException();
        }
        // Check for invalid token.
        if (!$this->token || !$this->tokenGenerator->validate($this->token, 'js.callback:' . $callback->getPluginId())) {
          drupal_set_message($callback->invalidTokenMessage(), 'error', FALSE);
          throw new AccessDeniedHttpException();
        }
      }

      // Check callback access.
      $access = $callback->call('access');
      if (($access instanceof AccessResultInterface && !$access->isAllowed()) || !$access) {
        throw new AccessDeniedHttpException();
      }

      // Invoke the callback, if it validated.
      if ($callback->call('validate')) {
        $result = $callback->call('execute');
      }
    }
    catch (AccessDeniedHttpException $e) {
      drupal_set_message($callback->accessDeniedMessage(), 'error', FALSE);
      $response->setStatusCode(403);
      $callback->setTitle($this->t('Access Denied'));
    }
    catch (MethodNotAllowedHttpException $e) {
      drupal_set_message($callback->methodNotAllowedMessage(), 'error', FALSE);
      $response->setStatusCode(405);
      $callback->setTitle($this->t('Method Not Allowed'));
    }
    catch (\Exception $e) {
      // Since "multiple catch types" is only supported in PHP 7.1 and higher,
      // there must be a global "catch" and their types checked here.
      // @see https://wiki.php.net/rfc/multiple-catch
      if ($e instanceof NotFoundHttpException || $e instanceof ResourceNotFoundException || $e instanceof MatchingRouteNotFoundException) {
        $response->setStatusCode(404);
        $callback->setTitle($this->t('Page Not Found'));
      }

      // Otherwise, rethrow exception.
      throw $e;
    }

    // Deliver the result.
    return $this->deliver($result);
  }

  /**
   * Provides custom PHP error handling.
   *
   * @param $error_level
   *   The level of the error raised.
   * @param $message
   *   The error message.
   */
  public function errorHandler($error_level, $message) {
    if ($error_level & error_reporting()) {
      require_once \Drupal::root() . '/core/includes/errors.inc';

      $types = drupal_error_levels();
      list($severity_msg, $severity_level) = $types[$error_level];
      $backtrace = debug_backtrace();
      $caller = $this->getLastCaller($backtrace);

      // We treat recoverable errors as fatal.
      $recoverable = $error_level == E_RECOVERABLE_ERROR;
      // As __toString() methods must not throw exceptions (recoverable errors)
      // in PHP, we allow them to trigger a fatal error by emitting a user error
      // using trigger_error().
      $to_string = $error_level == E_USER_ERROR && substr($caller['function'], -strlen('__toString()')) == '__toString()';

      $error = [
        '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
        '@message' => Markup::create(Xss::filterAdmin($message)),
        '%function' => $caller['function'],
        '%file' => $caller['file'],
        '%line' => $caller['line'],
        'severity_level' => $severity_level,
        'backtrace' => $backtrace,
      ];

      static::logError($error, $recoverable || $to_string);
    }
  }

  /**
   * Provides custom PHP exception handling.
   *
   * Uncaught exceptions are those not enclosed in a try/catch block. They are
   * always fatal: the execution of the script will stop as soon as the
   * exception handler exits.
   *
   * @param $exception
   *   The exception object that was thrown.
   */
  public function exceptionHandler($exception) {
    require_once \Drupal::root() . '/core/includes/errors.inc';
    try {
      $this->logError($this->decodeException($exception), TRUE);
    }
    catch (\Exception $exception2) {
      // Another uncaught exception was thrown while handling the first one.
      // If we are displaying errors, then do so with no possibility of a further
      // uncaught exception being thrown.
      $message = '<h1>Additional uncaught exception thrown while handling exception.</h1>';
      $message .= '<h2>Original</h2><p>' . $this->renderExceptionSafe($exception) . '</p>';
      $message .= '<h2>Additional</h2><p>' . $this->renderExceptionSafe($exception2) . '</p>';
      $caller = $this->getLastCaller(debug_backtrace());

      $error = [
        '%type' => 'Unknown error',
        // The standard PHP error handler considers that the error messages
        // are HTML. Mimic this behavior here.
        '@message' => Markup::create(Xss::filterAdmin($message)),
        '%function' => $caller['function'],
        '%file' => $caller['file'],
        '%line' => $caller['line'],
        'severity_level' => RfcLogLevel::ERROR,
      ];

      $this->logError($error, TRUE);
    }
  }

  /**
   * Provides custom PHP fatal error handling.
   */
  public function fatalErrorHandler() {
    if ($error = error_get_last()) {
      require_once \Drupal::root() . '/core/includes/errors.inc';
      $error = [
        '%type' => 'Fatal Error',
        // The standard PHP error handler considers that the error messages
        // are HTML. Mimic this behavior here.
        '@message' => Markup::create(Xss::filterAdmin($error['message'])),
        '%file' => $error['file'],
        '%line' => $error['line'],
        'severity_level' => RfcLogLevel::ERROR,
      ];
      $this->logError($error, TRUE);
    }
  }

  /**
   * Retrieves the set callback.
   *
   * @return \Drupal\js\Plugin\Js\JsCallbackInterface
   */
  public function getCallback() {
    // Retrieve the provided callback, defaulting to "js.content" if not set.
    if (!isset($this->callback)) {
      $this->callback = $this->callbackManager->createInstance($this->getJsParameter('callback', 'js.content'));
    }
    return $this->callback;
  }

  /**
   * Retrieves the JS Callback endpoint.
   *
   * @return string
   *   The endpoint path.
   */
  public function getEndpoint() {
    return (string) $this->settings->get('endpoint') ?: '/js';
  }

  /**
   * Gets the last caller from a backtrace.
   *
   * @param array $backtrace
   *   A standard PHP backtrace. Passed by reference.
   *
   * @return array
   *   An associative array with keys 'file', 'line' and 'function'.
   */
  public function getLastCaller(array &$backtrace) {
    return Error::getLastCaller($backtrace);
  }

  /**
   * Retrieves parameter from current request prefixed with "js" and removes it.
   *
   * @param string $name
   *   The name of the parameter to retrieve, minus any "js" or "js_" prefix.
   * @param mixed $default
   *   The default value to return if parameter does not exist.
   * @param bool $remove
   *   Flag indicating whether parameter should be removed from the request.
   *
   * @return string|null
   *   The parameter value or the default value.
   */
  public function getJsParameter($name, $default = NULL, $remove = TRUE) {
    /** @var \Symfony\Component\HttpFoundation\ParameterBag $bag */
    foreach ([$this->getRequest()->query, $this->getRequest()->attributes, $this->getRequest()->request] as $bag) {
      if ($value = $bag->get("js_$name")) {
        if ($remove) {
          $bag->remove("js_$name");
        }
        return $value;
      }
    }
    return $default;
  }

  /**
   * The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   */
  public function getRequest() {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * Retrieves the currently set callback response object.
   *
   * @return \Drupal\js\JsResponse
   */
  public function getResponse() {
    if (!isset($this->response)) {
      $this->response = $this->getCallback()->getResponse()->setJs($this);
    }
    return $this->response;
  }

  /**
   * Retrieves the currently set theme from the request.
   *
   * @return string
   *   The theme machine name.
   */
  public function getTheme() {
    return $this->theme;
  }

  /**
   * Generate a unique token for JS callbacks.
   *
   * @param string $callback
   *   A callback object to retrieve the token for.
   *
   * @return string|array
   *   If $module and $callback are provided the unique token belonging to it
   *   is returned, otherwise all current tokens set are returned.
   */
  public function getToken($callback = NULL) {
    // Use the advanced drupal_static() pattern, since this has the potential to
    // be called quite often on a single page request.
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['tokens'] = &drupal_static(__METHOD__, []);
    }
    $tokens = &$drupal_static_fast['tokens'];

    // Return a specific token for a module callback.
    if ($callback) {
      // Only authenticated users should be allowed to generate tokens.
      if (!\Drupal::currentUser()->isAnonymous()) {
        return $tokens[$callback] = $this->tokenGenerator->get("js.callback:$callback");
      }
      else {
        return FALSE;
      }
    }

    // Otherwise return all tokens.
    return $tokens;
  }

  /**
   * Determines whether an error should be displayed.
   *
   * When in maintenance mode or when error_level is
   * ERROR_REPORTING_DISPLAY_ALL, all errors should be displayed. For
   * ERROR_REPORTING_DISPLAY_SOME, $error will be examined to determine if it
   * should be displayed.
   *
   * @param $error
   *   Optional error to examine for ERROR_REPORTING_DISPLAY_SOME.
   *
   * @return bool
   *   TRUE if an error should be displayed.
   */
  public function isErrorDisplayable($error = NULL) {
    if (defined('MAINTENANCE_MODE')) {
      return TRUE;
    }
    $error_level = NULL;
    try {
      $error_level = \Drupal::config('system.logging')->get('error_level');
    }
    catch (\Exception $e) {
      $error_level = isset($GLOBALS['config']['system.logging']['error_level']) ? $GLOBALS['config']['system.logging']['error_level'] : ERROR_REPORTING_HIDE;
    }
    if (!isset($error_level) || $error_level == ERROR_REPORTING_DISPLAY_ALL || $error_level == ERROR_REPORTING_DISPLAY_VERBOSE) {
      return TRUE;
    }
    if ($error_level == ERROR_REPORTING_DISPLAY_SOME && isset($error)) {
      return $error['%type'] != 'Notice' && $error['%type'] != 'Strict warning';
    }
    return FALSE;
  }

  /**
   * Indicates if the current request is executing a JS Callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   (Optional) A request object to check. If not provided, the current
   *   request will be used.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isExecuting(Request $request = NULL) {
    if (!isset($request)) {
      $request = $this->getRequest();
    }
    return $request->get('_controller') === 'js.callback:execute';
  }

  /**
   * Logs a PHP error or exception and displays the error in fatal cases.
   *
   * @param $error
   *   An array with the following keys: %type, @ message, %function, %file,
   *   %line and severity_level. All the parameters are plain-text, with the
   *   exception of @ message, which needs to be a safe HTML string.
   * @param $fatal
   *   TRUE if the error is fatal.
   */
  public function logError($error, $fatal = FALSE) {
    // Log the error immediately.
    if (\Drupal::hasService('logger.factory')) {
      try {
        \Drupal::logger('php')->log($error['severity_level'], '%type: @message in %function (line %line of %file).', $error);
      }
      catch (\Exception $e) {
        // We can't log, for example because the database connection is not
        // available. At least try to log to PHP error log.
        error_log(strtr('Failed to log error: %type: @message in %function (line %line of %file).', $error));
      }
    }

    // Display the error to the user, if it should.
    if ($this->isErrorDisplayable($error)) {
      if (!isset($error['%function'])) {
        drupal_set_message($this->t('%type: @message (line %line of %file).', $error), 'error');
      }
      else {
        drupal_set_message($this->t('%type: @message in %function (line %line of %file).', $error), 'error');
      }
    }

    // If fatal, deliver an internal server error response.
    if ($fatal) {
      $this->getResponse()->setStatusCode(500);
      $this->deliver()->prepare($this->getRequest())->send();
      exit;
    }
  }

  /**
   * Pre-render callback for #js_callback and #js_get properties.
   *
   * @param array $element
   *   The render array element.
   *
   * @return array
   *   The modified render array element.
   *
   * @see js_element_info_alter
   */
  public function preRenderJsCallback(array $element) {
    if (isset($element['#js_callback']) && $this->callbackManager->hasDefinition($element['#js_callback'])) {
      $element['#attached']['library'][] = 'js/callback';
      $element['#attributes']['data-js-callback'] = $element['#js_callback'];
      if ($token = $this->getToken($element['#js_callback'])) {
        $element['#attributes']['data-js-token'] = $token;
      }
    }
    elseif (isset($element['#js_get'])) {
      $path = $element['#js_get'];
      if ($path === TRUE) {
        $path = isset($element['#url']) ? $element['#url'] : '';
      }
      if ($path) {
        if (is_string($path)) {
          if ($url = \Drupal::pathValidator()->getUrlIfValidWithoutAccessCheck($element['#js_get'])) {
            $path = $url;
          }
        }
        if ($path instanceof Url) {
          $path = $path->toString();
        }
        $element['#attached']['library'][] = 'js/get';
        $element['#attributes']['data-js-get'] = 'js.content';
        $element['#attributes']['data-path'] = $path;
      }
    }
    return $element;
  }

  /**
   * Process callback for #js_callback and #js_get properties.
   *
   * @param array $element
   *   The render array element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current FormState object.
   * @param array $form
   *   The complete form render array.
   *
   * @return array
   *   The modified render array element.
   *
   * @see js_element_info_alter
   */
  public static function processJsCallback(array $element, FormStateInterface $form_state, &$form) {
    // @todo Add this back somehow?
//    if (isset($element['#js_callback'])) {
//      $element['#attached']['library'][] = 'js/form';
//    }
    return $element;
  }

  /**
   * Renders an exception error message without further exceptions.
   *
   * @param \Exception|\Throwable $exception
   *   The exception object that was thrown.
   *
   * @return string
   *   An error message.
   */
  public function renderExceptionSafe($exception) {
    return Error::renderExceptionSafe($exception);
  }

  /**
   * Flag indicating if PHP errors should be silenced.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function silencePhpErrors() {
    return !!$this->settings->get('silence_php_errors');
  }

}
