<?php /**
 * @file
 * Contains \Drupal\tmgmt_xconnect\Controller\AdminController.
 */

namespace Drupal\tmgmt_xconnect\Controller;

use Amplexor\XConnect\Response;
use Amplexor\XConnect\Response\ZipFile;
use Amplexor\XConnect\Service\FtpService;
use Amplexor\XConnect\Service\ServiceAbstract;
use Amplexor\XConnect\Service\SFtpService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt_xconnect\Exception\ImportException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin controller for the tmgmt_xconnect module.
 */
class AdminController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(TranslationInterface $string_translation, LinkGeneratorInterface $link_generator, RendererInterface $renderer) {
    $this->stringTranslation = $string_translation;
    $this->linkGenerator = $link_generator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('link_generator'),
      $container->get('renderer')
    );
  }

  /**
   * Show an overview of X-Connect translators.
   *
   * @return array
   *   A render array.
   */
  public function overview() {
    $render_array = [
      'translators' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'xconnect-translators-overview'
          ]
        ],
      ],
    ];

    // Get all translators.
    $translators = $this->entityTypeManager()->getStorage('tmgmt_translator')->loadByProperties(['plugin' => 'xconnect']);
    if ($translators) {
      // List translators in a table.
      $header = [
        t('Label'),
        [
          'data' => t('Operations'),
          'colspan' => 3,
        ],
      ];
      $rows = [];

      foreach ($translators as $translator) {
        $label = [
          '#type' => 'inline_template',
          '#template' => '{{ context_title }}<br><small><span lang="en">{{ context }}</span></small>',
          '#context' => [
            'context_title' => $translator->link(),
            'context' => $translator->getDescription(),
          ],
        ];
        $rows[] = [
          $this->renderer->renderPlain($label),
          $this->l(t('Send'), Url::fromRoute('tmgmt_xconnect.admin_actions_request', ['translator' => $translator->id()])),
          $this->l(t('Scan'), Url::fromRoute('tmgmt_xconnect.admin_actions_scan', ['translator' => $translator->id()])),
          $this->l(t('Receive'), Url::fromRoute('tmgmt_xconnect.admin_actions_receive', ['translator' => $translator->id()])),
        ];
      }

      $footer = [
        [
          'data' => [
            [
              'data' => [
                '#theme' => 'item_list',
                '#items' => [
                  $this->t('<strong>Send</strong> all unprocessed translation jobs.'),
                  $this->t('<strong>Scan</strong> the remote service and report about the number of processed translation jobs that are ready to be received.'),
                  $this->t('<strong>Receive</strong> any processed translation jobs and import them.'),
                ],
              ],
              'colspan' => 4,
            ],
          ],
        ]
      ];

      $render_array['translators']['table'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#footer' => $footer,
      ];
    }
    else {
      // No translators found.
      drupal_set_message($this->t('There are no X-Connect translators available (%add-translator).', [
        '%add-translator' => $this->l(t('add a translator here'), Url::fromRoute('entity.tmgmt_translator.add_form'))
      ]), 'warning');
    }

    return $render_array;
  }

  /**
   * Initiate and run a request batch.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator service.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response to the overview page.
   */
  public function request(TranslatorInterface $translator) {
    // Get all the open jobs for the given translator.
    $jobs = $jobs = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->loadByProperties([
      'translator' => $translator->id(),
      'state' => 0,
    ]);

    if (!$jobs) {
      // No jobs found.
      drupal_set_message(
        t('There are no translation jobs available to send to the %service service.', ['%service' => $translator->label()]),
        'warning'
      );
      // Redirect to the overview page.
      return $this->redirect('tmgmt_xconnect.admin_actions');
    }

    // Create the operations.
    $operations = array();
    foreach ($jobs as $job) {
      $operations[] = array('\Drupal\tmgmt_xconnect\Controller\AdminController::batch_request_job', array($job));
    }

    $batch = array(
      'operations' => $operations,
      'finished' => '\Drupal\tmgmt_xconnect\Controller\AdminController::batch_request_finished',
      'title' => t('Sending translation jobs to the %service service', ['%service' => $translator->label()]),
      'init_message' => t('Starting sending jobs.'),
      'progress_message' => t('Sending @current out of @total.'),
      'error_message' => t('Sending translation jobs has encountered an error.'),
    );

    batch_set($batch);
    return batch_process($this->url('tmgmt_xconnect.admin_actions'));
  }

  /**
   * Scan for new translations.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator service.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the overview page.
   */
  public function scan(TranslatorInterface $translator) {
    // Get the list of ZIP packages that are ready.
    $service = $this->getService($translator);
    $files = $service->scan();
    $count = count($files);

    if ($count) {
      drupal_set_message(
        $this->formatPlural(
          $count,
          'There is one processed translation job ready to be picked up for the %service service.',
          'There are @count processed translation jobs ready to be picked up for the %service service.',
          ['%service' => $translator->label()]
        )
      );
    }
    else {
      drupal_set_message(
        $this->t(
          'There are no processed translation jobs ready to be picked up for the %service service.',
          ['%service' => $translator->label()]
        ),
        'warning'
      );
    }

    // Redirect to the overview page.
    return $this->redirect('tmgmt_xconnect.admin_actions');
  }

  /**
   * Initiate and run a receive batch based on a the given Translator.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator service.
   *
   * @return array
   *   A render array.
   */
  public function receive(TranslatorInterface $translator) {
    // Get the files ready for translation.
    $service = $this->getService($translator);
    $files = $service->scan();

    // Check if there are files to be imported.
    if (!$files) {
      drupal_set_message(
        $this->t(
          'There are no processed translation jobs ready to be picked up for the %service service.',
          ['%service' => $translator->label()]
        ),
        'warning'
      );
      // Redirect to the overview page.
      return $this->redirect('tmgmt_xconnect.admin_actions');
    }

    // Create the batch operations, one batch per file to avoid time-out issues.
    $operations = array();
    foreach ($files as $file) {
      $operations[] = array(
        '\Drupal\tmgmt_xconnect\Controller\AdminController::batch_receive_file',
        [$translator, $file],
      );
    }

    // Setup & process the batch.
    $batch = array(
      'operations' => $operations,
      'finished' => '\Drupal\tmgmt_xconnect\Controller\AdminController::batch_receive_finished',
      'title' => t('Processing translated jobs'),
      'init_message' => t('Starting processing translated files.'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('Translated files processing has encountered an error.'),
      'file' => drupal_get_path('module', 'tmgmt_xconnect') . '/includes/batch_receive.inc',
    );

    batch_set($batch);
    return batch_process($this->url('tmgmt_xconnect.admin_actions'));
  }

  /**
   * Batch process a single translation Job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The Job to send the request for.
   * @param array $context
   *   The Batch context.
   */
  public static function batch_request_job(JobInterface $job, &$context) {
    // Init the message array if not initiated before.
    if (empty($context['results'])) {
      $context['results'] = array(
        'success' => array(),
        'error' => array(),
      );
    }

    // Send out the translation request.
    $job->requestTranslation();

    // Log result.
    if ($job->isState(1)) {
      $context['results']['success'][] = $job->link();
    }
    else {
      $context['results']['error'][] = $job->link();
    }

    $context['finished'] = 1;
  }

  /**
   * Request Batch 'finished' callback.
   *
   * @param bool $success
   *   Was the batch a success.
   * @param array $results
   *   Array containing lists of successful and failed translation requests.
   * @param array $operations
   *   Array containing all operations that (should) have run.
   */
  public static function batch_request_finished($success, $results, $operations) {
    // An error occurred.
    if (!$success) {
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t(
        'An error occurred while processing %error_operation with arguments: @arguments',
        array(
          '%error_operation' => $error_operation[0],
          '@arguments' => print_r($error_operation[1], TRUE),
        )
      );
      drupal_set_message($message, 'error');
      return;
    }

    // Show info about the successful job requests.
    if (count($results['success'])) {
      $message = [
        'intro' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}Successfully sent {{ count }} translation jobs:{% endtrans %}',
          '#context' => [
            'count' => count($results['success']),
          ],
        ],
        'item_list' => [
          '#theme' => 'item_list',
          '#items' => $results['success'],
        ],
      ];
      drupal_set_message($message, 'status');
    }

    // Show info about the failed job requests.
    if (count($results['error'])) {
      $message = [
        'intro' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}Could not send {{ count }} translation jobs:{% endtrans %}',
          '#context' => [
            'count' => count($results['error']),
          ],
        ],
        'item_list' => [
          '#theme' => 'item_list',
          '#items' => $results['error'],
        ],
      ];
      drupal_set_message($message, 'error');
    }
  }


  /**
   * Batch process a single translated file.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator service to perform the action for.
   * @param string $filename
   *   The file that is ready to be processed.
   * @param array $context
   *   The Batch context.
   */
  public static function batch_receive_file(TranslatorInterface $translator, $filename, &$context) {
    // Init the message array if not initiated before.
    if (empty($context['results'])) {
      $context['results'] = array(
        'success' => array(),
        'error' => array(),
      );
    }

    try {
      // Process the file.
      tmgmt_xconnect_import_remote_file($translator, $filename);

      $context['results']['success'][] = $filename;
    }
    catch (ImportException $e) {
      $context['results']['error'][] = t('Could not process file %filename : %message', [
        '%filename' => $filename,
        '%message' => $e->getMessage(),
      ]);
    }

    $context['finished'] = 1;
  }

  /**
   * Receive Batch 'finished' callback.
   *
   * @param bool $success
   *   Was the batch a success.
   * @param array $results
   *   Array containing lists of successfully and failed processed translations.
   * @param array $operations
   *   Array containing all operations that (should) have run.
   */
  public static function batch_receive_finished($success, $results, $operations) {
    // An error occurred.
    if (!$success) {
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t(
        'An error occurred while processing %error_operation with arguments: @arguments',
        array(
          '%error_operation' => $error_operation[0],
          '@arguments' => print_r($error_operation[1], TRUE),
        )
      );
      drupal_set_message($message, 'error');
      return;
    }

    // Show info about the successful job imports.
    if (count($results['success'])) {
      $message = [
        'intro' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}Successfully processed {{ count }} translation jobs:{% endtrans %}',
          '#context' => [
            'count' => count($results['success']),
          ],
        ],
        'item_list' => [
          '#theme' => 'item_list',
          '#items' => $results['success'],
        ],
      ];
      drupal_set_message($message, 'status');
    }

    // Show info about the failed job imports.
    if (count($results['error'])) {
      $message = [
        'intro' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}Could not process {{ count }} translation jobs:{% endtrans %}',
          '#context' => [
            'count' => count($results['error']),
          ],
        ],
        'item_list' => [
          '#theme' => 'item_list',
          '#items' => $results['error'],
        ],
      ];
      drupal_set_message($message, 'error');
    }
  }

  /**
   * Get the FTP service for a given translator plugin.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator service.
   *
   * @return ServiceAbstract
   *   An FTP service.
   */
  private function getService(TranslatorInterface $translator) {
    // Get the translator connection settings.
    $config = $translator->getSetting('connection');

    if ($config['protocol'] === 'SFTP') {
      // Transport over SSH (encryption).
      return new SFtpService($config);
    }
    else {
      // Transport over FTP (no encryption).
      return new FtpService($config);
    }
  }

}
