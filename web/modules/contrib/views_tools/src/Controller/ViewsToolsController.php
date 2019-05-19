<?php

namespace Drupal\views_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Zend\Feed\PubSubHubbub\HttpResponse;
use Symfony\Component\Yaml\Yaml;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\views_tools\ViewsTools;
use Drupal\Core\Form\FormBuilder;

/**
 * Returns responses for Views Tools routes.
 */
class ViewsToolsController extends ControllerBase {

  protected $entityQuery;
  protected $renderer;
  protected $config;
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer, QueryFactory $entityQuery, FormBuilder $formBuilder) {
    $this->renderer = $renderer;
    $this->entityQuery = $entityQuery->get('view');
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer'), $container->get('entity.query'), $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewsList() {
    $viewsIds = $this->entityQuery->execute();
    $views = View::loadMultiple($viewsIds);
    foreach ($views as $view) {
      $dropButton = [
        '#type' => 'dropbutton',
        '#links' => [
          'list' => [
            'title' => $this->t('View Displays'),
            'url' => Url::fromRoute('views_tools.view', ['view' => $view->id()]),
          ],
          'export' => [
            'title' => $this->t('Export/Backup'),
            'url' => Url::fromRoute('views_tools.export', ['view' => $view->id()]),
          ],
        ],
      ];
      $items[] = [
        Link::createFromRoute($view->label(), 'entity.view.edit_form', ['view' => $view->id()]),
        $this->renderer->render($dropButton),
      ];
    }
    $output = [
      '#type' => 'table',
      '#header' => [$this->t('View'), $this->t('Operations')],
      '#rows' => $items,
      '#sticky' => TRUE,
    ];
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function view($view) {
    $viewEntity = View::load($view);
    foreach ($viewEntity->get('display') as $display) {
      $dropButton = [
        '#type' => 'dropbutton',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('entity.view.edit_display_form', ['view' => $viewEntity->id(), 'display_id' => $display['id']]),
          ],
          'export' => [
            'title' => $this->t('Create New View'),
            'url' => Url::fromRoute('views_tools.display_export', ['view' => $viewEntity->id(), 'display_id' => $display['id']]),
          ],
          'export_yaml' => [
            'title' => $this->t('Export YAML'),
            'url' => Url::fromRoute('views_tools.display_export_yaml', ['view' => $viewEntity->id(), 'display_id' => $display['id']]),
          ],
          'delete' => [
            'title' => $this->t('Delete display'),
            'url' => Url::fromRoute('views_tools.display_delete_confirm', ['view' => $viewEntity->id(), 'display_id' => $display['id']]),
          ],
        ],
      ];

      if ($display['id'] == 'default') {
        unset($dropButton['#links']['delete']);
      }

      $displayList[$display['id']] = [
        $display['display_title'] . ' (' . $display['id'] . ')',
        $this->renderer->render($dropButton),
      ];
    }
    $form = $this->formBuilder->getForm('\Drupal\views_tools\Form\BulkOperationForm', ['View', 'Operations'], $displayList, $viewEntity);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function export($view) {
    $viewEntity = View::load($view);
    $configFileName = 'views.view.' . $viewEntity->id();
    $viewConfig = Yaml::dump($this->config($configFileName)->get());
    $fileName = "$configFileName.yml";
    $response = new HttpResponse();
    $response->setContent($viewConfig);
    $response->setHeader('Content-Type', 'text/yaml');
    $response->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    $response->send();
    exit;
  }

  /**
   * {@inheritdoc}
   */
  public function exportDisplayAsView($view, $display_id) {
    $viewEntity = View::load($view);
    $newView = ViewsTools::exportDisplaysAsView($viewEntity, $display_id);
    return $this->redirect('entity.view.edit_form', ['view' => $newView->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function exportDisplayAsYaml($view, $display_id) {
    $viewEntity = View::load($view);
    $newView = ViewsTools::exportDisplaysToYaml($viewEntity, $display_id);
    return $this->redirect('entity.view.edit_form', ['view' => $newView->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDisplay($view, $display_id) {
    $viewEntity = View::load($view);
    ViewsTools::deleteDisplay($viewEntity, $display_id);
    return $this->redirect('views_tools.view', ['view' => $viewEntity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function editDisplay($view, $display_id) {
    // @todo feature to edit a single display without affecting
    // other displays of view.
  }

}
