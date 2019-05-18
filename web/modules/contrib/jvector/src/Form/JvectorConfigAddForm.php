<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\jvector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
//use Drupal\Core\Form\FormValidatorInterface;
use Drupal\jvector\JvectorSvgReader;
use Drupal\Core\RouteProcessor;

class JvectorConfigAddForm extends EntityForm {

  protected $routeMatch;

  /**
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query.
   */
  public function __construct(QueryFactory $entity_query, RouteMatchInterface $current_route_match) {
    $this->entityQuery = $entity_query;
    $this->routeMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $config = array();
    $config_id = 'default';
    $form['#title'] = 'Add colorset for \'' . $this->entity->label();
    $form['copy'] = array(
      '#type' => 'select',
      '#title' => $this->t('Create from'),
      '#default_value' => 'default',
      '#description' => $this->t('New configuration will be based on the selected set.'),
      '#options' => $entity->getJvectorConfigSetsAsSelect()
    );
    //@todo If the system default is altered, add option to retrieve true defaults.
    //$form['copy']['#options']['system_default'] = $this->t('System default');

    $form['#title'] = 'Jvector colorset for \'' . $this->entity->label() . '\'';


    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $paths = $entity->paths;


    // Set standard label & machine name
    $form['config_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Configuration name'),
      '#description' => t('The jvector\'s name.'),
      '#required' => TRUE,
    );
    $form['config_id'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => '\Drupal\jvector\Entity\Jvector::loadConfig',
        'source' => array('config_label'),
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
      ),
      '#maxlength' => 23,
    );
    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::actions().
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    $actions['submit']['#value'] = $this->t('Add');
    return $actions;
  }

  public function save(array $form, FormStateInterface $form_state) {
    // Only add a new array to the entity.
    $entity = $this->entity;
    $values = $form_state->getValues();
    $id = $values['config_id'];
    $label = $values['config_label'];
    $copy = $values['copy'];
    $entity->customconfig[$id] = $entity->customconfig[$copy];
    $entity->customconfig[$id]['id'] = $id;
    $entity->customconfig[$id]['label'] = $label;
    // Add a hierarchy.

    $this->entity->save();
    drupal_set_message($this->t("Configuration set was created successfully."));
    $form_state->setRedirectUrl($this->entity->urlInfo('view-form'));
  }


}