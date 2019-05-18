<?php

namespace Drupal\sa11y\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\sa11y\Sa11yInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Sa11yNodeController.
 */
class Sa11yNodeController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Sa11y service.
   *
   * @var \Drupal\sa11y\Sa11y
   */
  protected $sa11y;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('sa11y.service')
    );
  }

  /**
   * Constructs a Sa11yController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\sa11y\Sa11yInterface $sa11y
   *   Sa11y Service.
   */
  public function __construct(Connection $database, Sa11yInterface $sa11y) {
    $this->database = $database;
    $this->sa11y = $sa11y;
  }

  /**
   * Ajax callback to check the status of a node report.
   *
   * @param int $report_id
   *   The report to get data from.
   * @param int $nid
   *   The node to check against.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response with commands.
   */
  public function status($report_id = NULL, $nid = NULL) {
    $response = new AjaxResponse();

    if ($report = $this->sa11y->getReport($report_id)) {

      // Error.
      if ($report->status == Sa11yInterface::ERROR || $report->status == Sa11yInterface::TIMEOUT) {
        // Updates the status.
        $response->addCommand(new SettingsCommand(['sa11y' => ['status' => 'error']], TRUE));
      }

      // Wait longer in JS.
      if ($report->status == Sa11yInterface::CREATED || $report->status == Sa11yInterface::RUNNING) {
        // Add timer.
        $response->addCommand(new SettingsCommand(['sa11y' => ['timer' => TRUE]], TRUE));
      }

      // Error/timeout should've already been caught.
      if ($report->status == Sa11yInterface::COMPLETE) {

        /** @var \Drupal\node\NodeInterface $node */
        $node = Node::load($nid);

        // If this is the homepage, we need a lil magic here.
        $config = \Drupal::config('system.site');
        $front_uri = $config->get('page.front');
        if ('/node/' . $node->id() == $front_uri) {
          $node_url = \Drupal::request()->getSchemeAndHttpHost() . '/';
        }
        else {
          $node_url = $node->toUrl()->setAbsolute()->toString();
        }

        // Only pass paths for sitemaps.
        $url = $report->type != 'single' ? $node_url : NULL;
        $violations = $this->sa11y->getViolations($report_id, $url);

        $status = 'complete';
        // Check if node has changed since this report was run.
        if ($node && $node->getChangedTime() > $report->timestamp) {
          $status = 'outdated';
        }

        $js_violations = [];
        foreach ($violations as $violation) {
          $message = [
            '#theme' => 'sa11y_tip',
            '#violation' => $violation,
            '#reportId' => $report_id,
          ];

          $js_violations[] = [
            'dom' => $violation->dom,
            'impact' => $violation->impact,
            'message' => render($message),
          ];
        }

        // Add violations to drupalSettings.
        $response->addCommand(new SettingsCommand([
          'sa11y' => [
            'violations' => $js_violations,
            'status' => empty($js_violations) ? 'clean' : $status,
            'reportLink' => ltrim(Url::fromRoute('sa11y.report', ['report_id' => $report_id])->toString(), '/'),
          ],
        ], TRUE));
      }

      // Reset the Sa11y tab.
      $response->addCommand(new ReplaceCommand(
        '#sa11y-node-tab',
        Link::createFromRoute($this->t('Sa11y'), 'sa11y.node', ['node' => $nid], ['attributes' => ['id' => 'sa11y-node-tab']])
          ->toString()
      ));

    }

    return $response;
  }

}
