<?php

/**
 * @file
 * Contains \Drupal\klaviyo_subscription\Controller.
 */
 
namespace Drupal\klaviyo_subscription\Controller;
 
use Drupal\Core\Url;
/**
 * Provides a listing of Klaviyo lists.
 */
class klaviyoListAddController {

  /**
   * {@inheritdoc}
   */
  public function content() {	
	module_load_include('inc', 'klaviyo_subscription', 'includes/klaviyo_subscription');	
	$kl_drupal_lists = klaviyo_subscription_get_drupal_list();
    $kl_lists = klaviyo_subscription_get_klaviyo_list();

    foreach($kl_drupal_lists as $list_key => $list) {
		$result[$list_key]['list_name'] = $list;	
		$result[$list_key]['kl_id'] = $list_key;	
		$url = Url::fromRoute('klaviyo_subscription.kl_config_form', ['klid' => $list_key]);
		$result[$list_key]['edit'] = \Drupal::l('Edit', $url);	
	}

    $header = [
      ['data' => t('List Name'), 'field' => 't.list_name'],
      ['data' => t('Klaviyo ID'), 'field' => 't.kl_id'],
      ['data' => t('Edit'), 'field' => 't.edit'],
    ];

    $rows = array();
    foreach ($result as $row) {
      $rows[] = array('data' => (array) $row);
    }

    $build['test'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

  return $build;
  }

}