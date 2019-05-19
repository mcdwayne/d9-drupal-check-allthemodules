<?php
namespace Drupal\stacksight\Logger;

use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;
use Drupal\Core\Logger\RfcLogLevel;

class StacksightLog implements LoggerInterface {
    use RfcLoggerTrait;

    private $_ss_client;

    public function __construct(){
        global $ss_client;
        $this->_ss_client = $ss_client;
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array()) {
        $exclude = array('content');
        if (empty($this->_ss_client) || empty($level) || in_array($context['channel'], $exclude)) return;

        if (!empty($message)) {
            if ($context['channel'] == 'php') {
                $message = empty($context) ? t($message) : t($message,
                    array(
                        '%type' => $context['%type'],
                        '@message' => $context['@message'],
                        '%function' => $context['%function'],
                        '%line' => $context['%line'] ,
                        '%file' => $context['%file']
                    )
                );
                $message = strip_tags($message->render());
                $severity = $this->_map_severity_level($level);
                $res = $this->_ss_client->sendLog($message, $severity);
            } else {
                // process other types...
            }
        }
        if (isset($res) && $res && !$res['success']) \SSUtilities::error_log($res['message'], 'error');
    }

    private function _map_severity_level($drupal_sev_level) {
        // suppose we have an error if the severity level is malformed
        $level = 'error';
        switch ($drupal_sev_level) {
            case RfcLogLevel::ERROR:
                $level = 'error';
                break;

            case RfcLogLevel::WARNING:
                $level = 'warn';
                break;

            case RfcLogLevel::NOTICE:
                $level = 'info';
                break;

            case RfcLogLevel::DEBUG:
                $level = 'log';
                break;
        }
        return $level;
    }
}