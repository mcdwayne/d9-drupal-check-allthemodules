<?php
/**
 * @file
 * Contains \Drupal\collect\Controller\CollectContainerController.
 */

namespace Drupal\collect\Controller;

use Drupal\collect\CaptureEntity;
use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\Entity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Collect Container Controller.
 */
class CollectContainerController extends ControllerBase {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The entity capture service.
   *
   * @var \Drupal\Collect\CaptureEntity
   */
  protected $entityCapturer;

  /**
   * Constructs a CollectContainerController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Collect\CaptureEntity $entity_capturer
   *   The entity capture service.
   */
  public function __construct(DateFormatter $date_formatter, CaptureEntity $entity_capturer) {
    $this->dateFormatter = $date_formatter;
    $this->entityCapturer = $entity_capturer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('collect.capture_entity')
    );
  }

  /**
   * Generates an overview table of revisions of a collect container item.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   A collect container object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(CollectContainerInterface $collect_container) {
    $build = [];
    $build['#title'] = $this->t('Revisions for %title', array('%title' => $collect_container->label()));
    $header = [$this->t('Revision Id'), $this->t('Created'), $this->t('Data'), $this->t('Status')];

    $rows = [];
    $collect_container_storage = $this->entityManager()->getStorage('collect_container');
    $vids = $collect_container_storage->revisionIds($collect_container);

    foreach (array_reverse($vids) as $vid) {
      if ($revision = $collect_container_storage->loadRevision($vid)) {
        $row = [];

        if ($vid == $collect_container->getRevisionId()) {
          $row[] = [
            'data' => $revision->getRevisionId(),
            'class' => ['revision-current'],
          ];
          $row[] = [
            'data' => $this->dateFormatter->format($revision->date->value, 'short'),
            'class' => ['revision-current'],
          ];
          $row[] = [
            'data' => (($revision->data->data != '') ? substr(Xss::filter($revision->data->data), 0, 400) : ''),
            'class' => ['revision-current'],
          ];
          $row[] = [
            'data' => SafeMarkup::format('%placeholder', ['%placeholder' => $this->t('Current revision')]),
            'class' => ['revision-current'],
          ];
        }
        else {
          $row[] = [
            'data' => $revision->getRevisionId(),
          ];
          $row[] = [
            'data' => $this->dateFormatter->format($revision->date->value, 'short'),
          ];
          $row[] = [
            'data' => (($revision->data->data != '') ? substr(Xss::filter($revision->data->data), 0, 400) : ''),
          ];
          $row[] = [
            'data' => '',
          ];
        }
        $rows[] = $row;
      }
    }

    $build['collect_revisions_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'library' => array('node/drupal.node.admin'),
      ),
    );

    return $build;
  }

  /**
   * Loads an entity and delegates it to CaptureEntity for capturing.
   *
   * @param string $entity_type
   *   The entity type of the given entity.
   * @param string $entity_id
   *   The ID of the given entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A response redirecting to the entity page.
   */
  public function capture($entity_type, $entity_id) {
    $entity = entity_load($entity_type, $entity_id);
    $collect_container = $this->entityCapturer->capture($entity);
    drupal_set_message($this->t('The @entity_type %label has been captured as a new container. You can access it <a href="@container_url">here</a>.', [
      '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
      '@container_url' => $collect_container->url(),
    ]));
    $url = $entity->urlInfo();
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

  /**
   * Displays raw data of the given collect container.
   *
   * @param \Drupal\collect\CollectContainerInterface $collect_container
   *   The collect container which raw data is displayed.
   *
   * @return array
   *   A rendered array.
   */
  public function rawDataDisplay(CollectContainerInterface $collect_container) {
    $build = [];
    $build['#title'] = $this->t('Raw %title data', array('%title' => $collect_container->label()));
    $build['raw_data'] = [
      '#type' => 'item',
      'data' => $this->formatRawData($collect_container->getData(), $collect_container->getType()),
    ];
    return $build;
  }

  /**
   * Format raw container data according to common MIME types.
   *
   * @param mixed $data
   *   Container data.
   * @param string $type
   *   The MIME Type of container data.
   *
   * @return array
   *   Renderable array representing the formatted data.
   */
  protected function formatRawData($data, $type) {
    // Default MIME type is application/octet-stream.
    $type = strpos($type, '/') ? $type : 'application/octet-stream';

    // "Media type" and "subtype" are the terms used in the MIME standards (RFC
    // 2046).
    list($mediatype, $subtype) = explode('/', $type, 2);

    if ($type == 'application/json') {
      // Pretty-print JSON.
      $data = Json::decode($data);
      if (json_last_error()) {
        // If decoding fails, output raw.
        $string = SafeMarkup::checkPlain($data);
        return array('#markup' => $this->t('Decoding error: %message', ['%message' => json_last_error_msg()]) . " <pre>$string</pre>");
      }
      $string = Json::encodePretty($data);
      $string = SafeMarkup::checkPlain($string);
      return array('#markup' => "<pre>$string</pre>");
    }
    elseif ($mediatype == 'text') {
      if ($subtype == 'html') {
        // Filter HTML.
        $html = Xss::filter($data);
        return array('#markup' => $html);
      }
      else {
        // Escape any HTML.
        $text = SafeMarkup::checkPlain($data);
        return array('#markup' => "<pre>$text</pre>");
      }
    }

    // Don't display data of unknown type.
    return array('#markup' => $this->t('Unknown schema and MIME type.'));
  }

}
