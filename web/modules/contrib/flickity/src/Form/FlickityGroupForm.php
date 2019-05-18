<?php

/**
 * @file
 * Contains \Drupal\flickity\Form\FlickityGroupForm.
 */

namespace Drupal\flickity\Form;

use Drupal\flickity\Entity\FlickityGroup;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the group entity add/edit forms.
 */
class FlickityGroupForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $group = FlickityGroup::load('default_group');
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
        'exists' => ['\Drupal\flickity\Entity\FlickityGroup', 'load'],
      ),
    );
    // Setup
    $form['images_loaded'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('imagesLoaded'),
      '#default_value' => $settings['images_loaded'],
      '#description' => $this->t('Unloaded images have no size, which can throw off cell positions. To fix this, the imagesLoaded option re-positions cells once their images have loaded.'),
    );
    $form['cell_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cell selector'),
      '#default_value' => $settings['cell_selector'],
      '#size' => 30,
      '#description' => $this->t('Specify selector for cell elements. This is useful if you have other elements in your gallery elements that are not cells.'),
    );
    $form['initial_index'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Initial index'),
      '#default_value' => $settings['initial_index'],
      '#size' => 30,
      '#description' => $this->t('Zero-based index of the initial selected cell.'),
    );
    $form['accessibility'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Accessibility'),
      '#default_value' => $settings['accessibility'],
      '#description' => $this->t('Enable keyboard navigation. Users can tab to a Flickity gallery, and pressing left & right keys to change cells.'),
    );
    $form['set_gallery_size'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set gallery size'),
      '#default_value' => $settings['set_gallery_size'],
      '#description' => $this->t('Sets the height of the gallery to the height of the tallest cell.'),
    );
    $form['resize'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Resize'),
      '#default_value' => $settings['resize'],
      '#description' => $this->t('Adjusts sizes and positions when window is resized.'),
    );
    // Cell position
    $form['cell_align'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cell align'),
      '#default_value' => $settings['cell_align'],
      '#size' => 30,
      '#description' => $this->t('Align cells within the gallery element.'),
    );
    $form['contain'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Contain'),
      '#default_value' => $settings['contain'],
      '#description' => $this->t('Contains cells to gallery element to prevent excess scroll at beginning or end.'),
    );
    $form['percent_position'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Percent position'),
      '#default_value' => $settings['percent_position'],
      '#description' => $this->t('Sets positioning in percent values, rather than pixel values.'),
    );
    $form['right_to_left'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Right to left'),
      '#default_value' => $settings['right_to_left'],
      '#description' => $this->t('Enables right-to-left layout.'),
    );
    // Behavior
    $form['draggable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable'),
      '#default_value' => $settings['draggable'],
      '#description' => $this->t('Enables dragging and flicking.'),
    );
    $form['free_scroll'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Free scroll'),
      '#default_value' => $settings['free_scroll'],
      '#description' => $this->t('Enables content to be freely scrolled and flicked without aligning cells to an end position.'),
    );
    $form['wrap_around'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap around'),
      '#default_value' => $settings['wrap_around'],
      '#description' => $this->t('At the end of cells, wrap-around to the other end for infinite scrolling.'),
    );
    $form['group_cells'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Group cells'),
      '#default_value' => $settings['group_cells'],
      '#description' => $this->t('Groups cells together in slides. Flicking, page dots, and previous/next buttons are mapped to group slides, not individual cells.'),
    );
    $form['adaptive_height'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Adaptive height'),
      '#default_value' => $settings['adaptive_height'],
      '#description' => $this->t('Changes height of carousel to fit height of selected slide.'),
    );
    // @todo, provide additional field if #state is true.
    $form['lazy_load'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Lazy load'),
      '#default_value' => $settings['lazy_load'],
      '#description' => $this->t('Loads cell images when a cell is selected.'),
    );
    // @todo, provide additional field if #state is true.
    $form['auto_play'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto play'),
      '#default_value' => $settings['auto_play'],
      '#description' => $this->t('Auto-playing will pause when mouse is hovered over, and resume when mouse is hovered off. Auto-playing will stop when the gallery is clicked or a cell is selected.'),
    );
    // Extra
    $form['watch_css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Watch CSS'),
      '#default_value' => $settings['watch_css'],
      '#description' => $this->t('You can enable and disable Flickity with CSS. watchCSS option watches the content of :after of the gallery element. Flickity is enabled if :after content is flickity.'),
    );
    $form['as_nav_for'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('As nav for'),
      '#default_value' => $settings['as_nav_for'],
      '#size' => 30,
      '#description' => $this->t('Use one Flickity gallery as navigation for another. This can be set a selector string'),
    );
    // Display
    $form['drag_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Drag threshold'),
      '#default_value' => $settings['drag_threshold'],
      '#description' => $this->t('The number of pixels a mouse or touch has to move before dragging begins.'),
    );
    $form['selected_attraction'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Selected attraction'),
      '#default_value' => $settings['selected_attraction'],
      '#size' => 30,
      '#description' => $this->t('Attracts the position of the slider to the selected cell. Higher attraction makes the slider move faster.'),
    );
    $form['friction'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Friction'),
      '#default_value' => $settings['friction'],
      '#size' => 30,
      '#description' => $this->t('Slows the movement of slider. Higher friction makes the slider feel stickier and less bouncy. Lower friction makes the slider feel looser and more wobbly.'),
    );
    $form['free_scroll_friction'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Free scroll friction'),
      '#default_value' => $settings['free_scroll_friction'],
      '#size' => 30,
      '#description' => $this->t('Slows movement of slider when freeScroll: true. Higher friction makes the slider feel stickier. Lower friction makes the slider feel looser.'),
    );
    // UI
    $form['prev_next_buttons'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Previous next buttons'),
      '#default_value' => $settings['prev_next_buttons'],
      '#description' => $this->t('Creates and enables previous & next buttons.'),
    );
    $form['page_dots'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Page dots'),
      '#default_value' => $settings['page_dots'],
      '#description' => $this->t('Creates and enables page dots.'),
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
      'cell_selector' => $form_state->getValue('cell_selector'),
      'initial_index' => $form_state->getValue('initial_index'),
      'accessibility' => $form_state->getValue('accessibility'),
      'set_gallery_size' => $form_state->getValue('set_gallery_size'),
      'resize' => $form_state->getValue('resize'),
      'cell_align' => $form_state->getValue('cell_align'),
      'contain' => $form_state->getValue('contain'),
      'percent_position' => $form_state->getValue('percent_position'),
      'right_to_left' => $form_state->getValue('right_to_left'),
      'draggable' => $form_state->getValue('draggable'),
      'free_scroll' => $form_state->getValue('free_scroll'),
      'wrap_around' => $form_state->getValue('wrap_around'),
      'group_cells' => $form_state->getValue('group_cells'),
      'adaptive_height' => $form_state->getValue('adaptive_height'),
      'lazy_load' => $form_state->getValue('lazy_load'),
      'auto_play' => $form_state->getValue('auto_play'),
      'watch_css' => $form_state->getValue('watch_css'),
      'as_nav_for' => $form_state->getValue('as_nav_for'),
      'drag_threshold' => $form_state->getValue('drag_threshold'),
      'selected_attraction' => $form_state->getValue('selected_attraction'),
      'friction' => $form_state->getValue('friction'),
      'free_scroll_friction' => $form_state->getValue('free_scroll_friction'),
      'prev_next_buttons' => $form_state->getValue('prev_next_buttons'),
      'page_dots' => $form_state->getValue('page_dots')
    ));
    $status = $entity->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';

    // Group has been updated.
    drupal_set_message($this->t('Group %label has been %action.', array(
      '%label' => $entity->label(),
      '%action' => $action
    )));

    $this->logger('flickity')->notice('Group %label has been %action.', array(
      '%label' => $entity->label(),
      '%action' => $action
    ));

    // Redirect back to display view.
    $form_state->setRedirect('flickity.group_list');
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
