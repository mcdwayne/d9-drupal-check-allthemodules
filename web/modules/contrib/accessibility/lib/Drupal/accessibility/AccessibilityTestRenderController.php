<?php

/**
 * @file
 * Definition of Drupal\accessibility\AccessibilityTestRenderController.
 */

namespace Drupal\accessibility;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRenderController;
use Drupal\entity\Entity\EntityDisplay;

/**
 * Render controller for accessibility tests.
 */
class AccessibilityTestRenderController extends EntityRenderController {

	public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    $return = array();
    if (empty($entities)) {
      return $return;
    }

    
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];
      $values = $entity->getValue();
      $extra_fields = field_info_extra_fields('accessibility_test', 'accessibility_test', 'display');
    	$quail_name_extra = $display->getComponent('quail_name');
    	if($quail_name_extra) {
		    $entity->content['quail_name'] = array(
		      '#type' => 'item',
		      '#title' => $extra_fields['quail_name']['label'],
		    	'#markup' => check_plain($values['quail_name'][0]['value']),
		      '#weight' => $quail_name_extra['weight'],
		      );
		  }
    	$severity_extra = $display->getComponent('severity');
		  if($severity_extra) {
		    $entity->content['severity'] = array(
		      '#type' => 'item',
		      '#title' => $extra_fields['severity']['label'],
		    	'#markup' => check_plain($values['severity'][0]['value']),
		      '#weight' => $severity_extra['weight'],
		      );
		  }
		  $status_extra = $display->getComponent('status');
		  if($status_extra) {
		    $entity->content['status'] = array(
		      '#type' => 'item',
		      '#title' => $extra_fields['status']['label'],
		    	'#markup' => ($values['status'][0]['value']) ? t('Active') : t('Inactive'),
		      '#weight' => $status_extra['weight'],
		      );
		  }
    }
  }
}
