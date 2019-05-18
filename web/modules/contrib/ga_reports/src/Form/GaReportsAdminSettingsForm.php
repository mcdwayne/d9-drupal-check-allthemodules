<?php

namespace Drupal\ga_reports\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ga_reports\GaReports;
use Drupal\ga_reports\GaReportsApiFeed;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Google Analytics Reports API Admin Settings form override.
 */
class GaReportsAdminSettingsForm extends GaReportsApiAdminSettingsForm {

  /**
   * Date Formatter Interface.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $account = ga_reports_gafeed();
    if ($account instanceof GaReportsApiFeed && $account->isAuthenticated()) {
      $ga_reports_settings = $this->config('ga_reports.settings')->get();
      $last_time = '';
      if (!empty($ga_reports_settings['metadata_last_time'])) {
        $last_time = $ga_reports_settings['metadata_last_time'];
      }
      $collapsed = (!$last_time) ? TRUE : FALSE;
      $form['fields'] = [
        '#type' => 'details',
        '#title' => t('Import and update fields'),
        '#open' => $collapsed,
      ];
      if ($last_time) {
        $form['fields']['last_time'] = [
          '#type' => 'item',
          '#title' => t('Google Analytics fields for Views integration'),
          '#description' => t('Last import was @time.',
            [
              '@time' => $this->dateFormatter->format($last_time, 'custom', 'd F Y H:i'),
            ]),
        ];
        $form['fields']['update'] = [
          '#type' => 'submit',
          '#value' => t('Check updates'),
          '#submit' => [[GaReports::class, 'checkUpdates']],
        ];
      }
      $form['fields']['settings'] = [
        '#type' => 'submit',
        '#value' => t('Import fields'),
        '#submit' => [[GaReports::class, 'importFields']],
      ];
    }
    return $form;
  }

}
