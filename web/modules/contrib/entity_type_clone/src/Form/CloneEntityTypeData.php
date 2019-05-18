<?php

namespace Drupal\entity_type_clone\Form;

use Drupal\entity_type_clone\Controller\EntityTypeCloneController;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\profile\Entity\ProfileType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CloneEntityTypeData.
 *
 * @package Drupal\entity_type_clone\Form
 */
class CloneEntityTypeData {

  /**
   * Clones a entity type field.
   *
   * @param array $data
   *   Contains the field to clone and $form_state data.
   * @param array $context
   *   A reference to the batch operation context.
   */
  public static function cloneEntityTypeField(array $data, array &$context) {
    //Get the source field name.
    $sourceFieldName = $data['field']->getName();
    //Clone the field.
    $targetFieldConfig = $data['field']->createDuplicate();
    $targetFieldConfig->set('entity_type', $data['values']['show']['entity_type']);
    $targetFieldConfig->set('bundle', $data['values']['clone_bundle_machine']);
    $targetFieldConfig->save();
    //Copy the form display
    EntityTypeCloneController::copyFieldDisplay('form', 'default', $data);
    $config_factory = \Drupal::configFactory();
    $modes = $config_factory->listAll('core.entity_view_display' . '.' . $data['values']['show']['entity_type'] . '.' . $data['values']['show']['type']);
    foreach ($modes as $mode) {
      $mode_explode = explode('.', $mode);
      $view_mode = $mode_explode[4];
      //Copy the view display
      EntityTypeCloneController::copyFieldDisplay('view', $view_mode, $data);
    }
    //Update the progress information.target_machine_name
    $context['sandbox']['progress'] ++;
    $context['sandbox']['current_item'] = $sourceFieldName;
    $context['message'] = t(
      'Field @source successfully cloned.', ['@source' => $sourceFieldName]
    );
    $context['results']['fields'][] = $sourceFieldName;
  }

  /**
   * Clones a entity type.
   *
   * @param array $values
   *   Contains the values of the form submitted via $form_state.
   * @param array $context
   *   A reference to the batch operation context.
   */
  public function cloneEntityTypeData(array $values, array &$context) {
    // Prepare the progress array.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
    }
    // Load the source entity type.
    if ($values['show']['entity_type'] == 'node') {
      $sourceContentType = NodeType::load($values['show']['type']);
      // Create the target entity type.
      $targetContentType = $sourceContentType->createDuplicate();
      $targetContentType->set('uuid', \Drupal::service('uuid')->generate());
      $targetContentType->set('name', $values['clone_bundle']);
      $targetContentType->set('type', $values['clone_bundle_machine']);
      $targetContentType->set('originalId', $values['clone_bundle_machine']);
      $targetContentType->set('description', $values['target_description']);
      $targetContentType->save();
    }
    if ($values['show']['entity_type'] == 'paragraph') {
      $sourceContentType = ParagraphsType::load($values['show']['type']);
      // Create the target entity type.
      $targetContentType = $sourceContentType->createDuplicate();
      $targetContentType->set('uuid', \Drupal::service('uuid')->generate());
      $targetContentType->set('label', $values['clone_bundle']);
      $targetContentType->set('id', $values['clone_bundle_machine']);
      $targetContentType->set('originalId', $values['clone_bundle_machine']);
      $targetContentType->set('description', $values['target_description']);
      $targetContentType->save();
    }
    if ($values['show']['entity_type'] == 'taxonomy_term') {
      $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create(array(
          'vid' => $values['clone_bundle_machine'],
          'description' => $values['target_description'],
          'name' => $values['clone_bundle'],
      ));
      $vocabulary->save();
    }
    if ($values['show']['entity_type'] == 'profile') {
      $profile_type_load = ProfileType::load($values['show']['type']);
      $type = ProfileType::create([
          'id' => $values['clone_bundle_machine'],
          'label' => $values['clone_bundle'],
          'description' => isset($values['target_description']) ? $values['target_description'] : $profile_type_load->getDescription(),
          'registration' => $profile_type_load->getRegistration(),
          'multiple' => $profile_type_load->getMultiple(),
          'roles' => $profile_type_load->getRoles(),
      ]);
      $type->save();
    }
    //Update the progress information.
    $context['sandbox']['progress'] ++;
    $context['sandbox']['current_item'] = $values['show']['type'];
    $context['message'] = t(
      'Entity type @source successfully cloned.', ['@source' => $values['show']['type']]
    );
    $context['results']['source'][] = $values['show']['type'];
    $context['results']['target'][] = $values['clone_bundle_machine'];
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
  public static function cloneEntityTypeFinishedCallback($success, array $results, array $operations) {
    //Check batch operations success.
    if ($success) {
      $message = t('"@source" content type and @fields field(s) cloned successfuly to "@target".', array(
        '@source' => $results['source'][0],
        '@fields' => count($results['fields']),
        '@target' => $results['target'][0],
        )
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    //Send the result message.
    drupal_set_message($message, 'status', TRUE);
    //Redirect to the entity type clone page.
    $response = new RedirectResponse('admin/entity-type-clone');
    $response->send();
  }

}
