<?php

namespace Drupal\hidden_tab\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderPluginManager;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface;
use Drupal\hidden_tab\Utility;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Route controller which renders the hidden tabs.
 */
class XPageRenderController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Put in session variable to message this controller to open admin fieldset.
   *
   * The fieldset is closed by default, if an error happens in a form submit
   * we want it open. Using this flag, we can notify this controller to keep the
   * fieldset open.
   */
  const ADMIN_FS_OPEN = 'hidden_tab_on_page_admin_open';

  /**
   * To find the entity in the Uri.
   *
   * @var string
   */
  protected $currentPath;

  /**
   * Params including secret provided by user in the query.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $query;

  /**
   * To find template plugins.
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * To check access
   *
   * @var \Drupal\hidden_tab\Plugable\Access\HiddenTabAccessPluginManager
   */
  protected $renderMan;


  /**
   * Hidden Tab Page helper.
   *
   * @var \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface
   */
  protected $entityHelper;

  /**
   * XPageRenderController constructor.
   *
   * @param string $current_path
   *   See $this->currentPath.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $query
   *   See $this->query.
   * @param \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager $template_man
   *   See $this->templateMan.
   * @param \Drupal\hidden_tab\Plugable\Render\HiddenTabRenderPluginManager $render_man
   *   See $this->renderMan.
   * @param \Drupal\hidden_tab\Service\HiddenTabEntityHelperInterface $entity_helper
   *   See $this->pageHelper.
   */
  public function __construct(string $current_path,
                              ParameterBag $query,
                              HiddenTabTemplatePluginManager $template_man,
                              HiddenTabRenderPluginManager $render_man,
                              HiddenTabEntityHelperInterface $entity_helper) {
    $this->currentPath = $current_path;
    $this->query = $query;
    $this->templateMan = $template_man;
    $this->renderMan = $render_man;
    $this->entityHelper = $entity_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('request_stack')->getCurrentRequest()->getPathInfo(),
      $container->get('request_stack')->getCurrentRequest()->query,
      $container->get('plugin.manager.hidden_tab_template'),
      $container->get('plugin.manager.hidden_tab_render'),
      $container->get('hidden_tab.entity_helper')
    );
  }

  /**
   * Displays the actual page, called from Tab page.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   *   Render array of komponents to put in the regions, as configured in the
   *   page's layout.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function display(NodeInterface $node): array {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page */
    /** @var \Drupal\hidden_tab\Plugable\Render\HiddenTabRenderInterface $plugin */
    $entity = $node;

    list($type, $page) = $this->getPage();
    $this->checkAccess($node, $page, $type);

    $output['admin'] = [
      '#type' => 'details',
      '#open' => TRUE || isset($_SESSION[self::ADMIN_FS_OPEN]) ? TRUE || $_SESSION[self::ADMIN_FS_OPEN] : FALSE,
      '#title' => $this->t('Admin'),
    ];
    foreach ($this->renderMan->pluginsSorted() as $plugin) {
      if ($plugin->access($entity, $page, $this->currentUser())->isAllowed()) {
        try {
          $plugin->render($entity, $page, $this->currentUser(), $this->query, $output);
        }
        catch (\Throwable $err) {
          Utility::renderLog($err, $entity->id(), $entity->getType(), $page->id(), 'page render');
        }
      }
    }
    if (!$this->currentUser()
      ->hasPermission(HiddenTabPageInterface::PERMISSION_ADMINISTER)) {
      unset($output['admin']);
    }
    // TODO
    $output['admin']['#open'] = TRUE;
    return $output;
  }

  /**
   * Find the page form the path.
   *
   * @return array
   *   Uri type (secret/tab) and the page.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getPage(): array {
    $path = explode('/', $this->currentPath);
    if (count($path) !== 4 || ((((int) $path[2]) . '') !== ($path[2] . '')) || $path[1] !== 'node') {
      throw new NotFoundHttpException();
    }
    $page = $this->entityHelper->pageByTabUri($path[3]);
    $type = NULL;
    if ($page) {
      $type = 'tab';
    }
    if (!$page) {
      $page = $this->entityHelper->pageBySecretUri($path[3]);
      $type = 'uri';
    }
    if (!$page) {
      throw new NotFoundHttpException();
    }
    return [$type, $page];
  }

  /**
   * Check access to page by context: uri type (secret / tab) and the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity in question.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   The current page being visited.
   * @param string $type
   *   Type of uri: secret / tab.
   */
  protected function checkAccess(EntityInterface $entity, HiddenTabPageInterface $page, string $type) {
    if ($type !== 'uri') {
      return;
    }

    $can = $page->access(
      HiddenTabPageInterface::OP_VIEW_SECRET_URI,
      $this->currentUser,
      TRUE,
      $entity,
      $this->query);
    if ($can->isAllowed()) {
      return;
    }

    if ($page->isAccessDenied()) {
      /** @noinspection PhpUndefinedMethodInspection */
      throw new AccessDeniedHttpException(
        $page->isAccessDenied() && ($can instanceof AccessResultInterface)
          ? $can->getReason() :
          ''
      );
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
