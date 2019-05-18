<?php
/**
 * @file
 * Contains \Drupal\royalslider\Form\RoyalSliderOptionSetForm.
 */

namespace Drupal\royalslider\Form;


use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RoyalSliderOptionSetForm
 *
 * Form class for adding/editing RoyalSliderOptionSet config entities.
 */
class RoyalSliderOptionSetForm extends EntityForm {
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
    $form = parent::form($form, $form_state);
    $optionset = $this->entity;

    // Change page title for the edit operation
    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit optionset: @name', array('@name' => $optionset->name));
    }

    // The optionset name.
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $optionset->name,
      '#description' => $this->t("Optionset name."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $optionset->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exist'),
      ),
      '#disabled' => !$optionset->isNew(),
    );

    // The optionset autoScaleSlider option.
    $form['auto_scale_slider'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('autoScaleSlider'),
      '#default_value' => $optionset->auto_scale_slider,
      '#description' => $this->t("Automatically updates slider height based on base width."),
    );

    // The optionset autoScaleSliderWidth option.
    $form['auto_scale_slider_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('autoScaleSliderWidth'),
      '#default_value' => $optionset->auto_scale_slider_width,
      '#description' => $this->t("Base slider width. Slider will autocalculate the ratio based on these values."),
    );

    // The optionset autoScaleSliderHeight option.
    $form['auto_scale_slider_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('autoScaleSliderHeight'),
      '#default_value' => $optionset->auto_scale_slider_height,
      '#description' => $this->t("Base slider height."),
    );

    // The optionset loop option.
    $form['loop'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('loop'),
      '#default_value' => $optionset->loop,
      '#description' => $this->t("The slideshow loops."),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $optionset = $this->entity;
    $status = $optionset->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label optionset.', array(
        '%label' => $optionset->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label optionset was not saved.', array(
        '%label' => $optionset->label(),
      )));
    }

    $form_state->setRedirect('entity.royalslider_optionset.collection');
  }

  public function exist($id) {
    $entity = $this->entityQuery->get('royalslider_optionset')
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}