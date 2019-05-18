<?php

namespace Drupal\raven\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;

/**
 * Implements a Raven Config form.
 */
class RavenConfigForm {

  /**
   * Builds Raven config form.
   */
  public static function buildForm(array &$form) {
    $config = \Drupal::config('raven.settings');
    $form['raven'] = [
      '#type'           => 'details',
      '#title'          => t('Sentry'),
      '#tree'           => TRUE,
      '#open'           => TRUE,
    ];
    $form['raven']['js'] = [
      '#type'           => 'details',
      '#title'          => t('JavaScript'),
      '#open'           => TRUE,
    ];
    $form['raven']['js']['javascript_error_handler'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable JavaScript error handler'),
      '#description'    => t('Check to capture JavaScript errors (if user has the <a target="_blank" href=":url">send JavaScript errors to Sentry</a> permission).', [
        ':url' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-raven'])->toString(),
      ]),
      '#default_value'  => $config->get('javascript_error_handler'),
    ];
    $form['raven']['js']['public_dsn'] = [
      '#type'           => 'textfield',
      '#title'          => t('Sentry public DSN'),
      '#default_value'  => $config->get('public_dsn'),
      '#description'    => t('Sentry public client key for current site. This setting can be overridden with the SENTRY_DSN environment variable.'),
    ];
    $form['raven']['js']['polyfill_promise'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Load Polyfill'),
      '#description'    => t('Capturing JavaScript errors on IE &lt;= 11 requires the Polyfill library. Enable to load Polyfill from <a href="https://cdn.polyfill.io" rel="noreferrer" target="_blank">https://cdn.polyfill.io</a>.'),
      '#default_value'  => $config->get('polyfill_promise'),
    ];
    $form['raven']['php'] = [
      '#type'           => 'details',
      '#title'          => t('PHP'),
      '#open'           => TRUE,
    ];
    $form['raven']['php']['client_key'] = [
      '#type'           => 'textfield',
      '#title'          => t('Sentry DSN'),
      '#default_value'  => $config->get('client_key'),
      '#description'    => t('Sentry client key for current site. This setting can be overridden with the SENTRY_DSN environment variable.'),
    ];
    // "0" is not a valid checkbox option.
    foreach (RfcLogLevel::getLevels() as $key => $value) {
      $log_levels[$key + 1] = $value;
    }
    $form['raven']['php']['log_levels'] = [
      '#type'           => 'checkboxes',
      '#title'          => t('Log levels'),
      '#default_value'  => $config->get('log_levels'),
      '#description'    => t('Check the log levels that should be captured by Sentry.'),
      '#options'        => $log_levels,
    ];
    $form['raven']['php']['ignored_channels'] = [
      '#type'           => 'textarea',
      '#title'          => t('Ignored channels'),
      '#description'    => t('A list of log channels for which messages will not be sent to Sentry (one channel per line). Commonly-configured log channels include <em>access denied</em> for 403 errors and <em>page not found</em> for 404 errors.'),
      '#default_value'  => implode("\n", $config->get('ignored_channels') ?: []),
    ];
    $form['raven']['php']['fatal_error_handler'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable fatal error handler'),
      '#description'    => t('Check to capture fatal PHP errors.'),
      '#default_value'  => $config->get('fatal_error_handler'),
    ];
    $form['raven']['php']['fatal_error_handler_memory'] = [
      '#type'           => 'number',
      '#title'          => t('Reserved memory'),
      '#description'    => t('Reserved memory for fatal error handler (KB).'),
      '#default_value'  => $config->get('fatal_error_handler_memory'),
      '#size'           => 10,
      '#min'            => 0,
    ];
    $form['raven']['php']['message_limit'] = [
      '#type'           => 'number',
      '#title'          => t('Message limit'),
      '#default_value'  => $config->get('message_limit'),
      '#description'    => t('Log message maximum length in characters.'),
      '#size'           => 10,
      '#min'            => 0,
      '#step'           => 1,
      '#required'       => TRUE,
    ];
    $form['raven']['php']['stack'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Enable stacktraces'),
      '#default_value'  => $config->get('stack'),
      '#description'    => t('Check to add stacktraces to reports.'),
    ];
    $form['raven']['php']['trace'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Reflection tracing in stacktraces'),
      '#default_value'  => $config->get('trace'),
      '#description'    => t('Check to enable reflection tracing (function calling arguments) in stacktraces. Warning: This setting allows sensitive data to be logged by Sentry!'),
    ];
    $form['raven']['php']['timeout'] = [
      '#type'           => 'number',
      '#title'          => t('Timeout'),
      '#default_value'  => $config->get('timeout'),
      '#description'    => t('Connection timeout in seconds.'),
      '#size'           => 10,
      '#min'            => 0,
    ];
    $form['raven']['php']['ssl'] = [
      '#type'           => 'radios',
      '#title'          => t('SSL Verification'),
      '#default_value'  => $config->get('ssl'),
      '#options'        => [
        'verify_ssl'    => t('Verify SSL'),
        'ca_cert'       => t('Verify against a CA certificate'),
        'no_verify_ssl' => t("Don't verify SSL (not recommended)"),
      ],
    ];
    $form['raven']['php']['ca_cert'] = [
      '#type'           => 'textfield',
      '#title'          => t('Path to CA certificate'),
      '#default_value'  => $config->get('ca_cert'),
      '#description'    => t('Path to the CA certificate file of the Sentry server specified in the DSN.'),
      // Only visible when 'ssl' set to ca_cert.
      '#states'         => [
        'visible'       => [
          ':input[name="raven[php][ssl]"]' => ['value' => 'ca_cert'],
        ],
      ],
    ];
    $form['raven']['environment'] = [
      '#type'           => 'textfield',
      '#title'          => t('Environment'),
      '#default_value'  => $config->get('environment'),
      '#description'    => t('The environment in which this site is running (leave blank to use kernel.environment parameter). This setting can be overridden with the SENTRY_ENVIRONMENT environment variable.'),
    ];
    $form['raven']['release'] = [
      '#type'           => 'textfield',
      '#title'          => t('Release'),
      '#default_value'  => $config->get('release'),
      '#description'    => t('The release this site is running (could be a version or commit hash). This setting can be overridden with the SENTRY_RELEASE environment variable.'),
    ];
    $form['#submit'][] = 'Drupal\raven\Form\RavenConfigForm::submitForm';
  }

  /**
   * Submits Raven config form.
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('raven.settings')
      ->set('client_key',
        $form_state->getValue(['raven', 'php', 'client_key']))
      ->set('fatal_error_handler',
        $form_state->getValue(['raven', 'php', 'fatal_error_handler']))
      ->set('fatal_error_handler_memory',
        $form_state->getValue(['raven', 'php', 'fatal_error_handler_memory']))
      ->set('log_levels',
        $form_state->getValue(['raven', 'php', 'log_levels']))
      ->set('stack',
        $form_state->getValue(['raven', 'php', 'stack']))
      ->set('timeout',
        $form_state->getValue(['raven', 'php', 'timeout']))
      ->set('message_limit',
        $form_state->getValue(['raven', 'php', 'message_limit']))
      ->set('trace',
        $form_state->getValue(['raven', 'php', 'trace']))
      ->set('ssl',
        $form_state->getValue(['raven', 'php', 'ssl']))
      ->set('ca_cert',
        $form_state->getValue(['raven', 'php', 'ca_cert']))
      ->set('ignored_channels', array_map('trim', preg_split('/\R/',
        $form_state->getValue(['raven', 'php', 'ignored_channels']), -1, PREG_SPLIT_NO_EMPTY)))
      ->set('javascript_error_handler',
        $form_state->getValue(['raven', 'js', 'javascript_error_handler']))
      ->set('public_dsn',
        $form_state->getValue(['raven', 'js', 'public_dsn']))
      ->set('polyfill_promise',
        $form_state->getValue(['raven', 'js', 'polyfill_promise']))
      ->set('environment',
        $form_state->getValue(['raven', 'environment']))
      ->set('release',
        $form_state->getValue(['raven', 'release']))
      ->save();
  }

}
