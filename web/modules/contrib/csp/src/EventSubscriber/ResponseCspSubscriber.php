<?php

namespace Drupal\csp\EventSubscriber;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\csp\Csp;
use Drupal\csp\LibraryPolicyBuilder;
use Drupal\csp\ReportingHandlerPluginManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ResponseSubscriber.
 */
class ResponseCspSubscriber implements EventSubscriberInterface {

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Library Policy Builder service.
   *
   * @var \Drupal\csp\LibraryPolicyBuilder
   */
  protected $libraryPolicyBuilder;

  /**
   * The Reporting Handler Plugin Manager service.
   *
   * @var \Drupal\csp\ReportingHandlerPluginManager
   */
  private $reportingHandlerPluginManager;

  /**
   * Constructs a new ResponseSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config Factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Module Handler service.
   * @param \Drupal\csp\LibraryPolicyBuilder $libraryPolicyBuilder
   *   The Library Parser service.
   * @param \Drupal\csp\ReportingHandlerPluginManager $reportingHandlerPluginManager
   *   The Reporting Handler Plugin Manager service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler,
    LibraryPolicyBuilder $libraryPolicyBuilder,
    ReportingHandlerPluginManager $reportingHandlerPluginManager
  ) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->libraryPolicyBuilder = $libraryPolicyBuilder;
    $this->reportingHandlerPluginManager = $reportingHandlerPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE] = ['onKernelResponse'];
    return $events;
  }

  /**
   * Add Content-Security-Policy header to response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $cspConfig = $this->configFactory->get('csp.settings');
    $libraryDirectives = $this->libraryPolicyBuilder->getSources();

    $response = $event->getResponse();

    if ($response instanceof CacheableResponseInterface) {
      $response->getCacheableMetadata()
        ->addCacheTags(['config:csp.settings']);
    }

    foreach (['report-only', 'enforce'] as $policyType) {

      if (!$cspConfig->get($policyType . '.enable')) {
        continue;
      }

      $policy = new Csp();
      $policy->reportOnly($policyType == 'report-only');

      foreach ($cspConfig->get($policyType . '.directives') as $directiveName => $directiveOptions) {

        if (is_bool($directiveOptions)) {
          $policy->setDirective($directiveName, TRUE);
          continue;
        }

        // This is a directive with a simple array of values.
        if (!isset($directiveOptions['base'])) {
          $policy->setDirective($directiveName, $directiveOptions);
          continue;
        }

        switch ($directiveOptions['base']) {
          case 'self':
            $policy->setDirective($directiveName, "'self'");
            break;

          case 'none':
            $policy->setDirective($directiveName, "'none'");
            break;

          case 'any':
            $policy->setDirective($directiveName, "*");
            break;
        }

        if (!empty($directiveOptions['flags'])) {
          $policy->appendDirective($directiveName, array_map(function ($value) {
            return "'" . $value . "'";
          }, $directiveOptions['flags']));
        }

        if (!empty($directiveOptions['sources'])) {
          $policy->appendDirective($directiveName, $directiveOptions['sources']);
        }

        if (isset($libraryDirectives[$directiveName])) {
          $policy->appendDirective($directiveName, $libraryDirectives[$directiveName]);
        }
      }

      // Prior to Drupal 8.7, in order to support IE9, CssCollectionRenderer
      // outputs more than 31 stylesheets as inline @import statements.
      // @see https://www.drupal.org/node/2993171
      // Since checking the actual number of stylesheets included on the page is
      // more difficult, just check the optimization settings, as in
      // HtmlResponseAttachmentsProcessor::processAssetLibraries()
      // @see CssCollectionRenderer::render()
      // @see HtmlResponseAttachmentsProcessor::processAssetLibraries()
      if (
        (
          version_compare(\Drupal::VERSION, '8.7', '<')
          ||
          $this->moduleHandler->moduleExists('ie9')
        )
        &&
        (
          defined('MAINTENANCE_MODE')
          ||
          !$this->configFactory->get('system.performance')->get('css.preprocess')
        )
      ) {
        $policy->appendDirective('style-src', [Csp::POLICY_UNSAFE_INLINE]);
        // style-src-elem may not be set, if it is expected to fall back to
        // style-src.
        if ($policy->hasDirective('style-src-elem')) {
          $policy->appendDirective('style-src-elem', [Csp::POLICY_UNSAFE_INLINE]);
        }
      }

      $reportingPluginId = $cspConfig->get($policyType . '.reporting.plugin');
      if ($reportingPluginId) {
        $reportingOptions = $cspConfig->get($policyType . '.reporting.options') ?: [];
        $reportingOptions += [
          'type' => $policyType,
        ];
        try {
          $this->reportingHandlerPluginManager
            ->createInstance($reportingPluginId, $reportingOptions)
            ->alterPolicy($policy);
        }
        catch (PluginException $e) {
          watchdog_exception('csp', $e);
        }
      }

      $response->headers->set($policy->getHeaderName(), $policy->getHeaderValue());
    }
  }

}
