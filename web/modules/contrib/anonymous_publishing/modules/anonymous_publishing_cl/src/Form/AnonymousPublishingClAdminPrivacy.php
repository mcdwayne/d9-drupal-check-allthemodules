<?php

namespace Drupal\anonymous_publishing_cl\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AnonymousPublishingClAdminPrivacy extends FormBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a \Drupal\anonymous_publishing_cl\Form\AnonymousPublishingClAdminModeration object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   * @param \Drupal\Core\Datetime\DateFormatter
   *   The date formatter service.
   */
  public function __construct(Connection $database, DateFormatter $date_formatter, TranslationInterface $string_translation) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_publishing_cl_admin_privacy';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('anonymous_publishing_cl.settings');

    // Count the number of used aliases on file.
    $aliases = $this->getNumberOfVerifiedContents();

    $period = [
      0 => t('Delete ASAP'),
      3600 => $this->dateFormatter->formatInterval(3600),
      21600 => $this->dateFormatter->formatInterval(21600),
      43200 => $this->dateFormatter->formatInterval(43200),
      86400 => $this->dateFormatter->formatInterval(86400),
      259200 => $this->dateFormatter->formatInterval(259200),
      604800 => $this->dateFormatter->formatInterval(604800),
      2592000 => $this->dateFormatter->formatInterval(2592000),
      -1 => t('Indefinitely'),
    ];

    $form = [];
    $aliasopt = $settings->get('user_alias');
    if ($aliasopt != 'anon') {
      $disablep = TRUE;
      $warn = '<br/><strong>' . t('Note:') . '</strong> ' . t('Purging is incompatible with having an alias (main settings).  To purge, you need to turn this setting off.');
    }
    elseif (empty($aliases)) {
      $warn = '';
      $disablep = FALSE;
    }
    else {
      $warn = '<br/>' . t('You have @count linking verification e-mail to content. @these will be deleted when these links are purged.', [
          '@count' => $this->stringTranslation->formatPlural(count($aliases), '1 record', '@count records'),
          '@these' => $this->stringTranslation->formatPlural(count($aliases), 'This', 'These'),
        ]);
      $disablep = FALSE;
    }

    $form['anonymous_publishing_privacy'] = [
      '#markup' => '<p>' . t('For enhanced privacy, you can set a limited retention period for identifying information, or purge this information instantly or periodically.') . ' ' . $warn . '</p>'
    ];

    $form['apperiod'] = [
      '#type' => 'fieldset',
      '#title' => t('Retention period'),
      '#collapsible' => FALSE,
    ];
    $form['apperiod']['retain_period'] = [
      '#type' => 'select',
      '#title' => t('Maximum period to retain records that links verification e-mails, ip-addresses and generated aliases to <em>specific</em> contents:'),
      '#default_value' => $settings->get('retain_period'),
      '#options' => $period,
      '#description' => t('Select &#8220;Indefinitely&#8221; to make the records linking verification e-mail to content persistent.  This is the <em>only</em> setting compatible with a persistent alias as byline.'),
    ];
    $form['apperiod']['submit'] = [
      '#type' => 'submit',
      '#disabled' => $disablep,
      '#value' => t('Save settings'),
      '#name' => 'save'
    ];

    $form['appurge'] = [
      '#type' => 'fieldset',
      '#title' => t('Purge'),
      '#collapsible' => FALSE,
    ];
    $form['appurge']['info'] = [
      '#markup' => t('<p>Press button below to immediately purge all information linking e-mails, ip-addresses and generated aliases to anonymously published content.  This operation can not be reversed.</p>')
    ];
    $form['appurge']['submit'] = [
      '#type' => 'submit',
      '#disabled' => $disablep,
      '#value' => t('Purge now'),
      '#name' => 'purge'
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'purge':
        // First delete completly all that are published.
        $this->database->delete('anonymous_publishing')
          ->condition('verified', 1)
          ->execute();

        // For the rest, delete the IP (we need e-mail for whitelist).
        $this->database->update('anonymous_publishing')
          ->fields(array('ip' => ''))
          ->execute();

        drupal_set_message(t('All information linking identifiers to published content have been purged.'));
        break;

      case 'save':
        \Drupal::configFactory()
          ->getEditable('anonymous_publishing_cl.settings')
          ->set('retain_period', $form_state->getValue(['retain_period']))
          ->save();
        drupal_set_message(t('Rentention period updated.'));
        break;

      default:
        drupal_set_message(t('Unknown operation.'), 'error');
        break;
    }
  }

  /**
   * Get all verified content.
   *
   * @param int $test_id
   *   The test_id to retrieve results of.
   *
   * @return array
   *  Array of results grouped by test_class.
   */
  protected function getNumberOfVerifiedContents() {
    $query = $this->database->select('anonymous_publishing', 'a');
    $query->fields('a');
    $query->where('a.verified > 0');
    $result = $query->execute()->fetchAssoc();
    return $result;
  }
}