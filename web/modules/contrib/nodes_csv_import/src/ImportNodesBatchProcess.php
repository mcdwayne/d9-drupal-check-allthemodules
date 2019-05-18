<?php

namespace Drupal\nodes_csv_import;

use Drupal\node\Entity\Node;

class ImportNodesBatchProcess {
	public static function NodesCSVBatchStart($import_node_type, $file_path, &$context) {
		
		if(($handle = fopen($file_path, 'r')) !== FALSE) {
			$headers = fgetcsv($handle, ',');
			$updated_count = $created_count = 0;
			while(($line = fgetcsv($handle, ',')) !== FALSE) {
				$data = array_combine($headers, $line);
				if($data['nid'] !== '') {
					$nid = (int)$data['nid'];
					$node = Node::load($nid);
					$node->title->value = $data['title'];
					$node->body->value = $data['body'];
					$node->body->format = 'full_html';
					$node->langcode->value = $data['language'];
					$node->save();
					$updated_count++;
				} else {
					$node = Node::create([
						'type' => $import_node_type,
						'title' => $data['title'],
						'body' => $data['body'],
						'format' => 'full_html',
						'langcode' => $data['language']
					]);
					$node->save();
					$created_count++;
				}
				$context['message'] = t('Nodes are processed');
				
				$context['results']['updated'] = $updated_count;
				$context['results']['created'] = $created_count;
			}
		}
	}
	
	public static function NodesCSVBatchFinished($success, $results, $operations) {
		if($success) {
			$message = \Drupal::translation()->formatPlural(
				count($results['updated']),
				'One node updated.', '@count nodes updated.'
			);
			//drupal_set_message($message);
			
			$message = \Drupal::translation()->formatPlural(
				count($results['created']),
				'One node created.', '@count nodes created.'
			);
			drupal_set_message($message);
		} else {
			drupal_set_message('finished with an error');
		}
	}
}
