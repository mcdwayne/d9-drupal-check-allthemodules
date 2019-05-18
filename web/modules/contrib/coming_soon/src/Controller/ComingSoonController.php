<?php

namespace Drupal\coming_soon\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for the Example module.
 */
class ComingSoonController extends ControllerBase
{

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $files;

  /**
   * Entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Construct a formbuilder object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form builder.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entityStorage
   *   Entity Storage.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   Entity Query.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityStorageInterface $entityStorage, QueryFactory $entityQuery)
  {
    $this->formBuilder = $form_builder;
    $this->files = $entityStorage;
    $this->entityQuery = $entityQuery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.manager')->getStorage('file'),
      $container->get('entity.query')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function index()
  {
    $form = $this->formBuilder->getForm('Drupal\coming_soon\Form\ComingSoonSubscribersForm');
    $config = $this->config('coming_soon.settings');
    $bg = $config->get('coming_soon_bg');
    $bg = !empty($bg) && (is_array($bg)) ? array_shift($bg) : NULL;
    if ($bg) {
      $bg = $this->files->load($bg);
    }
    $logo = theme_get_setting('logo.url');

    return [
      '#theme' => 'coming_soon_predefined_page',
      '#config' => $config,
      '#background' => $bg,
      '#form' => $form,
      '#logo' => $logo,
    ];
  }

  /**
   * Define a batch to export subscribers.
   */
  public function export()
  {

    $url = Url::fromRoute('entity.coming_soon_subscriber.collection');
    $link = Link::fromTextAndUrl($this->t('Go back to the subscribers list.'), $url);

    // Get the count of the subscribers.
    $query = $this->entityQuery->get('coming_soon_subscriber')->count();
    $subscribers_count = $query->execute();
    // Set the progress message.
    $progress_message = $this->t('Processed @current out of @total.');
    // Prepare the batch.
    $batch = [
      'title' => $this->t('Exporting subscribers...'),
      'operations' => [
        [
          '\Drupal\coming_soon\SubscribersExporter::export',
          [$subscribers_count],
        ],
      ],
      'finished' => '\Drupal\coming_soon\SubscribersExporter->exportFinishedCallback',
      'progress_message' => $progress_message . '<br><br><strong>&#8592;</strong>' . $link->toString(),
    ];
    // Setting the batch.
    batch_set($batch);
    // Redirect to download page once the batch is processed.
    return batch_process('admin/content/subscriber/download');
  }

  /**
   * Serve a CSV file of subscribers.
   */
  public function download()
  {
    // Create a response object.
    $response = new Response();
    // Setting the headers.
    $response->headers->set('Content-type', 'application/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename=\'subscribers-list-' .date('d-m-Y-h-i-s') . '.csv\'');
    $response->headers->set('Cache-Control', 'max-age=300; must-revalidate');
    // Send the headers.
    $response->sendHeaders();
    // And serve the file.
    readfile($_SESSION['csv_download_file']);
    die();
  }

}
