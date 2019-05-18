<?php

namespace Drupal\hold_my_draft\Controller;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Controller\NodeController as DefaultNodeController;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\hold_my_draft\Utilities;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NodeController.
 *
 * @package Drupal\hold_my_draft\Controller
 */
class NodeController extends DefaultNodeController {


  /**
   * The draft-hold utilities service.
   *
   * @var \Drupal\hold_my_draft\Utilities
   */
  protected $utilities;

  /**
   * NodeController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   Date formatter for parent.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer for parent.
   * @param \Drupal\hold_my_draft\Utilities $utilities
   *   Draft-hold utilities service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, Utilities $utilities) {
    parent::__construct($date_formatter, $renderer);
    $this->utilities = $utilities;
  }

  /**
   * Get services from the global services container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The services container.
   *
   * @return \Drupal\node\Controller\NodeController|\Drupal\hold_my_draft\Controller\NodeController
   *   The controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('hold_my_draft.utilities')
    );
  }

  /**
   * Generates an overview table of older revisions of a node.
   *
   * This allows an added "Clone and edit" button for the current revision.
   * By default there are no actions available on the revision screen
   * for the current revision.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A node object.
   *
   * @return array
   *   Build array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(NodeInterface $node) {

    // Get the build array from base controller method.
    $build = parent::revisionOverview($node);

    $nid = $node->id();

    // We need to prevent multiple draft-holds from happening at once.
    // We also need to check this before draft-hold-ability because when a
    // draft-hold is first kicked off, it's not in a draft-holdable state.
    $draftHold = $this->utilities->getDraftHoldInfo($node);
    if ($this->utilities->isInProgress($draftHold)) {
      // Add a message.
      $this->utilities->generateMessage($draftHold, $node);
      return $build;
    }
    // Before proceeding, we should make sure that this ought to be draft-held.
    if (!$this->utilities->isDraftHoldable($node)) {
      return $build;
    }
    $vid = $this->utilities->getDefaultRevisionId($node);

    // Make sure the build array is what we expect.
    if ($build['node_revisions_table'] && $build['node_revisions_table']['#rows']) {
      $rows = $build['node_revisions_table']['#rows'];

      foreach ($rows as $row) {
        // In the parent controller, only the current revision gets a class.
        if (isset($row['class'])) {
          if (in_array('revision-current', $row['class'])) {
            $row['data'][1]['data']['#links'] = ['hotfix' => $this->addDraftHoldButton($nid, $vid)];
            $row['data'][1]['data']['#type'] = 'operations';
          }
        }
        $newrows[] = $row;
      }
      if (isset($newrows)) {
        $build['node_revisions_table']['#rows'] = $newrows;
      }
    }

    return $build;
  }

  /**
   * Create a draft-hold button link.
   *
   * @param int $nid
   *   The node id of the current page.
   * @param int $vid
   *   The revision id to be cloned and edited.
   *
   * @return array
   *   A link to be added to the render array.
   */
  protected function addDraftHoldButton(int $nid, int $vid) {
    $draftHold = [
      'title' => $this->t('Clone and edit'),
      'url' => Url::fromRoute('hold_my_draft.draft_hold_start', ['node' => $nid, 'node_revision' => $vid]),
    ];

    return $draftHold;
  }

}
