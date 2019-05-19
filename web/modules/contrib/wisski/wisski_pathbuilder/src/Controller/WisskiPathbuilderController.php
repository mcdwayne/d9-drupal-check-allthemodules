<?php
/**
 * @file
 *
 * Contains drupal\wisski_pathbuilder\WisskiPathbuilderListBuilder
 */
    
namespace Drupal\wisski_pathbuilder\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Utility\LinkGeneratorInterface;    
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
    
class WisskiPathbuilderController extends ControllerBase {

  /**
   * The factory for entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;
                
 /**
  * The menu link manager.
  *
  * @var \Drupal\Core\Menu\MenuLinkManagerInterface
  */
  protected $menuLinkManager;
               
  /**
   * The pathbuilder tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $pathbuilderTree;
  
  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;
                  
  /**
   * The overview tree form.
   *
   * @var array
   */
   
  public $overviewTreeForm = array('#tree' => TRUE);
                   
  /**
   * Constructs a MenuForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *   The factory for entity queries.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $pathbuilder_tree
   *   The menu tree service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   */
  public function __construct(QueryFactory $entity_query_factory, MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $pathbuilder_tree, LinkGeneratorInterface $link_generator, WisskiPathbuilder $pb) {
    $this->entityQueryFactory = $entity_query_factory;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTree = $pathbuilder_tree;
    $this->linkGenerator = $link_generator;             
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    # $container->get('date.formatter'),
     $container->get('entity.query'),
     $container->get('plugin.manager.menu.link'),
     $container->get('menu.link_tree'),
     $container->get('link_generator')
   );
                            
  } 

  public function loadTreeData($path_name, MenuTreeParameters $parameters) {
    // Build the cache ID; sort 'expanded' and 'conditions' to prevent duplicate
    // cache items.
    sort($parameters->expandedParents);
    asort($parameters->conditions);
    $tree_cid = "tree-data:$menu_name:" . serialize($parameters);
    $cache = $this->menuCacheBackend->get($tree_cid);
    if ($cache && isset($cache->data)) {
      $data = $cache->data;
      // Cache the definitions in memory so they don't need to be loaded again.
      $this->definitions += $data['definitions'];
      unset($data['definitions']);
    }
    else {
      $links = $this->loadLinks($menu_name, $parameters);
      $data['tree'] = $this->doBuildTreeData($links, $parameters->activeTrail, $parameters->minDepth);
      $data['definitions'] = array();
      $data['route_names'] = $this->collectRoutesAndDefinitions($data['tree'], $data['definitions']);
      $this->menuCacheBackend->set($tree_cid, $data, Cache::PERMANENT, ['config:system.menu.' . $menu_name]);
      // The definitions were already added to $this->definitions in
      // $this->doBuildTreeData()
      unset($data['definitions']);
    }
    return $data;
  }




#  function viewPB(FormStateInterface $form_state, $wisski_pathbuilder) {   
  public function form(array $form, FormStateInterface $form_state) {
    drupal_set_message("using function form");     
    #$form = array(
     # '#type' => 'markup',
      #'#markup' => 'hello world',
    #);
    
    $pathbuilder_entity = entity_load('wisski_pathbuilder', $wisski_pathbuilder);
    
    $path_entities = entity_load_multiple('wisski_path');
    #drupal_set_message('wisski pathbuilder id: ' . serialize($wisski_pathbuilder));
    #drupal_set_message(serialize($pathbuilder_entity));
    #drupal_set_message(serialize($path_entities));
    
     $form['#title'] = $this->t('Edit pathbuilder %label', array('%label' => 'pb'));   
            
     $form['label'] = array(
       '#type' => 'textfield',
       '#title' => 'Title',
       '#default_value' => 'pb',
     #  '#required' => TRUE,
     );
     $form['id'] = array(
       '#type' => 'textfield',
       '#title' => 'Pathbuilder ID',
       '#default_value' => 'pb',               
      #  '#type' => 'machine_name',
      #  '#title' => $this->t('Menu name'),
      #  '#default_value' => $menu->id(),
      #  '#maxlength' => MENU_MAX_MENU_NAME_LENGTH_UI,
      #  '#description' => $this->t('A unique name to construct the URL for the menu. It must only contain lowercase letters, numbers and hyphens.'),
      #  '#machine_name' => array(
      #    'exists' => array($this, 'menuNameExists'),
      #    'source' => array('label'),
      #    'replace_pattern' => '[^a-z0-9-]+',
      #    'replace' => '-',
      #     ),
     // A menu's machine name cannot be changed.
     #'#disabled' => !$menu->isNew() || $menu->isLocked(),
     );
     $form['description'] = array(
       '#type' => 'textfield',
       '#title' => t('Description'),
       '#maxlength' => 512,
       '#default_value' => 'Here is the pathbuilder description',
     );


     // Add menu links administration form for existing menus.
    # if (!$menu->isNew() || $menu->isLocked()) {
     // Form API supports constructing and validating self-contained sections
     // within forms, but does not allow handling the form section's submission
     // equally separated yet. Therefore, we use a $form_state key to point to
     // the parents of the form section.
     // @see self::submitOverviewForm()
       $form_state->set('pathbuilder_overview_form_parents', ['path_items']);
       $form['path_items'] = array();
       $form['path_items'] = $this->buildOverviewForm($form['path_items'], $form_state);
     #}
      return parent::form($form, $form_state);                                                                                                                                                                               
    
    }
    
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;
    if (!$menu->isNew() || $menu->isLocked()) {
      $this->submitOverviewForm($form, $form_state);
    }

    $status = $menu->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Menu %label has been updated.', array('%label' => $menu->label())));
      $this->logger('menu')->notice('Menu %label has been updated.', array('%label' => $menu->label(), 'link' => $edit_link));
    }
    else {
      drupal_set_message($this->t('Menu %label has been added.', array('%label' => $menu->label())));
      $this->logger('menu')->notice('Menu %label has been added.', array('%label' => $menu->label(), 'link' => $edit_link));
    }

    $form_state->setRedirectUrl($this->entity->urlInfo('edit-form'));
  }
    
  
  private function pb_render_path($path) {
    $pathform = array();

    $pathform['#item'] = $path;
    
    $pathform['#attributes'] = $path->getEnabled() ? array('class' => array('menu-enabled')) : array('class' => array('menu-disabled')); 
      
    $pathform['title'] = $path->getName();
      
    if (!$path->getEnabled()) {
      $pathform['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
    }
      
    $pathform['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable @title path', array('@title' => $path->getName())),
      '#title_display' => 'invisible',
      '#default_value' => $path->getEnabled(),
    );

    $pathform['weight'] = array(
      '#type' => 'weight',
      '#delta' => 100, # Do something more cute here $delta,
      #'#default_value' => $path->getWeight(),
      '#default_value' => 100,       
      '#title' => $this->t('Weight for @title', array('@title' => $path->getName())),
      '#title_display' => 'invisible',
    );

    $pathform['id'] = array(
      '#type' => 'hidden',
      '#value' => $path->getID(),
    );

   # $pathform['parent'] = array(
    #  '#type' => 'hidden',
     # '#default_value' => $path->parent,
    #);
    return $pathform;
  }
    
  function viewPB($wisski_pathbuilder) {
  // Ensure that menu_overview_form_submit() knows the parents of this form
  // section.
#  if (!$form_state->has('pathbuilder_overview_form_parents')) {
#   $form_state->set('pathbuilder_overview_form_parents', []);
#  }

    // load the pathbuilder entity that is used - given by the parameter
    // in the url.                        
    $pathbuilder_entity = entity_load('wisski_pathbuilder', $wisski_pathbuilder);
    
    // load all paths - here we should load just the ones of this pathbuilder
    $path_entities = entity_load_multiple('wisski_path');
    #drupal_set_message('wisski pathbuilder id: ' . serialize($wisski_pathbuilder));    

    $form = array();
    
    $header = array("title", "Path", array('data' => $this->t("Enabled"), 'class' => array('checkbox')), "Weight", array('data' => $this->t('Operations'), 'colspan' => 3));
    
    $form['pathbuilder_table'] = array(
      '#type' => 'table',
#      '#theme' => 'table__menu_overview',
      '#header' => $header,
#      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'my-module-table',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => 9,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );
    
    foreach($path_entities as $path) {
      #drupal_set_message(serialize($path));

      $pathform = $this->pb_render_path($path);
      $path_id = $path->getID();
      
      $form['pathbuilder_table'][$path_id]['#item'] = $pathform['#item'];
      
      // TableDrag: Mark the table row as draggable.
      $form['pathbuilder_table'][$path_id]['#attributes'] = $pathform['#attributes'];
      $form['pathbuilder_table'][$path_id]['#attributes']['class'][] = 'draggable';


        // TableDrag: Sort the table row according to its existing/configured weight.
      $form['pathbuilder_table'][$path_id]['#weight'] = $pathform['#item']->getWeight();

      // Add special classes to be used for tabledrag.js.
      $pathform['parent']['#attributes']['class'] = array('menu-parent');
      $pathform['weight']['#attributes']['class'] = array('menu-weight');
      $pathform['id']['#attributes']['class'] = array('menu-id');

      $form['pathbuilder_table'][$path_id]['title'] = array(
          array(
            '#theme' => 'indentation',
            '#size' => $pathform['#item']->depth - 1,
          ),
          $pathform['title'],
        );
      $form['pathbuilder_table'][$path_id]['enabled'] = $pathform['enabled'];
      $form['pathbuilder_table'][$path_id]['enabled']['#wrapper_attributes']['class'] = array('checkbox', 'menu-enabled');

      $form['pathbuilder_table'][$path_id]['weight'] = $pathform['weight'];

        // Operations (dropbutton) column.
      $form['pathbuilder_table'][$path_id]['operations'] = $pathform['operations'];

      $form['pathbuilder_table'][$path_id]['id'] = $pathform['id'];
      $form['pathbuilder_table'][$path_id]['parent'] = $pathform['parent'];
                      
      
      
    }
    
/*
        // Build a list of operations.
        $operations = array();
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
        );
        // Allow for a custom edit link per plugin.
        $edit_route = $link->getEditRoute();
        if ($edit_route) {
          $operations['edit']['url'] = $edit_route;
          // Bring the user back to the menu overview.
          $operations['edit']['query'] = $this->getDestinationArray();
        }
        else {
          // Fall back to the standard edit link.
          $operations['edit'] += array(
            'url' => Url::fromRoute('menu_ui.link_edit', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        // Links can either be reset or deleted, not both.
        if ($link->isResettable()) {
          $operations['reset'] = array(
            'title' => $this->t('Reset'),
            'url' => Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        elseif ($delete_link = $link->getDeleteRoute()) {
          $operations['delete']['url'] = $delete_link;
          $operations['delete']['query'] = $this->getDestinationArray();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($link->isTranslatable()) {
          $operations['translate'] = array(
            'title' => $this->t('Translate'),
            'url' => $link->getTranslateRoute(),
          );
        }
        $form[$id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );                                
*/
/*      
      $rows[] = array(
        'data' => array(
          'name' => $path->name,
          'path' => $path->path,
          'enabled' => $path->enabled,
          'weight' => array(
            '#type' => 'weight',
            '#title' => t('Weight'),
            '#default_value' => $path->weight,
            '#delta' => 10,
            '#title_display' => 'invisible',
          ),
          'operations' => 'juhu',
        ),
        'class' => array('draggable'),
      );
    }
    */
    /*  
    $rows[] = array( 
      'data' => array(
        'name' => "hallo",
         'weight' => array(
                 '#type' => 'weight',
                         '#title' => t('Weight'),
                                 '#default_value' => 0,
                                         '#delta' => 10,
                                                 '#title_display' => 'invisible',
                                                       ),
      ),
      'class' => array('draggable'),
    );
    
    $rows[] = array( 
      'data' => array(
        'name' => "welt",
         'weight' => array(
                 '#attributes' => array(
                   'class' => 'example-item-weight',
                  ),
                 '#type' => 'weight',
                         '#title' => t('Weight'),
                                 '#default_value' => 1,
                                         '#delta' => 10,
                                                 '#title_display' => 'invisible',
                                                       ),
      ),
      'class' => array('draggable'),
    );
    */
    
    /*
    $form = array(
      '#type' => 'table',
#      '#theme' => 'table__menu_overview',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'my-module-table',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => 9,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );
*/
#    drupal_attach_tabledrag($form, array(
#      'action' => 'order',
#        'relationship' => 'sibling',
#          'group' => 'my-elements-weight',
#          ));

    #drupal_set_message(serialize($form));

    return $form;
  
    
  
  
  }
/* BY KERSTIN    
    foreach ($path_entities as $entity){        
    #$tree = $this->menuTree->load($this->entity->id(), new MenuTreeParameters());
    drupal_set_message('entity: ' . serialize($entity));
    $menu_tree = \Drupal::menuTree();
    $parameters = new MenuTreeParameters();   
    $tree = $menu_tree->load(NULL, $parameters);
    drupal_set_message('tree1: ');
    drupal_set_message(serialize($tree));
    // We indicate that a menu administrator is running the menu access check.
   #  $entity->getRequest()->attributes->set('_menu_admin', TRUE);     
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    #  array('callable' => 'toolbar_menu_navigation_links'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);
   #  $entity->getRequest()->attributes->set('_menu_admin', FALSE);   
    drupal_set_message('tree2: ');
    drupal_set_message(serialize($tree)); 

    // Determine the delta; the number of weights to be made available.
    $count = function(array $tree) {
    $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();    
      };
      return array_reduce($tree, $sum);
    };
    $delta = max($count($tree), 50);
  }
    $form['path_items'] = array(
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
     # '#header' => array(t('Label'), t('Machine name'), t('Weight'), t('Operations')), 
      '#header' => array(
        $this->t('Path'),
        array(
          'data' => $this->t('Enabled'),
          'class' => array('checkbox'),
        ),
        $this->t('Weight'),
        array(
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ),
      ),
      '#attributes' => array(
        'id' => 'menu-overview',
     # '#empty' => t('There are no items yet. <a href="@add-path-url">Add an item.</a>', array(
     # '@add-path-url' => Url::fromRoute('entity.wisski_path.add_form'), 
     # )), 
       '#empty' => t('There are no WissKI Pathbuilder Paths yet.'), 
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag(). The #id of the table is automatically prepended;
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => array(       
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'wisski-order-weight',
        ),
      ),
      ),
    );
    $path_items = $this->buildOverviewTreeForm($tree, $delta);
    foreach (Element::children($path_items) as $id) {
      if (isset($path_items[$id]['#item'])) {
        $element = $path_items[$id];
             
        $form['path_items'][$id]['#item'] = $element['#item'];
                     
        // TableDrag: Mark the table row as draggable.
        $form['path_items'][$id]['#attributes'] = $element['#attributes'];
        $form['path_items'][$id]['#attributes']['class'][] = 'draggable';
                                             
        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['path_items'][$id]['#weight'] = $element['#item']->link->getWeight();
        
        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = array('menu-parent');
        $element['weight']['#attributes']['class'] = array('wisski-order-weight');
        $element['id']['#attributes']['class'] = array('menu-id');
                                 
        $form['path_items'][$id]['title'] = array(
          array(
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ),
          $element['title'],
        );
        $form['path_items'][$id]['enabled'] = $element['enabled'];
        $form['path_items'][$id]['enabled']['#wrapper_attributes']['class'] = array('checkbox', 'menu-enabled');
                 
        $form['path_items'][$id]['weight'] = $element['weight'];
                        
        // Operations (dropbutton) column.
        $form['path_items'][$id]['operations'] = $element['operations'];
                                         
        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    } 
                                                                  
    return $form;
    }
                                                                        
            */                                                                                           
                                                             
          
    
    // Build the table rows and columns.
    // The first nested level in the render array forms the table row, on which you
    // likely want to set #attributes and #weight.
    // Each child element on the second level represents a table column cell in the
    // respective table row, which are render elements on their own. For single
    // output elements, use the table cell itself for the render element. If a cell
    // should contain multiple elements, simply use nested sub-keys to build the
    // render element structure for drupal_render() as you would everywhere else.             
    // Iterate through each path entity
    
    #drupal_set_message('wisski pathbuilder id: ' . serialize($wisski_pathbuilder));
/**             
    foreach ($path_entities as $id => $path_entity) {
       drupal_set_message($path_entity->id . ':');
       drupal_set_message(serialize($path_entity));
       #drupal_set_message('name: ' . $path_entity->get('id'));
       $weightbool = is_null($path_entity->get('weight')); 
       #drupal_set_message(serialize($weightbool));     
       #if ($weightbool) drupal_set_message('weight is null!');
       
       drupal_set_message('weight: ' . $path_entity->get('weight'));
       #drupal_set_message('weight2: ' . $path_entity->weight);
                
      // TableDrag: Mark the table row as draggable.
      $form['path_items'][$id]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      $form['path_items'][$id]['#weight'] = $path_entity->get('weight'); 
      
      // Some table columns containing raw markup.
      $form['path_items'][$id]['label'] = array(
        '#plain_text' => $path_entity->label(),
      );
      $form['path_items'][$id]['id'] = array(
        '#plain_text' => $path_entity->id(),
      );
      
      // TableDrag: Weight column element.
      $form['path_items'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $path_entity->label())),
        '#title_display' => 'invisible',
        '#default_value' => $path_entity->get('weight'),
       # '#default_value' => ($weightbool ? $path_entity->set('weight', '-10') : $path_entity->get('weight')),
       # '#delta' => 10,     
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('wisski-order-weight')),
        );
        
        // Operations (dropbutton) column.
        $form['path_items'][$id]['operations'] = array(
          '#type' => 'operations',
          '#links' => array(),
        );
        $form['path_items'][$id]['operations']['#links']['edit'] = array(
          'title' => t('Edit'),
          'url' => Url::fromRoute('entity.wisski_path.edit_form', array('wisski_pathbuilder' => $wisski_pathbuilder,'wisski_path' => $id)),
        );
        $form['path_items'][$id]['operations']['#links']['delete'] = array(
          'title' => t('Delete'),
          #'url' => Url::fromRoute('entity.wisski_path.delete_form', array('id' => $id,'wisski_pathbuilder'=>$wisski_pathbuilder,'wisski_path'=>$id)),
          'url' => Url::fromRoute('entity.wisski_path.delete_form', array('wisski_pathbuilder' => $wisski_pathbuilder,'wisski_path' => $id)),
        );
      }                       
      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save changes'),
      );
      
                             
      return $form;
                          
   /**  
     foreach($path_entities as $path_entity) {
        drupal_set_message(serialize($path_entity));
        drupal_set_message($path_entity->id);       
       // Each entry will be an array using the unique id for that path_entity as
       // the array key, and an array of table row data as the value.
       $form['path_items'][$path_entity->id] = array(
       
       // We'll use a form element of type '#markup' to display the item name.
           'name' => array(
             '#markup' => $path_entity->name,
             #  ),
           ),
           
        // The 'weight' field will be manipulated as we move the items around in
        // the table using the tabledrag activity.  We use the 'weight' element
        // defined in Drupal's Form API.
           'weight' => array(
           '#type' => 'weight',
           '#title' => t('Weight'),
           '#default_value' => $path_entity->weight,
           '#delta' => 10,
           '#title_display' => 'invisible',
           ),                                                                             
       );
       
     }
        // Now we add our submit button, for submitting the form results.
        //
        // The 'actions' wrapper used here isn't strictly necessary for tabledrag,
        // but is included as a Form API recommended practice.
        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save Changes'));             
    
     return $form;
 */     
    #return drupal_render($table); 
 // }


  /**
   * Recursive helper function for buildOverviewForm().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The tree retrieved by \Drupal\Core\Menu\MenuLinkTreeInterface::load().
   * @param int $delta
   *   The default number of menu items used in the menu weight selector is 50.
   *
   * @return array
   *   The overview tree form.
   */
  protected function buildOverviewTreeForm($tree, $delta) {
    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link) {
        $id = 'menu_plugin_id:' . $link->getPluginId();
        $form[$id]['#item'] = $element;
        $form[$id]['#attributes'] = $link->isEnabled() ? array('class' => array('menu-enabled')) : array('class' => array('menu-disabled'));
        $form[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
        if (!$link->isEnabled()) {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
        }
    /*    // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif ($id === 'menu_plugin_id:user.logout') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('<q>Log in</q> for anonymous users') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif (($url = $link->getUrlObject()) && $url->isRouted() && $url->getRouteName() == 'user.page') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('logged in users only') . ')';
        }
 */
        $form[$id]['enabled'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', array('@title' => $link->getTitle())),
          '#title_display' => 'invisible',
          '#default_value' => $link->isEnabled(),
        );
        $form[$id]['weight'] = array(
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $link->getWeight(),
          '#title' => $this->t('Weight for @title', array('@title' => $link->getTitle())),
          '#title_display' => 'invisible',
        );
        $form[$id]['id'] = array(
          '#type' => 'hidden',
          '#value' => $link->getPluginId(),
        );
        $form[$id]['parent'] = array(
          '#type' => 'hidden',
          '#default_value' => $link->getParent(),
        );
        // Build a list of operations.
        $operations = array();
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
        );
        // Allow for a custom edit link per plugin.
        $edit_route = $link->getEditRoute();
        if ($edit_route) {
          $operations['edit']['url'] = $edit_route;
          // Bring the user back to the menu overview.
          $operations['edit']['query'] = $this->getDestinationArray();
        }
        else {
          // Fall back to the standard edit link.
          $operations['edit'] += array(
            'url' => Url::fromRoute('menu_ui.link_edit', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        // Links can either be reset or deleted, not both.
        if ($link->isResettable()) {
          $operations['reset'] = array(
            'title' => $this->t('Reset'),
            'url' => Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        elseif ($delete_link = $link->getDeleteRoute()) {
          $operations['delete']['url'] = $delete_link;
          $operations['delete']['query'] = $this->getDestinationArray();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($link->isTranslatable()) {
          $operations['translate'] = array(
            'title' => $this->t('Translate'),
            'url' => $link->getTranslateRoute(),
   
       );
        }
        $form[$id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
      }

      if ($element->subtree) {
        $this->buildOverviewTreeForm($element->subtree, $delta);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }

/** 
 * Theme callback 
 *
 * The theme callback will format the $form data structure into a table and
 * add our tabledrag functionality.  (Note that drupal_add_tabledrag should be
 * called from the theme layer, and not from a form declaration.  This helps
 * keep template files clean and readable, and prevents tabledrag.js from
 * being added twice accidently.
 *
 * @return array
 *   The rendered tabledrag form
 *
 * 
 */
/** 
function wisski_pathbuilder_tabledrag_form($variables) {
  $form = $variables['form'];
               
  // Initialize the variable which will store our table rows.
  $rows = array();
  // Iterate over each element in our $form['example_items'] array.
  foreach (element_children($form['path_items']) as $id) {
  
    // Before we add our 'weight' column to the row, we need to give the
    // element a custom class so that it can be identified in the
    // drupal_add_tabledrag call.
    //
    // This could also have been done during the form declaration by adding
    // '#attributes' => array('class' => 'example-item-weight'),
    // directy to the 'weight' element in tabledrag_example_simple_form().
    $form['path_items'][$id]['weight']['#attributes']['class'] = array('path-item-weight');
                               
    // We are now ready to add each element of our $form data to the $rows
    // array, so that they end up as individual table cells when rendered
    // in the final table.  We run each element through the drupal_render()
    // function to generate the final html markup for that element.
    $rows[] = array(
      'data' => array(
        // Add our 'name' column.
        drupal_render($form['path_items'][$id]['name']),
        // Add our 'description' column.
      #  drupal_render($form['example_items'][$id]['description']),
        
        // Add our 'weight' column.
        drupal_render($form['path_items'][$id]['weight']),
      ),
      // To support the tabledrag behaviour, we need to assign each row of the
      // table a class attribute of 'draggable'. This will add the 'draggable'
      // class to the <tr> element for that row when the final table is
      // rendered.
      'class' => array('draggable'),
    );
  }
      
  // We now define the table header values.  Ensure that the 'header' count
  // matches the final column count for your table.
  $header = array(t('Name'), t('Weight'));
    
  // We also need to pass the drupal_add_tabledrag() function an id which will
  // be used to identify the <table> element containing our tabledrag form.
  // Because an element's 'id' should be unique on a page, make sure the value
  // you select is NOT the same as the form ID used in your form declaration.
  $table_id = 'path-items-table';
                 
  // We can render our tabledrag table for output.
  $output = theme('table', array(
    'header' => $header,
    'rows' => $rows,
    '#attributes' => array('id' => $table_id),
    '#tabledrag' => array(
      array(
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'example-item-weight',
      ),
    ),
  ));
                       
  // And then render any remaining form elements (such as our submit button).
  $output .= drupal_render_children($form);
  
  // We now call the drupal_add_tabledrag() function in order to add the
  // tabledrag.js goodness onto our page.
  //
  // For a basic sortable table, we need to pass it:
  // - the $table_id of our <table> element,
  // - the $action to be performed on our form items ('order'),
  // - a string describing where $action should be applied ('siblings'),
  // - and the class of the element containing our 'weight' element.
  drupal_attach_tabledrag($table_id, array(
  'action' => 'order',
  'relationship' => 'sibling', 
  'group' => 'path-item-weight',
  ));
  return $output;
  }
 */                                                                                                                                                              

}
