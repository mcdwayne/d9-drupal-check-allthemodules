<?php
/**
 * @file
 * Contains \Drupal\signed_nodes\Form\SignedNodesForm.
 **/
   
namespace Drupal\signed_nodes\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo: Do the deletion.
    if ($form_state->getValue('confirm') == 1) {
      $snid = $this->id;//$form_state->getValue('snid');
      $nid = signed_node_get_nid($snid);
      signed_nodes_delete_agreement($snid);
      // Logs a notice
      $message = t('Deleted signed node agreemet for Node ID = %name with all its user signed agreements', array('%name' => $nid));
      \Drupal::logger('signed_nodes')->notice($message);

      drupal_set_message(t('The node agreement with all its signed agreements from users for Node ID = %name was deleted.', array('%name' => $nid)));
      $form_state->setRedirect('signed_nodes.adminlistpage');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('signed_nodes.adminlistpage');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    //return t('Do you want to delete %id?', ['%id' => $this->id]);
    return t('Are you sure you want to delete node agreement for Node ID = %title ? All nodes signed by users will be removed.', array('%title' => signed_node_get_nid($this->id)));
  }

}