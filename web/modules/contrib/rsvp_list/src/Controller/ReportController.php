<?php
/**
	* @file
	* Contains \Drupal\rsvplist\Controller\ReportController;
	*/

namespace Drupal\rsvplist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

/**
  * Controller for RSVP List Report
  */
 class ReportController extends ControllerBase {

 	/**
 	  * Get all RSVPs for all nodes.
 	  * @return array
 	  */
 	   protected function load() {
      $select = Database::getConnection()->select('rsvplist', 'r');
      // Join the user table, so we can get the Entry Creator's name.
      $select->join('users_field_data', 'u', 'r.uid = u.uid');	
      // Join the node table so we can get the event's name.
      $select->join('node_field_data', 'n', 'r.nid = n.nid');	
     // Select the specific fields for the Outputs.
      $select->addField('u', 'name', 'username');
      $select->addField('n', 'title');
      $select->addField('r', 'mail');

      $entries = $select->execute()->fetchAll(\PDO::FETCH_ASSOC);
      return $entries;
 	  }

 	  /**
 	    *  Create the Report page.
 	    *
 	    * @return array
 	    * Render array for report output.
 	    *
 	    */
 	   public function report() {
      $content = array();

      $content['message'] = array( 
       '#markup' => $this->t('Below is a list of all Event RSVPs including username email address and the name of the event they will be attending.'));
        $header = array( t('Name'), t('Event'), t('Email'));
      $rows = array();

      foreach ($entires = $this->load() as $entry ) {
      	// Sanitize each entry
      	$rows[] = array_map('Drupal\Component\Utility\SafeMarkup::checkPlain', $entry);
      }

      $content['table'] = array(
         '#type' => 'table',
         '#header' => $header,
         '#rows' => $rows,
         '#empty' => t('No Entires Available.'),
      );

      // Don't Catche this page.
     $content['#cache']['max-age'] = 0;
     return $content;

 	   }



 }
