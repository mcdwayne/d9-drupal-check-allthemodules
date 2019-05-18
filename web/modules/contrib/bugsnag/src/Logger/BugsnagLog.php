<?php

namespace Drupal\bugsnag\Logger;

use Bugsnag\Client as BugsnagClient;
use Bugsnag\Handler as BugsnagHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events to Bugsnag.
 */
class BugsnagLog implements LoggerInterface
{
    use RfcLoggerTrait;

    /**
     * A configuration object containing Bugsnag log settings.
     *
     * @var \Drupal\Core\Config\Config
     */
    protected $config;

    /**
     * The bugsnag instance.
     *
     * @var \Bugsnag\Client
     */
    protected $bugsnag;

    /**
     * The message's placeholders parser.
     *
     * @var \Drupal\Core\Logger\LogMessageParserInterface
     */
    protected $parser;

    /**
     * Constructs a BugsnagLog object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The configuration factory object.
     * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
     *   The parser to use when extracting message variables.
     */
    public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser)
    {
        $this->config = $config_factory->get('bugsnag.settings');
        $this->parser = $parser;
        $this->initializeBugsnag();
    }

    /**
     * Initialize the Bugsnag client if not initialized.
     *
     *  Workaround for KernelEvents::REQUEST not being triggered
     *  by drush.
     */
    protected function initializeBugsnag()
    {
        global $_bugsnag_client;
        $apikey = trim($this->config->get('bugsnag_apikey'));

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
        $this->bugsnag = $_bugsnag_client;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        if (empty($this->bugsnag)) {
            return;
        }

        try {
            // Get the log levels we've configured to send to bugsnag.
            $configured_levels = $this->config->get('bugsnag_logger');

            $logged_levels = [];
            if (!empty($configured_levels) && is_array($configured_levels)) {
                foreach ($configured_levels as $configured_level) {
                    $logged_levels[] = str_replace('severity-', '', $configured_level);
                }
            }

            if (in_array($level, $logged_levels)) {
                // Populate the message placeholders and replace them in the message.
                $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
                $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

                // Log the item to bugsnag.
                $this->bugsnag->notifyError($context['channel'], strip_tags($message), function ($report) use ($level) {
                    if ($level < 2) {
                        $severity = 'info';
                    } elseif ($level === 3) {
                        $severity = 'warning';
                    } else {
                        $severity = 'error';
                    }
                    $report->setSeverity($severity);
                });
            }
        } catch (\Exception $e) {
        }
    }
}
