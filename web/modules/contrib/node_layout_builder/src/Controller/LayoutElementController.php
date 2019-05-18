<?php

namespace Drupal\node_layout_builder\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node_layout_builder\Helpers\NodeLayoutBuilderHelper;
use Drupal\node_layout_builder\Helpers\NodeLayoutFileHelper;
use Drupal\node_layout_builder\NodeLayoutBuilderEditor;
use Drupal\node_layout_builder\NodeLayoutBuilderStyle;
use Drupal\node_layout_builder\Services\NodeLayoutBuilderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ElementLayoutController.
 *
 * Methods for handling content.
 */
class LayoutElementController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Form builder interface.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   *   The current route match.
   */
  protected $routeMatch;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module handler service.
   *
   * @var \Drupal\node_layout_builder\NodeLayoutBuilderEditor
   */
  protected $nlbEditor;

  /**
   * {@inheritdoc}
   */
  public function __construct(FormBuilderInterface $form_builder, AccountProxyInterface $currentUser, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $routeMatch, ModuleHandlerInterface $module_handler, NodeLayoutBuilderEditor $nlb_editor) {
    $this->formBuilder = $form_builder;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
    $this->moduleHandler = $module_handler;
    $this->nlbEditor = $nlb_editor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('node_layout_builder.editor')
    );
  }

  /**
   * Title modal form.
   *
   * @param int $nid
   *   ID of entity.
   * @param string $type
   *   Type of element.
   * @param int $parent
   *   ID parent.
   * @param int $id_element
   *   ID of element.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Title.
   */
  public function getTitle($nid, $type, $parent, $id_element) {
    $route_name = $this->routeMatch->getRouteName();

    if ($route_name == 'node_layout_builder.element.add') {
      $action = 'Add';
    }
    else {
      $action = 'Edit';
    }

    return $this->t('@action @title', [
      '@action' => $action,
      '@title' => $type,
    ]);
  }

  /**
   * Load list types of element.
   *
   * @param int $nid
   *   ID of entity.
   * @param string $type
   *   Type of element.
   * @param int $parent
   *   ID of element.
   * @param int $id_element
   *   ID of element.
   *
   * @return array
   *   Form to select type element that we want to add.
   */
  public function getElementsTypes($nid, $type, $parent, $id_element = 0) {
    $categories = $this->nlbEditor::linksCategoriesElements($nid, $parent, $id_element, $type);

    return [
      '#theme' => 'select_category_element',
      '#links' => $categories,
    ];
  }

  /**
   * Get form byt type element to add this element in layout.
   *
   * @param int $nid
   *   ID of entity.
   * @param string $type
   *   Type of element.
   * @param string $parent
   *   ID parent of element.
   * @param string $id_element
   *   ID parent of element.
   *
   * @return array
   *   Form element selected by params ($nid, $type, $parent).
   */
  public function addElement($nid, $type, $parent, $id_element) {
    $form = $this->formBuilder->getForm('Drupal\node_layout_builder\Form\AddElementForm', [
      'nid' => $nid,
      'type' => $type,
      'parent' => $parent,
      'id_element' => $id_element,
    ]);

    return $form;
  }

  /**
   * Get form byt type element to update this element.
   *
   * @param int $nid
   *   ID of entity.
   * @param string $type
   *   Type of element.
   * @param int $parent
   *   ID parent of parent.
   * @param int $id_element
   *   ID parent of element.
   *
   * @return array
   *   Form element selected by params ($nid, $type, $parent).
   */
  public function updateElement($nid, $type, $parent, $id_element) {
    $form = $this->formBuilder->getForm('Drupal\node_layout_builder\Form\AddElementForm', [
      'nid' => $nid,
      'type' => $type,
      'parent' => $parent,
      'id_element' => $id_element,
      'update' => TRUE,
    ]);

    return $form;
  }

  /**
   * Duplicate element.
   *
   * @param int $nid
   *   ID entity.
   * @param int|string $parent
   *   ID parent element.
   * @param int|string $id_element
   *   ID element.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function duplicateElement($nid, $parent, $id_element) {
    $data = NodeLayoutBuilderManager::loadDataElement($nid);

    $pathresult = NodeLayoutBuilderHelper::getkeypath($data, $id_element);
    krsort($pathresult);
    $element_to_duplicate = NodeLayoutBuilderHelper::getElementFromArrayData($data, $pathresult, TRUE);

    $date = date('YmdHis');
    $new_id_element = $date . uniqid();

    $element_duplicate = [
      $new_id_element => $element_to_duplicate['data'],
    ];

    if (isset($element_duplicate[$new_id_element]['#children'])) {
      if (count($element_duplicate[$new_id_element]['#children']) > 0) {
        NodeLayoutBuilderHelper::updateKeyElementAndParent($element_duplicate[$new_id_element]['#children'], $new_id_element);
      }
    }

    $clone_element = [];
    NodeLayoutBuilderHelper::duplicateElement($data, $id_element, $nid, $new_id_element, $element_to_duplicate['data'], $clone_element);

    $duplicate_id_element = key($clone_element);
    $element_duplicate = reset($clone_element);

    // Update data cache.
    NodeLayoutBuilderHelper::setCache($nid, $data);

    $pathresult = NodeLayoutBuilderHelper::getkeypath($data, $duplicate_id_element);
    krsort($pathresult);

    $path = [];
    $cur = &$path;
    foreach ($pathresult as $value) {
      $cur[$value] = [];
      $cur = &$cur[$value];
    }
    $cur = NULL;

    $item_id_string = '';
    foreach ($pathresult as $v) {
      $item_id_string .= "$v/";
    }
    $item_id_string = trim($item_id_string, '/');
    $keys = explode('/', $item_id_string);

    $temp = &$data;

    foreach ($keys as $key) {
      $temp = &$temp[$key];
    }

    $temp['#type'] = $element_duplicate['#type'];
    $temp['#parent'] = $duplicate_id_element['parent'];
    $temp['#data'] = $element_duplicate['#settings'];
    $temp['#attr'] = $element_duplicate['#attributes'];
    $temp['#styles'] = $element_duplicate['#styles'];

    $children = '';
    if (isset($temp['#children'])) {
      $children = $this->nlbEditor::renderChildrenRecursive($temp['#children'], $nid);
    }

    $styles_element = NodeLayoutBuilderStyle::getStyles($element_duplicate['#styles']);

    if ($element_duplicate['#type'] == 'section') {
      $class = '';
      $tag_element = 'section';
    }
    else {
      $class = 'element ';
      $tag_element = 'div';
      if ($element_duplicate['#type'] == 'column') {
        $class .= 'col-md-' . $element_duplicate['#data']['column']['grid'] . ' ';
      }
    }

    $element = [
      '#theme' => 'node_layout_builder_element',
      '#btns_actions' => $this->nlbEditor::renderBtnActions($element_duplicate['#type'], $nid, $duplicate_id_element, $element_duplicate['#parent']),
      '#nid' => $nid,
      '#type' => $element_duplicate['#type'],
      '#id_element' => $duplicate_id_element,
      '#parent' => $element_duplicate['#parent'],
      '#settings' => $element_duplicate['#data'],
      '#styles' => $styles_element,
      '#content_element' => $children,
      '#editable' => 1,
      '#class' => $class,
    ];

    $prefix = '<' . $tag_element . ' class="updated ' . $class . ' ' . $element_duplicate['#type'] . ' ' . $element_duplicate['#attributes']['container']['class'] . '" id="' . $duplicate_id_element . '" data-id="' . $duplicate_id_element . '" data-parent="' . $element_duplicate['#parent'] . '" data-type="nlb_' . $element_duplicate['#type'] . '" style="' . $styles_element . '">';
    $suffix = '</' . $tag_element . '>';

    $content = $prefix . render($element) . $suffix;

    $response = new AjaxResponse();
    if ($element_duplicate['#parent'] == 0) {
      $response->addCommand(new AppendCommand('.nlb-wrapper', $content));
    }
    else {
      if ($element_duplicate['#type'] == 'section' || $element_duplicate['#type'] == 'row') {
        $response->addCommand(new AppendCommand('#' . $element_duplicate['#parent'] . ' .container-fluid:eq(0)', $content));
      }
      else {
        $response->addCommand(new AppendCommand('#' . $element_duplicate['#parent'], $content));
      }
    }

    return $response;
  }

  /**
   * Remove element from data.
   *
   * @param int $nid
   *   ID entity.
   * @param string $id_element
   *   Id element.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Data updated.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeElement($nid, $id_element) {
    $data = NodeLayoutBuilderManager::loadDataElement($nid);
    $pathresult = NodeLayoutBuilderHelper::getkeypath($data, $id_element);
    krsort($pathresult);

    // Remove Files.
    $element = NodeLayoutBuilderHelper::getElementFromArrayData($data, $pathresult);
    if (!$element) {
      $data = NodeLayoutBuilderHelper::getCache($nid);
      $element = NodeLayoutBuilderHelper::getElementFromArrayData($data, $pathresult);
    }
    NodeLayoutFileHelper::saveFileImgBgRecursively([$element], 'delete');
    NodeLayoutFileHelper::saveFileImgRecursively([$element], 'delete');

    $data_updated = NodeLayoutBuilderHelper::removeArrayKey($pathresult, $data);

    $data_cache = NodeLayoutBuilderHelper::getCache($nid);
    if (count($data_cache) > 0) {
      NodeLayoutBuilderHelper::setCache($nid, $data_updated);
    }
    else {
      $uuid = $this->currentUser->id();
      $entities = $this->entityTypeManager
        ->getStorage('node_layout_builder')
        ->loadByProperties(['entity_id' => $nid]);
      if (!empty($entities)) {
        $entity = reset($entities);
        if (is_array($data)) {
          if (count($data) > 0) {
            $entity->set('uuid', $uuid);
            $entity->set('data', $data);
            $entity->save();
          }
        }
      }
    }

    $response = new AjaxResponse();
    // Show button "choose a template".
    if (count($data_updated) == 0 || count($data_cache) == 0 || count($data) == 0) {
      $response->addCommand(new CssCommand('.add-templates', ['display' => 'block']));
    }
    $response->addCommand(new RemoveCommand('#' . $id_element));

    return $response;
  }

  /**
   * Save data of element.
   *
   * @param int $nid
   *   ID element.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response width redirection to url.
   */
  public function saveDataElement($nid) {
    $uuid = $this->currentUser->id();

    // Get data layout node from cache.
    $data = NodeLayoutBuilderHelper::getCache($nid);
    $this->nlbEditor::saveElementEntity($nid, $uuid, $data);

    return new AjaxResponse([
      'msg' => $this->t('Data of layout has been saved successfully'),
    ]);
  }

  /**
   * Change position of element.
   *
   * @param int $nid
   *   ID entity.
   * @param int|string $from
   *   Old position.
   * @param int|string $to
   *   New position.
   * @param int|string $index
   *   Order of element.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Data updated with new position of element.
   */
  public function sortableDataElement($nid, $from, $to, $index) {
    // http://www.codingsips.com/php-echo-without-waiting-finish-execution.
    ob_end_flush();
    ob_implicit_flush();

    $data = NodeLayoutBuilderHelper::getCache($nid);

    if ($to == 'undefined') {
      $arrOrder = [];
      foreach ($data as $key => $arr) {
        $arrOrder[] = [$key => $arr];
      }

      $pathresult = NodeLayoutBuilderHelper::getkeypath($arrOrder, $from);
      krsort($pathresult);

      $element = NodeLayoutBuilderHelper::getElementFromArrayData($arrOrder, $pathresult, TRUE);
      $key = array_shift(array_values($element['keys']));

      $data_updated = NodeLayoutBuilderHelper::moveElementInArray($arrOrder, (int) $key, $index);
      $data_sortabled = [];
      foreach ($data_updated as $key => $data) {
        $data_sortabled += $data;
      }
    }
    else {
      if ($index != 0) {
        $index = (int) $index - 1;
      }

      // Move and remove element.
      $pathresult_from = NodeLayoutBuilderHelper::getkeypath($data, $from);
      krsort($pathresult_from);

      $element_from = NodeLayoutBuilderHelper::getElementFromArrayData($data, $pathresult_from, TRUE);

      // Change id parent.
      $element_from['data']['#parent'] = $to;

      $data_updated = NodeLayoutBuilderHelper::removeArrayKey($pathresult_from, $data);

      // Move and add element.
      $pathresult_to = NodeLayoutBuilderHelper::getkeypath($data_updated, $to);
      krsort($pathresult_to);
      $data_sortabled = NodeLayoutBuilderHelper::addElementToElementData($data_updated, $pathresult_to, $element_from, $index);
    }

    NodeLayoutBuilderHelper::setCache($nid, $data_sortabled);

    $response = new AjaxResponse();
    return $response;
  }

  /**
   * Get List of templates.
   *
   * @param int $nid
   *   ID entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Popup to choose a template.
   */
  public function listTemplates($nid) {
    global $base_path;

    $moduel_path = $base_path . $this->moduleHandler->getModule('node_layout_builder')->getPath();

    module_load_include('inc', 'node_layout_builder', 'node_layout_builder.data.templates');
    $templates = data_templates();
    $links_templates = [];

    foreach ($templates as $key => $template) {
      $links_templates[] = [
        '#type' => 'link',
        '#title' => Markup::create('<img src="' . $moduel_path . '/assets/img/templates/' . $template['preview'] . '" />'),
        '#url' => Url::fromRoute(
          'node_layout_builder.template.add',
          [
            'nid' => $nid,
            'tid' => $key,
          ]
        ),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'icon-plus',
            'item-template',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => $this->nlbEditor::modalDialogOptions(),
          'title' => $this->t('Select a template'),
        ],
      ];
    }

    $list_template = [
      '#theme' => 'item_list',
      '#items' => $links_templates,
      '#title' => NULL,
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => ['list-templates'],
      ],
    ];

    $response = new AjaxResponse();
    $response->addCommand(
      new OpenModalDialogCommand(
        $this->t('Choose a template'),
        render($list_template),
        [
          'width' => '70%',
          'height' => 'auto',
          'maxWidth' => '900',
          'resizable' => TRUE,
          'modal' => TRUE,
          'top' => '10%',
        ]
      )
    );

    return $response;
  }

  /**
   * Choose a template.
   *
   * @param int $nid
   *   ID entity.
   * @param int $tid
   *   ID template.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Hanlder ajax command.
   */
  public function addTemplate($nid, $tid) {
    // Load liste templates.
    module_load_include('inc', 'node_layout_builder', 'node_layout_builder.data.templates');
    $templates = data_templates();
    $data = $templates[$tid]['data'];

    // Set data of element.
    NodeLayoutBuilderHelper::setCache($nid, $data);

    $content = $this->nlbEditor->recursive($nid, $data, 1);

    // Response.
    $response = new AjaxResponse();
    // Add template.
    $response->addCommand(new AppendCommand('.nlb-wrapper', $content));
    // Hide button "choose a template".
    $response->addCommand(new CssCommand('.add-templates', ['display' => 'none']));
    // Close Dialog.
    $response->addCommand(new CloseDialogCommand('.ui-dialog-content'));

    return $response;
  }

}
