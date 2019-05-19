<?php

namespace Drupal\simply_signups\Form\Confirm;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class SimplySignupsNodesRemoveAllFieldsConfirmForm extends ConfirmFormBase {

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
    $path = \Drupal::service('path.current')->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $node = Node::load($nid);
    $isValidNode = (isset($node)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $id = $node->id();
    $db = \Drupal::database();
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->condition('nid', $id, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $title = $row->name;
    }
    $this->id = $title;
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-nodes-remove-confirm-form', 'simply-signups-form'],
    ];
    $form['title'] = [
      '#type' => 'hidden',
      '#value' => $title,
    ];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $db = \Drupal::database();
    $db->delete('simply_signups_fields')
      ->condition('nid', $values['nid'], '=')
      ->execute();
    $form_state->setRedirect('simply_signups.nodes.fields', ['node' => $values['nid']]);
    drupal_set_message($this->t('Successfully removed all fields.'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "simply_signups_nodes_remove_all_fields_confirm_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to remove all fields?');
  }

}
