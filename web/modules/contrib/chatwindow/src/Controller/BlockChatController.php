<?php

namespace Drupal\chatwindow\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\node\Entity\Node;

/**
 * Controller routines for block chat routes.
 */
class BlockChatController extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'chatwindow';
  }
	
	
	public function createblock(Request $request){
		

		//core\modules\system\src\Plugin\Condition\RequestPath.php
		
		
		$blocktitle  = $request->request->get('blocktitle');
		$blockdescription = $request->request->get('blockdescription');
		$blockbodycontent = $request->request->get('blockbodycontent');
		$contentregion = $request->request->get('contentregion');
		$currentpath = $request->request->get('currentpath');
		$recipientid = $request->request->get('recipient_id');
		$authtoken = $request->headers->get('Auth-token');
		
		

		//for home pages
		
		if($currentpath == 'node')
		{
		
			$currentpath = '<front>';

		}
		else {
			$currentpath = '/'.$currentpath;
			
		}	
		
		$messagetobot = ['clientmessage'=> 'Failure'];
		
		
		$config = \Drupal::config('chatwindow.settings');				
		$chatwindowaccesstoken = $config->get('accesstoken');
		
		if($request->getMethod() == 'POST' && !empty($authtoken))
		{
			
			$accesstoken = \Drupal::config('chatwindow.settings')->get('accesstoken');
			
			if($accesstoken == $authtoken) 
			{
				
				
				
				$block_content = BlockContent::create([
				'type' => 'basic',
				'info' => substr($blockdescription,0,254)
				]);
				$block_content->set('body', $blockbodycontent);
				// $block_content->set('region', 'content');
				$block_content->save();




				$blockconfigureid = 'created_by_chatwindow_'. time();

				$block = Block::create([
				'id' => $blockconfigureid,
				'plugin' => 'block_content:' . $block_content->uuid(),
				'region' => $contentregion,
				'provider' => 'block_content',
				'weight' => 1000,
				'theme' => \Drupal::config('system.theme')->get('default'),
				'visibility' => array('request_path' => array('id' => 'request_path' , 'pages' => $currentpath)),
				'settings' => [
					'label' => substr($blocktitle,0,254),
					'label_display' => 'visible',
					]
				]);
				$block->save();


				/*
				$theme = \Drupal::theme()->getActiveTheme()->getName();
				$system_region = system_region_list($theme, $show = REGIONS_ALL);


				$blocks = \Drupal::entityManager()
				->getStorage('block')
				->loadByProperties(array('theme' =>  $theme,'region'=>'content'));

				*/

				

				global $base_url; 

				$blockediturl = $base_url.'/block/'.$block_content->id();

				$blockconfigureurl = $base_url.'/admin/structure/block/manage/'.$blockconfigureid; 

				$response = ["clientmessage"=>"Success",
				"editurl" => $blockediturl,	
				"configureurlid" => $blockconfigureurl		
				];	

				
				return new JsonResponse($response, 200, ['Content-Type'=> 'application/json']); 

			}	
			
			
		}	

			return new Response('Failure',Response::HTTP_OK);
		
		
		
		
	}
	
	public function checknodepagepathornodeid(Request $request) {
		
		
		$authtoken = $request->headers->get('Auth-token');
		
		
		if($request->getMethod() == "POST" && !empty($authtoken))
		{
			$nodepagepathornodeid =	$request->request->get('nodepagepathornodeid');
			
				$accesstoken = \Drupal::config('chatwindow.settings')->get('accesstoken');
			
				
			
				if($accesstoken == $authtoken) 
				{
					$tempnodepagepathornodeid = (int) $nodepagepathornodeid;
					
					if(is_int($tempnodepagepathornodeid) && $tempnodepagepathornodeid != 0)
					{

							$nodepagepathornodeid = $tempnodepagepathornodeid;

				
						$values = \Drupal::entityQuery('node')->condition('nid', $nodepagepathornodeid)->execute();
						
						
						if(!empty($values))
						{

							
							
							$response = ["clientmessage"=>"Success",
								"nodepagepathornodeid" => $nodepagepathornodeid,	
									
								];	
							
							
							return new JsonResponse($response,200,['Content-Type'=> 'application/json']);
					
						}
					}// based on url
					else {
						
						global $base_url;
						
						
						$nodepagepathornodeid = str_replace($base_url,'',$nodepagepathornodeid);
						
						
						
						
						//remove query string
						$temppath = explode('?',$nodepagepathornodeid);
						
						$nodepagepathornodeid = $temppath[0];
						
						
						
						$path = \Drupal::service('path.alias_manager')->getPathByAlias($nodepagepathornodeid);
						if(preg_match('/node\/(\d+)/', $path, $matches)) {
							$nodepagepathornodeid = $matches[1];
							
							$values = \Drupal::entityQuery('node')->condition('nid', $nodepagepathornodeid)->execute();
							if(!empty($values))
							{
								
								$response = ["clientmessage"=>"Success",
								
									"nodepagepathornodeid" => $nodepagepathornodeid,	
									
								];	
								
								
								return new JsonResponse($response,200,['Content-Type'=> 'application/json']);

							}
							
							
						}
						

					}


					$response = ["clientmessage"=>"Failure",
								
									"nodepagepathornodeid" => $nodepagepathornodeid,	
									
								];	
								
								
					return new JsonResponse($response,200,['Content-Type'=> 'application/json']);
					
				}
			
		}	
		
	}	

	
	public function addtosection(Request $request)
	{
		
		
		$authtoken = $request->headers->get('Auth-token');
		
		
		if($request->getMethod() == "POST" && !empty($authtoken))
		{
			
			
				$accesstoken = \Drupal::config('chatwindow.settings')->get('accesstoken');
			
				if($accesstoken == $authtoken) 
				{
					
					
					
					
					$nodepagepathornodeid =	$request->request->get('nodepagepathornodeid');
					$sectionfieldname =	$request->request->get('sectionfieldname');
					$sectionfieldvalue = $request->request->get('sectionfieldvalue');
					
					$node = Node::load($nodepagepathornodeid);
					



					$definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->bundle());
					
					//check field exists
					if(isset($definitions[$sectionfieldname]))
					{	
						$setting = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.'.$sectionfieldname)->getSettings();
						
						$label = $definitions[$sectionfieldname]->getLabel();
					}	
					

					
					//check the field and type of field. Taxonomy field is supported
					if(isset($definitions[$sectionfieldname]) && isset($setting['target_type']) && $setting['target_type'] == 'taxonomy_term') 
					{
						
						
						
						
						
								$response = ["clientmessage"=>"Success",								
									"nodepagepathornodeid" => $nodepagepathornodeid,									
								];

						$cardinality = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load('node.'.$sectionfieldname)->getCardinality();
						$sectionfielddata = $node->get($sectionfieldname)->getValue();
						
						
						// check if the value already exists
						foreach($sectionfielddata as $eachsectionfielddata)
						{
						
							if($eachsectionfielddata['target_id'] == $sectionfieldvalue )
							{
								
								
								
								    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($sectionfieldvalue);

									$termtitle = $term->name->value;

								
								$response = ["clientmessage"=>"Failure",								
									"nodepagepathornodeid" => $nodepagepathornodeid,									
									"errormessage" => 'The section value '.$sectionfieldvalue.' ('.$termtitle.') already exists for the node id '.$nodepagepathornodeid.' ('.\Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$nodepagepathornodeid).')'								
								];
								
								
								return new JsonResponse($response,200,['Content-Type'=> 'application/json']);
							
								
								
							}	
							
							
						}	
						
						if($cardinality == -1)
						{	
							 
							 
							 if(is_array($sectionfielddata))
							 {
								 
								$sectionfielddata[] = array('target_id'=> $sectionfieldvalue );

								$node->set($sectionfieldname,$sectionfielddata);

							 }	
						}
						elseif($cardinality == 1)
						{
							
							 if(is_array($sectionfielddata))
							 {

								$node->set($sectionfieldname,array('target_id'=> $sectionfieldvalue));

							 }

						}
						else{
							
							// in case count is same then we can't override
							if(count($sectionfielddata) == $cardinality)
							{
								
									$term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($sectionfieldvalue);

									$termtitle = $term->name->value;
								
								$response = ["clientmessage"=>"Failure",								
									"nodepagepathornodeid" => $nodepagepathornodeid,									
									"errormessage" => 'The field ('.$label.' - '.$sectionfieldname.') has all the values as per the field limitation.Addtional value ('.$termtitle.') cannot be added to node id '.$nodepagepathornodeid.' ('.\Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$nodepagepathornodeid).')',									
								];

							}
							else {
								
								if(is_array($sectionfielddata))
								 {
									 
									$sectionfielddata[] = array('target_id'=> $sectionfieldvalue );

									$node->set($sectionfieldname,$sectionfielddata);

								 }	
								
								
							}	

								
							
						}	
					
					}
					else {
						
						$response = ["clientmessage"=>"Failure",								
									"nodepagepathornodeid" => $nodepagepathornodeid,									
									"errormessage" => 'Only Taxonomy reference fields are supported',									
								];
						
					}	
					

					
					$node->save();				
					
					
					return new JsonResponse($response,200,['Content-Type'=> 'application/json']);
					
					
				}

		}
		
		
		
				$response = ["clientmessage"=>"Failure",					
						"nodepagepathornodeid" => $nodepagepathornodeid,						
					];	
								
								
				return new JsonResponse($response,200,['Content-Type'=> 'application/json']);
		
		
	}	

}	