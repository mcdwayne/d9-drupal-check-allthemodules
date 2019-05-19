<?php
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 15/01/2016
 * Time: 22:57
 */

namespace Drupal\subsite;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;


interface SubsiteManagerInterface {
  /**
   * Builds the common elements of the subsite form for the node form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\NodeInterface $node
   *   The node whose form is being viewed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account viewing the form.
   * @param bool $collapsed
   *   If TRUE, the fieldset starts out collapsed.
   *
   * @return array
   *   The form structure, with the book elements added.
   */
  public function addFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account, $collapsed = TRUE);
  public function validateFormElements(array $form, FormStateInterface $form_state, NodeInterface $node, AccountInterface $account);
  public function getFormValues(array $form, FormStateInterface $form_state);

  public function blockViewAlter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block);
  public function nodeViewAlter(array &$build, EntityInterface $node, EntityViewDisplayInterface $display);
  public function pageAttachmentsAlter(array &$attachments);

  /**
   * @param \Drupal\node\Entity\Node $node
   */
  public function getSubsiteField(Node $node);
}