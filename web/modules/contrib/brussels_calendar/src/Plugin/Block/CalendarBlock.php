<?php

namespace Drupal\brussels_calendar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

use Drupal\views\Views;

use Drupal\node\Entity\Node;

use Drupal\Core\Url; 

use Drupal\Core\Cache\Cache;

/**
 * Provides a 'CalendarBlock' block.
 *
 * @Block(
 *  id = "calendar_block",
 *  admin_label = @Translation("Brussels Calendar Block"),
 * )
 */
class CalendarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  
  private $acceptedFieldTypes=[
  	'timestamp',
  	'datetime',
  ];
  
  private $customCacheTags=['brussels_calendar'];
  /**
   * Constructs a new CalendarBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $database
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view' => $this->t(''),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
  	
  	$viewsList=[];
  	
  	foreach(Views::getAllViews() as $view){
  		$viewsList[$view->id()]=$view->label();
  	}
  	
  	
    $form['view'] = [
      '#type' => 'select',
      '#options' => $viewsList,
      '#title' => $this->t('View'),
      '#description' => $this->t('Choose the machine name of the view from witch the list of nodes will be retrieved.'),
      '#default_value' => $this->configuration['view'],
      '#weight' => '4',
      '#required'=> true,
    ];
    
    $calendarId=$this->configuration['calendar_id'];
    if(empty($calendarId)){
    	$calendarId='brussels_calendar';
    }
    
    $form['calendar_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HTML element ID'),
      '#description' => $this->t('Enter the ID of the calendar div to be used.'),
      '#default_value' => $calendarId,
      '#weight' => '5',
      '#required'=> true,
    ];
    
    $categoryList=[
    	''=>'None'
    ];
    
    $vids =  \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();
    
	foreach ($vids as $vid) {
	    $container = \Drupal::getContainer();
	    
	    //Machine name.
	    $categoryList[$vid->id()]=$vid->id();
	}
	
	asort($categoryList);
	
    $form['category_taxonomy'] = [
      '#type' => 'select',
      '#options' => $categoryList,
      '#title' => $this->t('Category taxonomy (machine name)'),
      '#description' => $this->t('There should be a field \'color\' in the taxonomy with a CSS value. Examples: red, #12544. If "None" chosen, categories disabled.'),
      '#default_value' => $this->configuration['category_taxonomy'],
      '#weight' => '7',
      '#required'=> false,
    ];
    
    $form['category_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Category field (machine name)'),
      '#description' => $this->t('Enter the machine name of the category field in de content type.'),
      '#default_value' => $this->configuration['category_field'],
      '#weight' => '8',
      '#required'=> false,
    ];
    
    $form['category_color_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color field of the taxonomy (machine name)'),
      '#description' => $this->t('Enter the machine name of the color field in the taxonomy structure.'),
      '#default_value' => $this->configuration['category_color_field'],
      '#weight' => '9',
      '#required'=> false,
    ];
    
    $form['legend_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Legend'),
      '#description' => $this->t('Only possible if categories enabled. Shows legend of colors at bottom of existing events.'),
      '#default_value' => $this->configuration['legend_enabled'],
      '#weight' => '9',
      '#required'=> false,
    ];
    
    
    $timestampField=$this->configuration['timestamp_field'];
    
    if(empty($timestampField)){
    	$timestampField='created';
    }
    
    $timestampFieldDescription="
    	Machine name. Enter the field of content type used to get the UNIX timestamp value. 
    	This should be a datetime field. The first array value will be used.
    ";
    
    $form['timestamp_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timestamp field'),
      '#description' => $this->t($timestampFieldDescription),
      '#default_value' => $timestampField,
      '#weight' => '10',
      '#required'=> true,
    ];
    
    $timestampFieldIndex=intval($this->configuration['timestamp_field_index']);
    
    if($timestampFieldIndex<0){
    	$timestampFieldIndex=0;
    }
    
    $form['timestamp_field_index'] = [
      '#type' => 'number',
      '#title' => $this->t('Timestamp field index'),
      '#description' => $this->t('If multiple values, specify index.'),
      '#default_value' => 0,
      '#weight' => '10',
      '#required'=> true,
      '#min'=> $timestampFieldIndex,
    ];
    
    $timestampEndField=$this->configuration['timestamp_end_field'];
    if(empty($timestampEndField)){
    	$timestampEndField='';
    }
    
    $form['timestamp_end_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timestamp end field'),
      '#description' => $this->t("Machine name. Same as 'Timestamp field', but optional. The value should be greater then 'Timestamp field'. For ranges as end date (spread over multiple days)."),
      '#default_value' => $timestampEndField,
      '#weight' => '11',
      '#required'=> false,
    ];
    
    $timestampEndFieldIndex=intval($this->configuration['timestamp_end_field_index']);
    
    if($timestampEndFieldIndex<0){
    	$timestampEndFieldIndex=0;
    }
    
    $form['timestamp_end_field_index'] = [
      '#type' => 'number',
      '#title' => $this->t('Timestamp end field index'),
      '#description' => $this->t('If multiple values, specify index. For ranges. Example: 24/11/2017 to 26/11/2017.'),
      '#default_value' => $timestampEndFieldIndex,
      '#weight' => '11',
      '#required'=> true,
      '#min'=> 0,
    ];
    
    $javascriptDayFunction=$this->configuration['javascript_day_function'];
    
    $form['javascript_day_function'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Javascript day function'),
      '#description' => $this->t('Enter a javascript function name to be executed on day click. The function must be globally defined and will receive the day momentjs object as parameter. Can be useful for statistics.'),
      '#default_value' => $javascriptDayFunction,
      '#weight' => '15',
      '#required'=> false,
    ];
    
    $javascriptEventFunction=$this->configuration['javascript_event_function'];
    $form['javascript_event_function'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Javascript event function'),
      '#description' => $this->t('Enter a javascript function name to be executed on event click. The function must be globally defined and will receive the node as associative array (id and eventName). The user will not be forwarded to the node page.'),
      '#default_value' => $javascriptEventFunction,
      '#weight' => '15',
      '#required'=> false,
    ];
    
    return $form;
  }


  public function blockValidate($form, FormStateInterface $form_state) {
  	$viewName=trim($form_state->getValue('view'));
  	$view = Views::getView($viewName);
  	if(empty($view)){
  		$form_state->setErrorByName('view', $this->t("View doesn't exist"));
  	}
  	
  	$timestampField = trim($form_state->getValue('timestamp_field'));
  	$timestampEndField = trim($form_state->getValue('timestamp_end_field'));
  	
  	if($timestampField==$timestampEndField){
  		$form_state->setErrorByName($timestampFieldKey, $this->t('"Timestamp end field" cannot be the same as "timestamp field".'));
  	}
  	
  	// First node for validation.
  	$node = null;
  	
  	if(!empty($view)){
  		$view->execute();
  		if(count($view->result)>0){
  			$node = Node::load($view->result[0]->nid);
  			
  			$timestampField=null;
  			
  			foreach([
  				'timestamp_field',
  				'timestamp_end_field',
  				] as $timestampFieldKey){
  					
  				$timestampFieldValue=$form_state->getValue($timestampFieldKey);
  			
  				
  				//Optional?
  				if(in_array($timestampFieldKey, [
  					'timestamp_end_field'
  				])){
  					
  					if(empty($timestampFieldValue)){
  						continue;
  					}
  				}
	  				
	  			try{
	  				// Unknown?
		  			$timestampField=$node->get($timestampFieldValue);
				}catch(\InvalidArgumentException $exception){
					$form_state->setErrorByName($timestampFieldKey, $exception->getMessage());
				}
				if(!empty($timestampField)){
					
					
					$fieldType=$timestampField->getFieldDefinition()->getType();
					if(!in_array($fieldType, $this->acceptedFieldTypes)){
						$form_state->setErrorByName($timestampFieldKey, $this->t('Field type '.$fieldType.' not supported by module.'));
						return;
					}
 
					$timestampFieldValue=$timestampField->getValue();
				
					$eventDateValidation=$this->validateEventDate(
						$timestampField->getValue(), 
						$form_state->getValue($timestampFieldKey.'_index')
					);
					
					if(array_key_exists('error', $eventDateValidation)){
						$form_state->setErrorByName($timestampFieldKey, $eventDateValidation['error']);
					}
				}
  			}
			  	
  		}
  	}
  	
  	$categoryTaxonomy = trim($form_state->getValue('category_taxonomy'));
  	$categoryField = trim($form_state->getValue('category_field'));
  	$categoryColorField = trim($form_state->getValue('category_color_field'));
  	$legendEnabled = intval($form_state->getValue('legend_enabled'))==1;
  	dpm('legend int '.intval($form_state->getValue('legend_enabled')));
  	dpm('legend boolean '.intval($form_state->getValue('legend_enabled'))==1);
  	
  	$categoryFieldsFilled=intval(!empty($categoryTaxonomy))+intval(!empty($categoryField))+intval(!empty($categoryColorField));
  	
  	if($categoryFieldsFilled!=0&&$categoryFieldsFilled!=3){
  		$errorMessage=$this->t('Fields "Category taxonomy", "Category field" and "Category color field" should be together filled in or left empty.');
  		
  		$form_state->setErrorByName('category_taxonomy', $errorMessage);
  		$form_state->setErrorByName('category_field', $errorMessage);
  		$form_state->setErrorByName('category_color_field', $errorMessage);
  	}
  	
  	if($categoryFieldsFilled==3){
  		
  		// Check if the taxonomy has a color field.
  		$vids =  \Drupal\taxonomy\Entity\Vocabulary::loadMultiple();

	    $container = \Drupal::getContainer();
	    $terms = $container->get('entity.manager')->getStorage('taxonomy_term')->loadTree($categoryTaxonomy);
	    //For value: term->name
	    
	    if(count($terms)==0){
	    	$form_state->setErrorByName('category_taxonomy', $this->t('Category taxonomy not found or no entries.'));
	    }else if(!empty($node)){
	    	// TODO Validate category_color_field
    		try{
				
				$term = \Drupal\taxonomy\Entity\Term::load($node->get($categoryField)->target_id);
				
				if(!empty($term)){
					// Human name.
					// dpm($term->getName());
					
					// CSS color.
					$color=$term->get($categoryColorField)->getValue()[0]['value'];
				}
			}catch(\InvalidArgumentException $exception){
				$form_state->setErrorByName('category_color_field', $this->t($exception->getMessage()));
			}
	    }
	}else if($legendEnabled){
  		$form_state->setErrorByName('legend_enabled', $this->t('Categories must be enabled to activate legend.'));
	}
  }
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view'] = trim($form_state->getValue('view'));
    $this->configuration['calendar_id'] = trim($form_state->getValue('calendar_id'));
    
    $this->configuration['category_taxonomy'] = trim($form_state->getValue('category_taxonomy'));
    $this->configuration['category_field'] = trim($form_state->getValue('category_field'));
    $this->configuration['category_color_field'] = trim($form_state->getValue('category_color_field'));
    $this->configuration['legend_enabled'] = intval($form_state->getValue('legend_enabled'))==1;
    
    $this->configuration['timestamp_field'] = trim($form_state->getValue('timestamp_field'));
    $this->configuration['timestamp_field_index'] = intval($form_state->getValue('timestamp_field_index'));
    
    $this->configuration['timestamp_end_field'] = trim($form_state->getValue('timestamp_end_field'));
    $this->configuration['timestamp_end_field_index'] = intval($form_state->getValue('timestamp_end_field_index'));
    
    $this->configuration['javascript_day_function'] = trim($form_state->getValue('javascript_day_function'));
    $this->configuration['javascript_event_function'] = trim($form_state->getValue('javascript_event_function'));
    
    //Delete cache.
    \Drupal::service('cache_tags.invalidator')->invalidateTags($this->customCacheTags);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
  	$config = $this->getConfiguration();
  	
  	$viewName='';
  	if (!empty($config['view'])) {
      $viewName = $config['view'];
    }
    
    $groupEnabled=!empty($config['category_taxonomy'])&&
		!empty($config['category_field'])&&
		!empty($config['category_color_field']);
	
	$build = [];
    
    $build['#theme']='brussels_calendar';
    
	$build['#attached']['library'][] = 'brussels_calendar/brussels_calendar';
	$build['#attached']['library'][] = 'brussels_calendar/momentjs';
	
	$build['#calendar_id']=$config['calendar_id'];
	
	$build['#settings']=json_encode([
		'no_events'=>$this->t("No events"),
		'legend_enabled'=>$groupEnabled&&$config['legend_enabled'],
	]);
	
	//No default needed, as already set in .module file.
	if(!empty($config['javascript_day_function'])){
		$build['#javascript_day_function']=$config['javascript_day_function'];
	}
	
	if(!empty($config['javascript_event_function'])){
		$build['#javascript_event_function']=$config['javascript_event_function'];
	}
	
	$events=[];
	
	$view = Views::getView($viewName);
	
	if(!empty($view)){
		$view->execute();
		
		$debugHandled=false;
		// Validate (dates valid?) and build list.
		foreach($view->result as $row){
			$node = Node::load($row->nid);
			
			// Epoch values.
			$date=null;
			$dateEnd=null;
			
			
			$date=$this->validateEventDate(
				$node->get($config['timestamp_field'])->getValue(),
				$config['timestamp_field_index']
			)['datetime'];
			
			$dateEnd=$this->validateEventDate(
				$node->get($config['timestamp_end_field'])->getValue(),
				$config['timestamp_end_field_index']
			)['datetime'];
			
			// Skip if no valid date.
			if(empty($date)){
				continue;
			}
			$date=$date->format('U');
			
			if(!empty($dateEnd)){
				$dateEnd=$dateEnd->format('U');
				
				//Invalid or no range?
				if(intval($dateEnd)<=intval($date)){
					$dateEnd=null;
				}
			}
			
			// TODO: Code to get the rendered view for custom view fields. Not working. Now title used.
			
			/*
			$view->getDisplay();
			
			//dpm($row->_entity->get('An example view field')->getValue());
			try{
				// dpm($row->_entity->get('Custom text'));
				
				// dpm($view->field['nothing']->options['alter']['text']);
				//foreach ($view->field as $id => $field) {
					if($debugHandled){
						break;
					}
					// field_alias
					
						// Drupal\views\Plugin\views\field\Custom
						//dpm($field->render($row));
						dpm($view->field['nothing']->render($row));
						dpm($view->field['nothing']->renderText(null));
						$debugHandled=true;
					
					//dpm($id);
				//}
				
			}catch(InvalidArgumentException $exception){
				
			}
			*/
			
			// Color for calendar entry.
			$color = 'default';
			$calendar = $this->t('Event');
			
			if($groupEnabled){
				//Try to get field, or continue with default color if not exists.
				try{
					
					$term = \Drupal\taxonomy\Entity\Term::load($node->get($config['category_field'])->target_id);
					
					if(!empty($term)){
						// Human name.
						$calendar = $term->getName();
						
						// CSS color.
						$color = $term->get($config['category_color_field'])->getValue()[0]['value'];
					}
				}catch(\InvalidArgumentException $exception){
					
				}
			}
			
			$events[] = [
				'eventName'=>$node->getTitle(),
				'calendar'=>$calendar,
				'color'=>$color,
				'date'=>$date,
				'date_end'=>$dateEnd,
				'url'=> Url::fromRoute('entity.node.canonical', ['node' => $row->nid])->toString(),
			];
		}
	}
	
	$build['#events']=json_encode($events, true);
	
    return $build;
  }
  
	public function getCacheTags() {
		$cacheTags=$this->customCacheTags;
		
		$cacheTags=array_merge($cacheTags, ['config:views.view.'.$this->configuration['view']]);
		
		// Overhead:
		/*
		if(!empty($this->configuration['view'])){
			$view = Views::getView($this->configuration['view']);
			if(!empty($view)){
				$cacheTags=array_merge($cacheTags, $view->getCacheTags());
			}
		}
		*/
		
		if(!empty($this->configuration['category_taxonomy'])){
			$container = \Drupal::getContainer();
			
			$vid = \Drupal\taxonomy\Entity\Vocabulary::load($this->configuration['category_taxonomy']);
	    	
	    	// Not working as general tag for all underlining terms.
	    	$cacheTags=array_merge($cacheTags, $vid->getCacheTags());
			
	    	$container = \Drupal::getContainer();
	    	
	    	$terms = $container->get('entity.manager')->getStorage('taxonomy_term')->loadTree($this->configuration['category_taxonomy'], 0, null, true);
	    	foreach($terms as $term){
	    		// taxonomy_term:3, ...
	    		$cacheTags=array_merge($cacheTags, $term->getCacheTags());
	    	}
	    	
		}
		
		// dpm($cacheTags);
		
		return Cache::mergeTags(parent::getCacheTags(), $cacheTags);
	}

	// Returns array with DateTime instance (success) and error associative array.
	private function validateEventDate($timestampFieldValue, $timestampFieldIndex) {
			 
		if(count($timestampFieldValue)==0){
			return [
				'datetime'=>null,
				'error'=>$this->t('Has no value (based on first item in view)'),
			];
		}
		
		if(count($timestampFieldValue)<$timestampFieldIndex){
			return [
				'datetime'=>null,
				'error'=> $this->t('Index out of range (based on first item in view)')
			];
		}
		
		if(!array_key_exists('value', $timestampFieldValue[$timestampFieldIndex])){
			return [
				'datetime'=>null,
				'error'=> $this->t('Has no value field (based on first item in view)')
			];
		}
		
		//Timestamp?
		$value=\DateTime::createFromFormat('U', $timestampFieldValue[$timestampFieldIndex]['value']);
			
		//Date?
		if($value==false){
			$value=\DateTime::createFromFormat('Y-m-d', $timestampFieldValue[$timestampFieldIndex]['value']);
		}
		
		if($value==false){
			return [
				'datetime'=>null,
				'error'=>$timestampFieldKey, $this->t('Value invalid as timestamp and datetime: '.$timestampFieldValue[$timestampFieldIndex]['value']),
			];
		}
		
		return [
			'datetime'=>$value,
			'debug'=>$timestampFieldValue[$timestampFieldIndex]['value'],
		];
	
	}
}
