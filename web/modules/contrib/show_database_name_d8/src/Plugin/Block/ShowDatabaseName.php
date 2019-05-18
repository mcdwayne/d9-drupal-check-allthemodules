<?php
/**
 * @file
 * Contains \Drupal\show_database_name\Plugin\Block\ShowDatabaseName.
 */
namespace Drupal\show_database_name\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;

/**
 * Provides a 'Show Database Name' block.
 *
 * @Block(
 *   id = "show_database_name_block",
 *   admin_label = @Translation("Database Host & Name"),
 *   category = @Translation("Custom block")
 * )
 */

class ShowDatabaseName extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	if (\Drupal::currentUser()->hasPermission('access database information')) {
	  	$database_details = Database::getConnectionInfo('default');
	    return array(
	      '#type' => 'markup',
	      '#markup' => t('Host: @host | DB: @db_name', array('@host' => $database_details['default']['host'],'@db_name' => $database_details['default']['database'])),
	    );
	}
  }
}