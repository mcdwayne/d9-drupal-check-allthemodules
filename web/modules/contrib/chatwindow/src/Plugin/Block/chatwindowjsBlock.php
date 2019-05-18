<?php

namespace Drupal\chatwindow\Plugin\Block;

use Drupal\Core\Block\BlockBase;



/**
 * Provides a Chat window using javascript.
 *
 * @Block(
 *   id = "chatwindowjs",
 *   admin_label = @Translation("Creates a chat window"),
 *   category = @Translation("Chat Window")
 * )
 */
class chatwindowjsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
	  
	  
	 $module_handler = \Drupal::service('module_handler');
	 $modulepath = $module_handler->getModule('chatwindow')->getPath();
	 
	 $config = \Drupal::config('chatwindow.settings');
	 
	 $dontaddcssforbot = $config->get('dontaddcssforbot');
	 
	 //if the value is not set or equal to 0 then make the data as 0
	 if(empty($dontaddcssforbot)) {
			
			$dontaddcssforbot = 0;
	 }	 
		 
	 
	 global $base_url; 
 

	 
	  
    return [      
	  '#theme' => 'chatwindowjsdata',
	  '#modulepath' => $modulepath,
	  '#baseurl' => $base_url,
	  '#dontaddcssforbot' => $dontaddcssforbot,
    ];
  }

}