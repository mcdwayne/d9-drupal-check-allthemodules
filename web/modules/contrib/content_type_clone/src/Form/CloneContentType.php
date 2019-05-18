<?php
namespace Drupal\content_type_clone\Form;

use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\content_type_clone\Helpers\ContentTypeCloneHelper;

/**
 * Class CloneContentType.
 *
 * @package Drupal\content_type_clone\Form
 */
class CloneContentType {

  /**
   * Clones a content type.
   *
   * @param array $values
   *   Contains the values of the form submitted via $form_state.
   * @param array $context
   *   A reference to the batch operation context.
   */
  public static function cloneContentType(array $values, array &$context) {
    //Prepare the progress array.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }

    //Load the source content type.
    $sourceContentType = NodeType::load($values['source_machine_name']);

    //Create the target content type.
    $targetContentType = $sourceContentType->createDuplicate();
    $targetContentType->set('uuid', \Drupal::service('uuid')->generate());
    $targetContentType->set('name', $values['target_name']);
    $targetContentType->set('type', $values['target_machine_name']);
    $targetContentType->set('originalId', $values['target_machine_name']);
    $targetContentType->set('description', $values['target_description']);
    $targetContentType->save();
  
    //Update the progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_item'] = $values['source_machine_name'];    
    $context['message'] = t(
      'Content type @source successfully cloned.',
      ['@source' => $values['source_machine_name']]
    );
    $context['results']['source'][] = $values['source_machine_name'];
    $context['results']['target'][] = $values['target_machine_name'];
  }

  /**
   * Clones a content type field.
   *
   * @param array $data
   *   Contains the field to clone and $form_state data.
   * @param array $context
   *   A reference to the batch operation context.
   */
  public static function cloneContentTypeField(array $data, array &$context) {
    //Get the source field name.
    $sourceFieldName = $data['field']->getName();

    //Clone the field.
    $targetFieldConfig = $data['field']->createDuplicate();
    $targetFieldConfig->set('entity_type', 'node');
    $targetFieldConfig->set('bundle', $data['values']['target_machine_name']);
    $targetFieldConfig->save();

    //Copy the form display
    ContentTypeCloneHelper::copyFieldDisplay('form', 'default', $data);

    //Copy the view display
    ContentTypeCloneHelper::copyFieldDisplay('view', 'default', $data);

    //Update the progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_item'] = $sourceFieldName;
    $context['message'] = t(
      'Field @source successfully cloned.',
      ['@source' => $sourceFieldName]
    );
    $context['results']['fields'][] = $sourceFieldName;
  }

  /**
   * Clones the nodes of a content type.
   *
   * @param string $nid
   *   The id of the node to clone.
   * @param array $values
   *   Contains the values of the form submitted via $form_state.
   * @param array $context
   *   A reference to the batch operation context.
   */
  public static function copyContentTypeNode($nid, array $values, array &$context) {
    //Get the source node.
    $sourceNode = Node::load($nid);

    //Get the source node name.
    $sourceNodeName = $sourceNode->getTitle();

    //If the node has a title.
    if (!empty($sourceNodeName)) { 
      //Clone the given node
      $targetNode = $sourceNode->createDuplicate();
      $targetNode->set('type', $values['target_machine_name']);

      //Set the title with token if required
      trim($values['title_pattern']);
      if (\Drupal::moduleHandler()->moduleExists('token') && !empty($values['title_pattern'])) {
        $targetNode->set('title', \Drupal::token()->replace(
            $values['title_pattern'],
              array('node' => $targetNode)
            )
        );
      }

      //Save the node.
      $targetNode->save();

      //Delete the node if needed.
      if ((int)$values['delete_source_nodes'] == 1) {
        $sourceNode->delete();
      }
    }
    
    //Update the progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_item'] = $sourceNodeName;
    $context['message'] = t(
      'Node @source successfully cloned.',
      ['@source' => $sourceNodeName]
    );
    $context['results']['nodes'][] = $sourceNodeName;
  }

  /**
   * Handles results after the batch operations.
   *
   * @param bool $success
   *   The status of the batch process.
   * @param array $results
   *   Contains the results of the batch operation.
   * @param array $operations
   *   The array of operations processed by the batch.
   */
  public static function cloneContentTypeFinishedCallback($success, array $results, array $operations) {
    //Check batch operations success.
    if ($success) {
      $message = t('"@source" content type and @fields field(s) cloned successfuly to "@target" with @nodes node(s).', 
        array(
          '@source' => $results['source'][0], 
          '@fields' => count($results['fields']),
          '@target' => $results['target'][0], 
          '@nodes' => count($results['nodes']), 
        )
      );
    }
    else {
      $message = t('Finished with an error.');
    }

    //Send the result message.
    drupal_set_message($message, 'status', TRUE);

    //Redirect to the content type list
    $response = new RedirectResponse('admin/structure/types');
    $response->send();
  }
}