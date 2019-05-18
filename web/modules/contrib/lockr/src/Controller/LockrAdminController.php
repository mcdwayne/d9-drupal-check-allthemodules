<?php

namespace Drupal\lockr\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBuilderInterface;

use Lockr\Exception\LockrApiException;
use Lockr\Lockr;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\lockr\SettingsFactory;
use Drupal\lockr\Form\LockrAdvancedForm;
use Drupal\lockr\Form\LockrMigrateForm;
use Drupal\lockr\Form\LockrRegisterForm;

class LockrAdminController implements ContainerInjectionInterface {

  protected $lockr;

  protected $settingsFactory;

  /**
   * Constructs a new LockrAdminForm.
   */
  public function __construct(
    Lockr $lockr,
    SettingsFactory $settings_factory,
    ConfigFactoryInterface $config_factory,
    FormBuilderInterface $form_builder,
    FileSystemInterface $file_system,
    $drupalRoot
  ) {
    $this->lockr = $lockr;
    $this->settingsFactory = $settings_factory;
    $this->configFactory = $config_factory;
    $this->formBuilder = $form_builder;
    $this->fileSystem = $file_system;
    $this->drupalRoot = $drupalRoot;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lockr.lockr'),
      $container->get('lockr.settings_factory'),
      $container->get('config.factory'),
      $container->get('form_builder'),
      $container->get('file_system'),
      $container->get('app.root')
    );
  }

  public function overview() {
    try {
      $info = $this->lockr->getInfo();
    }
    catch (LockrApiException $e) {
      if ($e->getCode() >= 500) {
        watchdog_exception('lockr', $e);
        drupal_set_message('The Lockr service has returned an error. Please try again.', 'error');
        return [];
      }
      $info = [];
    }

    $text_config = $this->configFactory->get('lockr.ui_text');

    $ra['header'] = [
      '#prefix' => '<p>',
      '#markup' => $info
        ? $text_config->get('admin_page.header.registered')
        : $text_config->get('admin_page.header.not_registered'),
      '#suffix' => '</p>',
    ];

    $ra['status'] = $this->getStatus($info);

    $partner = $this->settingsFactory->getPartner();
    if ($partner) {
      $ra['description'] = [
        '#prefix' => '<p>',
        '#markup' => $partner['description'],
        '#suffix' => '</p>',
      ];
    }
    elseif ($info && $info['env'] === 'dev') {
      $ra['migrate'] = $this->formBuilder->getForm(LockrMigrateForm::class, $info);
    }

    if (!$info) {
      $ra['register'] = $this->formBuilder->getForm(LockrRegisterForm::class);
    }

    $ra['advanced'] = $this->formBuilder->getForm(LockrAdvancedForm::class);

    return $ra;
  }

  public function getStatus(array $info) {
    require_once "{$this->drupalRoot}/core/includes/install.inc";

    $text_config = $this->configFactory->get('lockr.ui_text');

    $reqs = [];

    if ($info) {
      $reqs[] = [
        'title' => 'Certificate Valid',
        'value' => 'Yes',
        'description' => $text_config->get('admin_page.status.registered'),
        'severity' => REQUIREMENT_OK,
      ];
      $reqs[] = [
        'title' => 'Environment',
        'value' => ucfirst($info['env']),
        'severity' => REQUIREMENT_INFO,
      ];
    }
    else {
      $private_valid = $this->fileSystem->validScheme('private');
      $reqs[] = [
        'title' => 'Private Directory',
        'value' => $private_valid ? $this->fileSystem->realpath('private://') : 'Unknown',
        'description' => $private_valid
          ? $text_config->get('admin_page.status.path.exists')
          : $text_config->get('admin_page.status.path.invalid'),
        'severity' => $private_valid ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      ];
      $reqs[] = [
        'title' => 'Certificate Valid',
        'value' => 'No',
        'description' => $text_config->get('admin_page.status.not_registered'),
      ];
    }

    if ($info) {
      $reqs[] = [
        'title' => 'Connected KeyRing',
        'value' => 'Yes',
        'description' => "You are currently connected to the {$info['keyring']['label']} KeyRing.",
        'severity' => REQUIREMENT_OK,
      ];

      $has_cc = $info['keyring']['hasCreditCard'];

      if (isset($info['keyring']['trialEnd'])) {
        $trial_end = \DateTime::createFromFormat(\DateTime::RFC3339, $info['keyring']['trialEnd']);
        if ($trial_end > (new \DateTime())) {
          $reqs[] = [
            'title' => 'Trial Expiration Date',
            'value' => $trial_end->format('M jS, Y'),
            'severity' => REQUIREMENT_INFO,
          ];
        }
        elseif (!$has_cc) {
          $reqs[] = [
            'title' => 'Trial Expiration Date',
            'value' => $trial_end->format('M jS, Y'),
            'severity' => REQUIREMENT_ERROR,
          ];
        }
      }
      $reqs[] = [
        'title' => 'Credit Card on File',
        'value' => $has_cc ? 'Yes' : 'No',
        'description' => $has_cc
          ? $text_config->get('admin_page.status.cc.has')
          : $text_config->get('admin_page.status.cc.missing.required'),
        'severity' => $has_cc ? REQUIREMENT_OK : REQUIREMENT_ERROR,
      ];
    }

    lockr_preprocess_status_report($reqs);

    return ['#theme' => 'status_report', '#requirements' => $reqs];
  }

}

function lockr_preprocess_status_report(&$reqs) {
  $severities = [
    REQUIREMENT_INFO => [
      'title' => t('Info'),
      'status' => 'info',
    ],
    REQUIREMENT_OK => [
      'title' => t('OK'),
      'status' => 'ok',
    ],
    REQUIREMENT_WARNING => [
      'title' => t('Warning'),
      'status' => 'warning',
    ],
    REQUIREMENT_ERROR => [
      'title' => t('Error'),
      'status' => 'error',
    ],
  ];
  foreach ($reqs as $i => $requirement) {
    // Always use the explicit requirement severity, if defined. Otherwise,
    // default to REQUIREMENT_OK in the installer to visually confirm that
    // installation requirements are met. And default to REQUIREMENT_INFO to
    // denote neutral information without special visualization.
    if (isset($requirement['severity'])) {
      $severity = $severities[(int) $requirement['severity']];
    }
    elseif (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === 'install') {
      $severity = $severities[REQUIREMENT_OK];
    }
    else {
      $severity = $severities[REQUIREMENT_INFO];
    }
    $reqs[$i]['severity_title'] = $severity['title'];
    $reqs[$i]['severity_status'] = $severity['status'];
  }
}
