<?php

namespace Drupal\lti_tool_provider\Controller;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for lti_tool_provider module routes.
 */
class LTIToolProviderController extends ControllerBase
{

    /**
     * The configuration factory.
     *
     * @var ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * A logger instance.
     *
     * @var LoggerChannelFactory
     */
    protected $loggerFactory;

    /**
     * The module handler.
     *
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * The private temp store for storing LTI context info.
     *
     * @var PrivateTempStore
     */
    protected $tempStore;

    /**
     * Constructs a HTTP basic authentication provider object.
     *
     * @param ConfigFactoryInterface $config_factory
     *   The configuration factory.
     * @param LoggerChannelFactory $logger_factory
     *   A logger instance.
     * @param ModuleHandlerInterface $module_handler
     *   The module handler.
     * @param PrivateTempStoreFactory $temp_store_factory
     *   The temp store factory.
     */
    public function __construct(
        ConfigFactoryInterface $config_factory,
        LoggerChannelFactory $logger_factory,
        ModuleHandlerInterface $module_handler,
        PrivateTempStoreFactory $temp_store_factory = null
    ) {
        $this->configFactory = $config_factory;
        $this->loggerFactory = $logger_factory->get('lti_tool_provider');
        $this->moduleHandler = $module_handler;
        $this->tempStore = $temp_store_factory->get('lti_tool_provider');
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        /* @var $config_factory ConfigFactoryInterface */
        $config_factory = $container->get('config.factory');
        /* @var $logger_factory LoggerChannelFactory */
        $logger_factory = $container->get('logger.factory');
        /* @var $module_handler ModuleHandlerInterface */
        $module_handler = $container->get('module_handler');
        /* @var $temp_store_factory  PrivateTempStoreFactory */
        $temp_store_factory = $container->get('tempstore.private');
        return new static(
            $config_factory,
            $logger_factory,
            $module_handler,
            $temp_store_factory
        );
    }

    /**
     * LTI launch.
     *
     * Authenticates the user via the authentication.lti_tool_provider service,
     * logins that user, and then redirects the user to the appropriate page.
     *
     * @return RedirectResponse
     *   Redirect user to appropriate LTI url.
     *
     * @see \Drupal\lti_tool_provider\Authentication\Provider\LTIToolProvider
     *   This controller requires that the authentication.lti_tool_provider
     *   service is attached to this route in lti_tool_provider.routing.yml.
     */
    public function launch()
    {
        $destination = '/';

        try {
            $context = $this->tempStore->get('context');
        }
        catch (Exception $e) {
            $this->loggerFactory->warning($e->getMessage());
        }

        $settings = Drupal::config('lti_tool_provider.settings');
        if (!empty($settings->get('destination'))) {
            $destination = $settings->get('destination');
        }

        if (isset($context['custom_destination']) && !empty($context['custom_destination'])) {
            $destination = $context['custom_destination'];
        }

        $this->moduleHandler->alter('lti_tool_provider_launch_redirect', $destination, $context);

        return new RedirectResponse($destination);
    }

    /**
     * LTI return.
     *
     * Logs the user out and returns the user to the LMS.
     *
     * @return RedirectResponse
     *   Redirect user to appropriate return url.
     */
    public function return()
    {
        try {
            $context = $this->tempStore->get('context');

            $this->moduleHandler->invokeAll('lti_tool_provider_return', [$context]);
            user_logout();

            return new RedirectResponse($context['launch_presentation_return_url']);
        }
        catch (Exception $e) {
            $this->loggerFactory->warning($e->getMessage());
        }

        return new RedirectResponse('/');
    }

    /**
     * Checks access for LTI routes.
     *
     * @return AccessResult
     *   The access result.
     */
    public function access()
    {
        $context = $this->tempStore->get('context');

        return AccessResult::allowedIf(isset($context));
    }

}
