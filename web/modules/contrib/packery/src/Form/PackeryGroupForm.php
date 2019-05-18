<?php

/**
 * @file
 * Contains \Drupal\packery\Form\PackeryGroupForm.
 */

namespace Drupal\packery\Form;

use Drupal\packery\Entity\PackeryGroup;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group entity edit forms.
 */
class PackeryGroupForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $group = PackeryGroup::load('default_group');
    $settings = $group->getSettings();

    if ($entity->getSettings()) {
      $settings = $entity->getSettings();
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Group name'),
      '#default_value' => $entity->label(),
      '#size' => 30,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#description' => $this->t('The settings group identifier.'),
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
      '#disabled' => !$entity->isNew(),
      '#size' => 30,
      '#maxlength' => 64,
      '#machine_name' => array(
        'exists' => ['\Drupal\packery\Entity\PackeryGroup', 'load'],
      ),
    );
    $form['images_loaded'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('imagesLoaded'),
      '#default_value' => $settings['images_loaded'],
      '#description' => $this->t('Provides support for imagesLoaded plugin..'),
    );
    $form['container_style'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Container Style'),
      '#default_value' => $settings['container_style'],
      '#size' => 30,
      '#description' => $this->t('CSS styles that are applied to the container element. To disable Packery from setting any CSS to the container element, set NULL.'),
    );
    $form['column_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Column Width'),
      '#default_value' => $settings['column_width'],
      '#size' => 30,
      '#description' => $this->t('The width of a column of a horizontal grid. When set, Packery will align item elements horizontally to this grid.'),
    );
    $form['gutter'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Gutter'),
      '#default_value' => $settings['gutter'],
      '#size' => 30,
      '#description' => $this->t('The space between item elements, both vertically and horizontally.'),
    );
    $form['percent_position'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Percent position'),
      '#default_value' => $settings['percent_position'],
      '#description' => $this->t('Will set item position in percent values, rather than pixel values. percentPosition works well with percent-width items, as items will not transition their position on resize.'),
    );
    $form['is_horizontal'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is horizontal'),
      '#default_value' => $settings['is_horizontal'],
      '#description' => $this->t('Arranges items horizontally instead of vertically.'),
    );
    $form['is_init_layout'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is init layout'),
      '#default_value' => $settings['is_init_layout'],
      '#description' => $this->t('Enables layout on initialization. Set this to false to disable layout on initialization, so you can use methods or add events before the initial layout.'),
    );
    $form['is_origin_left'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is origin left'),
      '#default_value' => $settings['is_origin_left'],
      '#description' => $this->t('Controls the horizontal flow of the layout. By default, item elements start positioning at the left. Set to false for right-to-left layouts.'),
    );
    $form['is_origin_top'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is origin top'),
      '#default_value' => $settings['is_origin_top'],
      '#description' => $this->t('Controls the vertical flow of the layout. By default, item elements start positioning at the top. Set to false for bottom-up layouts. It’s like Tetris!'),
    );
    $form['is_resize_bound'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Is resize bound'),
      '#default_value' => $settings['is_resize_bound'],
      '#description' => $this->t('Binds layout only when the Packery instance is first initialized. You can bind and unbind resize layout afterwards with the bindResize and unbindResize methods.'),
    );
    $form['item_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Item selector'),
      '#size' => 30,
      '#default_value' => $settings['item_selector'],
      '#description' => $this->t('Specifies which child elements to be used as item elements. Setting itemSelector is always recommended. itemSelector is useful to exclude sizing elements.'),
    );
    $form['row_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Row height'),
      '#size' => 30,
      '#default_value' => $settings['row_height'],
      '#description' => $this->t('Height of a row of a vertical grid. When set, Packery will align item elements vertically to this grid.'),
    );
    $form['stamp'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stamp'),
      '#size' => 30,
      '#default_value' => $settings['stamp'],
      '#description' => $this->t('Specifies which elements are stamped within the layout.'),
    );
    $form['transition_duration'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Transition duration'),
      '#size' => 30,
      '#default_value' => $settings['transition_duration'],
      '#description' => $this->t('The time duration of transitions for item elements.'),
    );

    return parent::form($form, $form_state, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $entity->set('label', trim($entity->label()));
    $entity->set('settings', array(
      'images_loaded' => $form_state->getValue('images_loaded'),
      'container_style' => $form_state->getValue('container_style'),
      'column_width' => $form_state->getValue('column_width'),
      'gutter' => $form_state->getValue('gutter'),
      'percent_position' => $form_state->getValue('percent_position'),
      'is_horizontal' => $form_state->getValue('is_horizontal'),
      'is_init_layout' => $form_state->getValue('is_init_layout'),
      'is_origin_left' => $form_state->getValue('is_origin_left'),
      'is_origin_top' => $form_state->getValue('is_origin_top'),
      'is_resize_bound' => $form_state->getValue('is_resize_bound'),
      'item_selector' => $form_state->getValue('item_selector'),
      'row_height' => $form_state->getValue('row_height'),
      'stamp' => $form_state->getValue('stamp'),
      'transition_duration' => $form_state->getValue('transition_duration')
    ));
    $status = $entity->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';

    // Group has been updated.
    drupal_set_message($this->t('Group %label has been %action.', ['%label' => $entity->label(), '%action' => $action]));
    $this->logger('packery')->notice('Group %label has been %action.', array('%label' => $entity->label(), 'link' => $edit_link));

    // Redirect back to display view.
    $form_state->setRedirect('packery.group_list');
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = ($this->entity->isNew()) ? $this->t('Add group') : $this->t('Update group');

    return $actions;
  }

}
