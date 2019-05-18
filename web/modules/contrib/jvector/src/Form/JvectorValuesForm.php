<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Form\FormValidatorInterface;
use Drupal\jvector\JvectorSvgReader;

class JvectorValuesForm extends EntityForm {

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $text = "";
    $entity = $this->entity;
    $paths = $entity->paths;
    foreach ($paths AS $path_id => $path) {
      $text .= $path_id . "|" . $path['name'] . "\n";
      $path = $path;
    }

    $form = parent::form($form, $form_state);
    $form['svg'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Allowed values'),
      '#description' => $this->t('Paste this data into a select field\'s allowed values.'),
      '#default_value' => $text,
    );
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::actions().
   */
  public function actions(array $form, FormStateInterface $form_state) {
    return array();
  }


}