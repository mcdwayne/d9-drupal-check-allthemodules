<?php

namespace Drupal\landingpage_clone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class LandingPageCloneForm.
 *
 * @package Drupal\landingpage_clone\Form
 */
class LandingPageCloneForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landingpage_clone_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clone'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = \Drupal::routeMatch()->getParameter('node');
    $targets = $node->get('field_landingpage_paragraphs')->getValue();
    $paragraphs = array();
    foreach ($targets as $target) {
      $paragraph_storage = \Drupal::entityManager()->getStorage('paragraph');
      $paragraph = $paragraph_storage->load($target['target_id']);
      $cloned_paragraph = $paragraph->createDuplicate();
      $cloned_paragraph->save();
      $paragraphs[] = array(
        'target_id' => $cloned_paragraph->id(),
        'target_revision_id' => $cloned_paragraph->getRevisionId(),
      );
    }
    $new_node = Node::create(array(
      'type' => 'landingpage',
      'title' => 'Clone of ' . $node->label(),
      'langcode' => 'en',
      'uid' => \Drupal::currentUser()->id(),
      'status' => 0,
      'field_landingpage_theme' => $node->get('field_landingpage_theme')->value,
      'field_landingpage_paragraphs' => $paragraphs,
    ));
    $new_node->save();
    drupal_set_message($this->t('New LandingPage "@title" was created! Please feel free to add your changes.', array('@title' => $new_node->label())));
    $form_state->setRedirect('entity.node.edit_form', ['node' => $new_node->id()]);
  }

}
