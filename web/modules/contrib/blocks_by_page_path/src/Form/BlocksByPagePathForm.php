<?php
namespace Drupal\blocks_by_page_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;

/**
* Route Controller for Blocks by page path
*/
class BlocksByPagePathForm extends FormBase {
  public function getFormId() {
    return 'list_blocks_by_page_path';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form["path"] = array("#type" => "textfield", 
						  "#title" => t("Name"),
						  "#description" => t("Drupal path ex: node/29 or content/services."),
						 );
	$form["submit"] = array("#type" => "submit", 
					"#value" => t("Find"), 
					"#ajax" => array("callback" => array($this, "_get_assigned_blocks"),
								"message" => t("Please wait..."),
								"event" => "click"),
					"#suffix" => '<br><br><div class="output-wrapper"></div>');
	return $form;
  }
  /**
	* Ajax callback to find the blocks which are assigned to particular page.
  */
  public function _get_assigned_blocks(array $form, FormStateInterface $form_state){
	$response = new AjaxResponse();
	// valid drupal paths
	$input_path = $form_state->getValue("path");
	if(!empty($input_path)){
	  $pathValidator = \Drupal::pathValidator()->getUrlIfValid($input_path);
	}else{
	  $input_path = t("empty");
	  $pathValidator = FALSE;
	}
	if($pathValidator){
		$db = \Drupal::database();
		$data = $db->select("config", "c")
				->fields("c")
				->condition("c.name", "block.block%", "LIKE")
				->execute()
				->fetchAll();
		// find the blocks
		$headers = array("Block Name", "Default Theme", "Region", "Configure");
		$rows = array();
		foreach($data as $record){
			$block_details = unserialize($record->data);
			// Conditions
			// 1. Make sure the block is enabled in some regions
			// 2. Make sure the block is assigned to particular page and "Show for the listed pages" settings enables
			if($block_details['region'] != -1){
				if(isset($block_details['visibility']['request_path'])){
					// convert to path alias
					$input_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath("/".$input_path, 'en');
					$pages = !empty($block_details['visibility']['request_path']['pages'])?explode("\n",$block_details['visibility']['request_path']['pages']):array();
					// Trim the data.
				    $pages_included = array();
				    foreach ($pages as $page) {
					  $pages_t = trim($page);
					  $input_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath("/".$pages_t, 'en');
					  $pages_included[] = $input_path_alias;
				    }
					//if($block_details["id"] == "views_block__content_recent_block_1"){
						//echo '<pre>'; print_r($pages_included); exit;
					//}
				}
				if((isset($block_details['visibility']['request_path']) && empty($block_details['visibility']['request_path']['negate']) && in_array($input_path_alias, $pages_included)) || empty($block_details['visibility'])){
					$block_title = !empty($block_details["settings"]["label"])?$block_details["settings"]["label"]:$record->name;
					// Get route name.
					$path = "admin/structure/block/manage/".$block_details["id"];
					$url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
					$route_name = $url_object->getRouteName();
					// Create URL.
					$url = Url::fromRoute($route_name, array("block" => $block_details["id"]));
					$configure_link = \Drupal::l(t('Configure'), $url);
					
					$rows[]['data'] = array($block_title,
											$block_details["theme"],
											$block_details["region"],
											$configure_link);
				}
			}
		}
		$table = array("#type" => "table",
						"#header" => $headers,
						"#rows" => $rows);
		$output = drupal_render($table);
	}else{
		$output = t("Invalid Path '".$input_path."'");
	}
	// build blocks list
	$response->addCommand(new HtmlCommand('.output-wrapper', $output));
	return $response;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
	/*Nothing to validate on this form*/ 
  } 
  public function submitForm(array &$form, FormStateInterface $form_state) {
	/*Nothing to submit on this form*/
  }
}