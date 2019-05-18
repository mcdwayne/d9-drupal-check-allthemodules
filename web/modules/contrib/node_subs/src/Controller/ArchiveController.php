<?php

namespace Drupal\node_subs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node_subs\Service\NodeService;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Class ArchiveController.
 */
class ArchiveController extends ControllerBase {

  /**
   * @var \Drupal\node_subs\Service\NodeService
   */
  private $nodeService;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Datetime\DateFormatterInterface definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;
  /**
   * Drupal\Core\StringTranslation\TranslationManager definition.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * ArchiveController constructor.
   */
  public function __construct(
    NodeService $node_service,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    TranslationManager $string_translation
  ) {
    $this->nodeService = $node_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $string_translation;

  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('node_subs.nodes'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('string_translation')
    );
  }

  /**
   * @return array
   *   Return Archive table.
   */
  public function build() {
    $header = [
      'nid' => 'Nid',
      'title' => $this->t('Title'),
      'type' => $this->t('Node type'),
      'created' => $this->t('Created'),
      'sent' => $this->t('Sent')];

    $rows = [];

    $history_nodes = $this->nodeService->getHistoryNodes(NODE_SUBS_HISTORY_TABLE);
    if (!empty($history_nodes)) {
      foreach ($history_nodes as $node) {
        $node_obj = $this->entityTypeManager->getStorage('node')->load($node->nid);

        if (!$node_obj) {
          continue;
        }
        $rows[] = array(
          'nid' => $node_obj->id(),
          'title' => $node_obj->getTitle(),
          'type' => $node_obj->bundle(),
          'created' => $this->dateFormatter->format($node_obj->getCreatedTime(), 'custom', 'd.m.Y - H:i'),
          'sent' => $this->dateFormatter->format($node->send, 'custom', 'd.m.Y - H:i'),
        );
      }
    }

// Table for output
    $output['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('Archive is empty.'),
      '#attributes' => array('class' => array('node-subs-archive'))
    );

    $output['pager'] = ['#type' => 'pager'];


    return $output;
  }

}
