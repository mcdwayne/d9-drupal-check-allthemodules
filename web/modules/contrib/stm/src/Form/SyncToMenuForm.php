<?php

/**
 * @file
 * Contains \Drupal\stm\Form\SyncToMenuForm.
 */

namespace Drupal\stm\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for term display edit form.
 */
class SyncToMenuForm extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The term storage handler.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storageController;

  /**
   * Constructs an OverviewTerms object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager) {
    $this->moduleHandler = $module_handler;
    $this->storageController = $entity_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_sync_to_menu';
  }

  /**
   * Form constructor.
   *
   * Display a tree of all the terms in a vocabulary, with options to edit
   * each one. The form is made drag and drop by the theme function.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\taxonomy\VocabularyInterface $taxonomy_vocabulary
   *   The vocabulary to display the overview form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL) {
    $form_state->set(['taxonomy', 'vocabulary'], $taxonomy_vocabulary);
	// Menu selection.
    $options = [];
    $menu_storage = \Drupal::entityManager()->getStorage('menu');
    foreach ($menu_storage->loadMultiple() as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    $form['menu_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#options' => $options,
    ];

    $form['path_pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path pattern'),
      '#maxlength' => 255,
      '#default_value' => '/taxonomy/term/%tid',
      '#description' => $this->t("%tid is the dynamic tid, you could change other parts."),
      '#required' => TRUE,
    );
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Sync'),
        '#button_type' => 'primary',
    );
	
    return $form;
  }

  /**
   * Form submission handler.
   *
   * Rather than using a textfield or weight field, this form depends entirely
   * upon the order of form elements on the page to determine new weights.
   *
   * Because there might be hundreds or thousands of taxonomy terms that need to
   * be ordered, terms are weighted from 0 to the number of terms in the
   * vocabulary, rather than the standard -10 to 10 scale. Numbers are sorted
   * lowest to highest, but are not necessarily sequential. Numbers may be
   * skipped when a term has children so that reordering is minimal when a child
   * is added or removed from a term.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
	$vid = $vocabulary->id();
	$menu_name = $form_state->getValue('menu_name');
	$path_pattern = $form_state->getValue('path_pattern');
	//$
	$terms = $this->storageController->loadTree($vocabulary->id(), 0, 1, TRUE);
	foreach($terms as $term){
	  //drupal_set_message($this->t('tid:') . $term->id());
	  $this->syncTermToMenuLink($term, $menu_name, $path_pattern, $vid);
	}
    //drupal_set_message($this->t('successs:') . $vocabulary->id());
  }
  
  
  public function syncTermToMenuLink(Term $term, $menu_name, $path_pattern, $vid) {
    
    $tid = $term->id();
	//if($tid != 1){
	  //return;
	//}
	
	
	$path = strtr($path_pattern, array('%tid' => $tid));
	$uri = 'internal:' . $path;
   $children_terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren($term->id(), $vid);
  
	$menu_link = $this->loadMenuLinkByUri($uri, $menu_name, $vid);
	
	$parent_tid = isset($term->parent_tid) ? $term->parent_tid : null;
	$parent = null;
	if(!empty($parent_tid)){
	  $parent_path = strtr($path_pattern, array('%tid' => $parent_tid));
	  $parent_uri = 'internal:' . $parent_path;	
	  $parent_menu_link = $this->loadMenuLinkByUri($parent_uri, $menu_name, $vid);
	  $parent = 'menu_link_content:' . $parent_menu_link->Uuid();
	}
	//drupal_set_message('parent_tid:'. $parent_tid);
	/*
    $query = \Drupal::entityQuery('menu_link_content');
    $query->condition('link.uri', $uri);
    $query->condition('link.title', 'stm-' . $vid);
    $query->condition('menu_name', $menu_name);
    $result = $query->range(0, 1)->execute();
    foreach($result as $key => $value){
      drupal_set_message('key:'.$key);
	  drupal_set_message('value:'. var_export($value,true));
    }
	*/
    if(!empty($menu_link)){
      //$menu_link_id = reset($result);
	  //$menu_link = \Drupal::entityManager()->getStorage('menu_link_content')->load($menu_link_id);
      $menu_link->title->value = $term->label();
	  $menu_link->weight->value = $term->weight->value;
	  if(!empty($parent)){
		  $menu_link->parent->value = $parent;
		}	  
	  $menu_link->save();
    }else{ 
		// Use the entity manager.
		$menu_link_array = array(
		  'menu_name' => $menu_name,
		  'title' => $term->label(),
		  'weight' => $term->weight->value,
		  'metadata' => array(
			'taxonomy_term_id' => $tid,
		  ),
		  'form_class' => '\Drupal\menu_link_content\Form\MenuLinkContentForm',
		  'enabled' => 1,
		  'provider' => 'menu_link_content',
		  //'parent' => $menu_parent_id,
		  'link' => array('uri' => $uri, 'title' => 'stm-' . $vid),
		  //'url' => $uri,
		);
		if(!empty($parent)){
		  $menu_link_array['parent'] = $parent;
		}
		
		
		if(!empty($children_terms)){
		  $menu_link_array['expanded'] = 1;
		}
		
		$menu_link = \Drupal::entityManager()->getStorage('menu_link_content')->create($menu_link_array);
		$menu_link->save();
	}
	foreach($children_terms as $children_term){
	  //drupal_set_message($this->t('children_term :tid:') . $children_term->id());
	  //save some cuputer time;
	  $children_term->parent_tid = $tid;
	  $this->syncTermToMenuLink($children_term, $menu_name, $path_pattern, $vid);
	}

  }  
  public function loadMenuLinkByUri($uri, $menu_name, $vid) {
    $menu_link = NULL;
    $query = \Drupal::entityQuery('menu_link_content');
    $query->condition('link.uri', $uri);
    $query->condition('link.title', 'stm-' . $vid);
    $query->condition('menu_name', $menu_name);
    $result = $query->range(0, 1)->execute();  
    if(!empty($result)){
      $menu_link_id = reset($result);
	  $menu_link = \Drupal::entityManager()->getStorage('menu_link_content')->load($menu_link_id);
    }  
	return $menu_link;
  }
}