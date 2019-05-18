<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\jvector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
//use Drupal\Core\Form\FormValidatorInterface;
use Drupal\jvector\JvectorSvgReader;
use Drupal\Core\Url;

class JvectorViewForm extends EntityForm {

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
    $form['#title'] = 'View Jvector \'' . $this->entity->label() . '\'';

    $form = parent::form($form, $form_state);
    $text = "";
    $entity = $this->entity;
    $paths = $entity->paths;

    $form['preview'] = array(
      '#type' => 'select',
      '#title' => 'Jvector preview',
      '#default' => 'empty',
      '#multiple' => FALSE,
      '#empty_option' => t('- None selected -'),
    );
    foreach ($paths AS $path_id => $path) {
      $name = $path['name'];
      $form['preview']['#options'][($path['id'])] = $name;
    };


    foreach ($paths AS $path_id => $path) {
      $text .= $path_id . "|" . $path['name'] . "\n";
    }

    $desc = 'Paste this data into a select field\'s allowed values.';
    $form['svg'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Default allowed values'),
      '#description' => $this->t($desc),
      '#default_value' => $entity->getJvectorAllowedList(),
      '#weight' => 20
    );

    $form['preview']['#jvector'] = $entity;
    $form['preview']['#jvector_config'] = 'default';
    $form['preview']['#jvector_admin'] = 'jvector';


    $form['jvector_config'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Style name'),
        $this->t('Style ID'),
        $this->t('Operations'),
        $this->t('Preview')
      ),
      '#empty' => t('There are no styles yet.'),
    );
    foreach ($entity->customconfig AS $key => $config) {
      $form['jvector_config'][$key]['title'] = array(
        '#tree' => FALSE,
        'data' => array(
          'label' => array(
            '#markup' => $config['label']
          ),
        ),
      );
      $form['jvector_config'][$key]['id'] = array(
        '#tree' => FALSE,
        'data' => array(
          'label' => array(
            '#markup' => $config['id']
          ),
        ),
      );


      $links = array();
      $links['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('jvector.config_edit', [
          'jvector' => $this->entity->id(),
          'customconfig' => $key,
        ]),
      );
      if ($key !== 'default') {
        $links['delete'] = array(
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('jvector.config_delete', [
            'jvector' => $this->entity->id(),
            'customconfig' => $key,
          ]),
        );
      }
      else {
        $links['revert'] = array(
          'title' => $this->t('Revert'),
          'url' => Url::fromRoute('jvector.config_delete', [
            'jvector' => $this->entity->id(),
            'customconfig' => $key,
          ]),
        );
      }

      $form['jvector_config'][$key]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
      $form['jvector_config'][$key]['jvector-preview-' . $key] = array(
        '#tree' => FALSE,
        'data' => array(
          'preview' => array(
            '#type' => 'button',
            '#value' => $this->t('Preview'),
            '#attributes' => array(
              'class' => array(
                'btn jvector-preview-btn jvector-preview-' . $key
              ),
            )
          ),
        ),
      );
    }


    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::actions().
   */
  public function actions(array $form, FormStateInterface $form_state) {
    return array();
  }


}