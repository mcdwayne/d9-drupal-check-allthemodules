<?php

namespace Drupal\bugsnag\EventSubscriber;

use Bugsnag\Client as BugsnagClient;
use Bugsnag\Handler as BugsnagHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class BootSubscriber implements EventSubscriberInterface
{

/**
 * A configuration object containing Bugsnag log settings.
 *
 * @var \Drupal\Core\Config\Config
 */
    protected $config;

    /**
     * Constructs a BootSubscriber object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The configuration factory object.
     */
    public function __construct(ConfigFactoryInterface $config_factory)
    {
        $this->config = $config_factory->get('bugsnag.settings');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => ['onEvent', 255]];
    }

    /**
     * Callback for KernelEvents::REQUEST.
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     *   The event object.
     */
    public function onEvent(GetResponseEvent $event)
    {
        $apikey = trim($this->config->get('bugsnag_apikey'));
        global $_bugsnag_client;
        if (!empty($apikey) && empty($_bugsnag_client)) {
            $user = \Drupal::currentUser();

            $_bugsnag_client = BugsnagClient::make($apikey);

            if (!empty($_SERVER['HTTP_HOST'])) {
                $_bugsnag_client->setHostname($_SERVER['HTTP_HOST']);
            }

            $release_stage = $this->config->get('release_stage');
            if (empty($release_stage)) {
                $release_stage = 'development';
            }
            $_bugsnag_client->setReleaseStage($release_stage);

            if ($user->id()) {
                $_bugsnag_client->registerCallback(function ($report) use ($user) {
                    $report->setUser([
                        'id' => $user->id(),
                        'name' => $user->getAccountName(),
                        'email' => $user->getEmail(),
                    ]);
                });
            }

            if ($this->config->get('bugsnag_log_exceptions')) {
                BugsnagHandler::register($_bugsnag_client);
            }
        }
    }
}
