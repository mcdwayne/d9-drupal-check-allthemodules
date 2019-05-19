<?php

namespace Drupal\track_da_files\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'TDF Generic files' formatter.
 *
 * @FieldFormatter(
 *   id = "track_da_files",
 *   label = @Translation("TDF Generic files"),
 *   field_types = {
 *     "file"
 *   },
 * )
 */

class TrackDaFilesFormatter extends FileFormatterBase {
 /**
   * {@inheritdoc}
   */
	public function viewElements(FieldItemListInterface $files, $langcode) {

		$elements = array();

    foreach ($this->getEntitiesToView($files, $langcode) as $delta => $file) {

      $entity = $files->getEntity();
      $type = $entity->getEntityTypeId();
      $entity_bundle = $entity->getType();
      $id = $entity->id();
      $mime_type = $file->getMimeType();
     	$filesize = $file->getSize();
    		$filename = $file->getFilename();
    		$url = track_da_files_create_url($file->getFileUri());
    		$options['attributes']['type'] = $mime_type . '; length=' . $filesize;
      $options['query']['file'] = '1';
      $item = $file->_referringItem;

		  if (isset($type)) {
		    $options['query']['type'] = $type;
		  }

		  if (isset($id)) {
		    $options['query']['id'] = $id;
		  }

		  $text = isset($item->description) ? $item->description : $filename;
      $link = \Drupal::l($text, Url::fromUri($url, $options));

      $elements[$delta] = array(
        '#markup' => $link,
        '#file' => $file,
        '#description' => $file->description,
        '#entity_type' =>  $type,
        '#entity_bundle' =>  $entity_bundle,
      	'#entity_id' => $id,
      ); 

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += array('#attributes' => array());
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }
    if (!empty($elements)) {
      $elements['#attached'] = array(
        'library' => array('file/drupal.file.formatter.generic'),
      );
    }

    return $elements;

  }

}
