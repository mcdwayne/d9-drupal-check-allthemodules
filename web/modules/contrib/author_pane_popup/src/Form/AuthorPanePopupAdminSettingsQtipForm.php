<?php

namespace Drupal\author_pane_popup\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the Google Analytics Vimeo settings form.
 */
class AuthorPanePopupAdminSettingsQtipForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'author_pane_popup_qtip_settings';
  }

  /**
   * Displays list of qTip instances created.
   */
  public function qtipList() {
    $query = \Drupal::database()->select('author_pane_popup_qtip', 'qinsta');
    $query->fields('qinsta', ['machine_name', 'name', 'settings']);
    $qtip_instances = $query->execute()->fetchAll();
    $add_qtip_url = Url::fromRoute('author_pane_popup.qtip_add_form');
    $form['qtip_list'] = array(
      '#type' => 'table',
      '#caption' => t('qTip instances'),
      '#header' => array(t('Name'), t('Machine name'), t('Operations')),
      '#empty' => t('There are no items yet. @add-url an item.', array(
        '@add-url' => \Drupal::l(t('Add'), $add_qtip_url),
      )),
    );
    foreach ($qtip_instances as $key => $qtip_instance) {
      $edit_url = Url::fromRoute('author_pane_popup.qtip_edit_form', array('machine_name' => $qtip_instance->machine_name));
      $delete_url = Url::fromRoute('author_pane_popup.qtip_delete_form', array('machine_name' => $qtip_instance->machine_name));

      $form['qtip_list'][$key]['name'] = array(
        '#plain_text' => $qtip_instance->name,
      );
      $form['qtip_list'][$key]['machine_name'] = array(
        '#plain_text' => $qtip_instance->machine_name,
      );
      $form['qtip_list'][$key]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['qtip_list'][$key]['operations']['#links']['edit'] = array(
        'title' => t('Edit'),
        'url' => $edit_url,
      );
      $form['qtip_list'][$key]['operations']['#links']['delete'] = array(
        'title' => t('Delete'),
        'url' => $delete_url,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = '') {

    $values = $form_state->getValues();

    if ($machine_name != '') {
      $qt = $this->authorPanePopupLoadQtipInstance($machine_name);
    }

    if (!isset($qt->name)) {
      $qt = new \stdClass();
    }
    else {
      // Get the settings out of the settings array.
      $content = $qt->settings['content'];
      $style = $qt->settings['style'];
      $position = $qt->settings['position'];
      $show = $qt->settings['show'];
      $hide = $qt->settings['hide'];
      $miscellaneous = $qt->settings['miscellaneous'];
    }

    // The contents of $qt will either come from the db or from $form_state.
    if (isset($values->name)) {
      $qt = authorPanePopupConvertFormToQtips($values);
    }

    $form = array('#tree' => TRUE);

    $form['name'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Name'),
      '#description'   => t('The human-friendly name to identify this qTip instance throughout the site.'),
      '#default_value' => isset($qt->name) ? $qt->name : '',
      '#required'      => TRUE,
    );
    $form['machine_name'] = array(
      '#type'         => 'machine_name',
      '#description'  => t('A unique machine-readable name for this qTip instance. It must only contain lowercase letters, numbers, and underscores. The machine name will be used internally by qTip and will be used in the CSS ID of your qTip instance.'),
      '#machine_name' => array(
        'exists' => [$this, 'authorPanePopupQtipMachineNameExists'],
        'source' => array('name'),
      ),
      '#maxlength' => 32,
    );
    if (!empty($qt->machine_name)) {
      $form['machine_name']['#default_value'] = $qt->machine_name;
      $form['machine_name']['#disabled'] = TRUE;
      $form['machine_name']['#value'] = $qt->machine_name;
      $form['edit_qtip_instance'] = ['#type' => 'hidden', '#value' => TRUE];
    }
    $form['qtip_settings'] = array(
      '#type'  => 'vertical_tabs',
      '#title' => t('Tracking scope'),
    );
    $form['style'] = array(
      '#type'   => 'details',
      '#title'  => t('Style'),
      '#group'  => 'qtip_settings',
    );

    /* Tip settings */

    $form['style']['tip_settings'] = array(
      '#type'        => 'details',
      '#title'       => t('Tip Settings'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
      '#parents'     => array('style'),
      '#states'      => array(
        'visible' => array(
          ':input[name="style[tip][corner]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['style']['tip_settings']['tip']['width'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Width'),
      '#description'   => t('Determines the width of the rendered tip in pixels, in relation to the side of the tooltip it lies upon i.e. when the tip position is on the left or right, this quantity actually refers to the tips height in visual terms, and vice versa.') .
      '<br><strong>' . t("Make sure this is a number only, don't include any units e.g. 'px'!") . '</strong>',
      '#default_value' => isset($style['tip']['width']) ? $style['tip']['width'] : '6',
      '#size'          => 5,
      '#required'      => 1,
    );
    $form['style']['tip_settings']['tip']['height'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Height'),
      '#description'   => t('Determines the height of the rendered tip in pixels, in relation to the side of the tooltip it lies upon i.e. when the tip position is on the left or right, this quantity actually refers to the tips width in visual terms, and vice versa.') .
      '<br><strong>' . t("Make sure this is a number only, don\'t include any units e.g. 'px'!") . '</strong>',
      '#default_value' => isset($style['tip']['height']) ? $style['tip']['height'] : '6',
      '#size'          => 5,
      '#required'      => 1,
    );
    $form['style']['tip_settings']['tip']['border'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Border'),
      '#description'   => t('This option determines the width of the border that surrounds the tip element, much like the CSS border-width property of regular elements.') .
      '<br><strong>' . t("Make sure this is a number only, don't include any units e.g. 'px'! Leave blank for default settings.") . '</strong>',
      '#default_value' => isset($style['tip']['border']) ? $style['tip']['border'] : '',
      '#size'          => 5,
    );
    $form['style']['tip_settings']['tip']['corner_position'] = array(
      '#type'          => 'select',
      '#title'         => t('Position'),
      '#options'       => $this->authorPanePopupQtipAdminTooltipPositionOptions(),
      '#default_value' => isset($style['tip']['corner_position']) ? $style['tip']['corner_position'] : '',
    );
    $form['style']['tip_settings']['tip']['mimic'] = array(
      '#type'          => 'select',
      '#title'         => t('Mimic'),
      '#options'       => $this->authorPanePopupQtipAdminTooltipPositionOptions(),
      '#default_value' => isset($style['tip']['mimic']) ? $style['tip']['mimic'] : '',
    );
    $form['style']['tip_settings']['tip']['offset'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Offset'),
      '#description'   => t('Determines the offset of the tip in relation to its current corner position. This value is relative i.e. depending on which corner the tooltip is set it will behave differently.') .
      '<br><strong>' . t("Make sure this is a number only, don't include any units e.g. 'px'!") . '</strong>',
      '#default_value' => isset($style['tip']['offset']) ? $style['tip']['offset'] : '',
      '#size'          => 5,
    );
    $form['style']['tip_settings']['tip']['corner'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Show speech bubble tip'),
      '#description'   => t('If checked each tooltip will have a small speech bubble tip appended to them.'),
      '#default_value' => isset($style['tip']['corner']) ? $style['tip']['corner'] : 0,
      '#states'        => array(
        'invisible' => array(
          ':input[name="style[color_scheme]"]' => array('value' => 'qtip-youtube'),
        ),
      ),
    );
    $form['style']['classes'] = array(
      '#type'    => 'select',
      '#title'   => t('Color scheme'),
      '#options' => array(
        'Standard Color Schemes' => array(
          ''           => t('Plain (default)'),
          'qtip-cream' => t('Cream'),
          'qtip-light' => t('Light'),
          'qtip-dark'  => t('Dark'),
          'qtip-red'   => t('Red'),
          'qtip-green' => t('Green'),
          'qtip-blue'  => t('Blue'),
        ),
        'Advanced Color Schemes' => array(
          'qtip-bootstrap' => t('Bootstrap'),
          'qtip-tipsy'     => t('Tipsy'),
          'qtip-youtube'   => t('YouTube'),
          'qtip-jtools'    => t('jTools'),
          'qtip-cluetip'   => t('ClueTip'),
          'qtip-tipped'    => t('Tipped'),
        ),
        'qtip-custom' => t('Custom Color Scheme'),
      ),

      '#default_value' => isset($style['classes']) ? $style['classes'] : '',
    );
    $form['style']['classes_custom'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Custom CSS class'),
      '#description'   => t('The custom CSS class that will be used for all qTips.'),
      '#default_value' => isset($style['classes_custom']) ? $style['classes_custom'] : '',
      '#states'        => array(
        'visible' => array(
          ':input[name="style[classes]"]' => array('value' => 'qtip-custom'),
        ),
      ),
      '#size'   => 40,
    );
    $form['style']['shadow'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Show shadow under tooltips'),
      '#description'   => t('If checked a shadow will display under each tooltip.') . '<br>' .
      '<strong>' . t('NOTE: This adds a class to each tooltip that uses the box-shadow CSS3 property, which is not supported in older browsers.') . '</strong>',
      '#default_value' => isset($style['shadow']) ? $style['shadow'] : 0,
      '#return_value'  => 'qtip-shadow',
    );
    $form['style']['rounded_corners'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Show tooltips with rounded corners'),
      '#description'   => t('If checked each tooltip will have rounded corners.') . '<br>' .
      '<strong>' . t('NOTE: This adds a class to each tooltip that uses the border-radius CSS3 property, which is not supported in older browsers.') . '</strong>',
      '#default_value' => isset($style['rounded_corners']) ? $style['rounded_corners'] : 0,
      '#return_value'  => 'qtip-rounded',
    );
    $form['position'] = array(
      '#type'   => 'details',
      '#title'  => t('Position'),
      '#group'  => 'qtip_settings',
    );
    $form['position']['at'] = array(
      '#type'          => 'select',
      '#title'         => t('Position'),
      '#options'       => $this->authorPanePopupQtipAdminTooltipPositionOptions(),
      '#description'   => t("Set where the tooltips should display relative to it's target."),
      '#default_value' => isset($position['at']) ? $position['at'] : 'bottom right',
    );
    $form['position']['my'] = array(
      '#type'          => 'select',
      '#title'         => t('Tooltip placement'),
      '#options'       => $this->authorPanePopupQtipAdminTooltipPositionOptions(),
      '#default_value' => isset($position['my']) ? $position['my'] : '',
    );
    $form['position']['viewport'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Keep tooltip within window'),
      '#default_value' => isset($position['viewport']) ? $position['viewport'] : 0,
    );
    $form['position']['target'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Follow mouse'),
      '#default_value' => isset($position['target']) ? $position['target'] : 0,
    );
    $form['position']['adjust']['method'] = array(
      '#type'    => 'select',
      '#title'   => t('Method'),
      '#options' => array(
        ''      => t('Flip/Invert'),
        'flip'  => t('Flip'),
        'shift' => t('Shift'),
      ),
      '#default_value' => isset($position['adjust']['method']) ? $position['adjust']['method'] : '',
    );
    $form['show'] = array(
      '#type'  => 'details',
      '#title' => t('Show'),
      '#group'  => 'qtip_settings',
    );

    $form['show']['intro'] = array(
      '#markup' => t('The show settings define what events trigger the tooltip to show on which elements, as well as the initial delay and several other properties.'),
    );
    $form['show']['event'] = array(
      '#type'    => 'checkboxes',
      '#title'   => t('Event'),
      '#options' => array(
        'mouseenter' => t('Hover'),
        'focus'      => t('Focus'),
        'click'      => t('Click'),
      ),
      '#description' => t('The action(s) that will display this tooltip.'),
      '#default_value' => isset($show['event']) ? $show['event'] : array('mouseenter'),
    );
    $form['show']['solo'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Only show one tooltip at a time'),
      '#description'   => t('Determines whether or not the tooltip will hide all others when the show event is triggered.'),
      '#default_value' => isset($show['solo']) ? $show['solo'] : 0,
    );
    $form['show']['ready'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Show tooltip on page load'),
      '#description'   => t("Determines whether or not the tooltip is shown as soon as the page has finished loading. This is useful for tooltips which are created inside event handlers, as without it they won't show up immediately."),
      '#default_value' => isset($show['ready']) ? $show['ready'] : 0,
    );
    $form['hide'] = array(
      '#type'  => 'details',
      '#title' => t('Hide'),
      '#group'  => 'qtip_settings',
    );
    $form['hide']['intro'] = array(
      '#markup' => t('The hide settings define what events trigger the tooltip to hide on which elements, as well as the initial delay and several other properties.'),
    );
    $form['hide']['event'] = array(
      '#type'    => 'checkboxes',
      '#title'   => t('Event'),
      '#options' => array(
        'mouseleave' => t('Leave'),
        'unfocus'    => t('Unfocus'),
        'blur'       => t('Blur'),
        'click'      => t('Click'),
      ),
      '#description'   => t('The action(s) that will hide this tooltip.'),
      '#default_value' => isset($hide['event']) ? $hide['event'] : array('mouseleave'),
    );
    $form['hide']['fixed'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Keep tooltip visible when hovered'),
      '#description'   => t('When enabled, the tooltip will not hide if moused over, allowing the contents to be clicked and interacted with.'),
      '#default_value' => isset($hide['fixed']) ? $hide['fixed'] : '',
    );
    $form['hide']['delay'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Delay'),
      '#description'   => t('Time in milliseconds by which to delay hiding of the tooltip when the hide event is triggered.') .
      '<br><strong>' . t('Make sure this is a number only!') . '</strong>',
      '#default_value' => isset($hide['delay']) ? $hide['delay'] : '',
      '#size'          => 5,
    );
    $form['hide']['inactive'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Inactive'),
      '#description'   => t("Time in milliseconds in which the tooltip should be hidden if it remains inactive e.g. isn't interacted with. If blank, tooltip will not hide when inactive.") .
      '<br><strong>' . t('Make sure this is a number only!') . '</strong>',
      '#default_value' => isset($hide['inactive']) ? $hide['inactive'] : '',
      '#size'          => 5,
    );

    /* Miscellaneous */
    $form['miscellaneous'] = array(
      '#type'  => 'details',
      '#title' => t('Miscellaneous'),
      '#group'  => 'qtip_settings',
    );
    $form['miscellaneous']['button'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Show close button on tooltip'),
      '#default_value' => isset($content['button']) ? $content['button'] : 0,
      '#return_value'  => TRUE,
    );
    $form['miscellaneous']['button_title_text'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Title text'),
      '#default_value' => isset($miscellaneous['button_title_text']) ? $miscellaneous['button_title_text'] : '',
      '#states'        => array(
        'visible' => array(
          ':input[name="miscellaneous[button]"]' => array('checked' => TRUE),
        ),
      ),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $qt = $this->authorPanePopupConvertFormToQtips($values);
    if (isset($values['edit_qtip_instance'])) {
      $query = \Drupal::database()->update('author_pane_popup_qtip');
      $query->fields([
        'name' => $qt->name,
        'machine_name' => $qt->machine_name,
        'settings' => serialize($qt->settings),
      ]);
      $query->condition('machine_name', $qt->machine_name);
      $message = t('qTip instance @name has been updated.', array('@name' => $qt->name));
    }
    else {
      $query = \Drupal::database()->insert('author_pane_popup_qtip');
      $query->fields([
        'machine_name',
        'name',
        'settings',
      ]);
      $query->values([
        $qt->machine_name,
        $qt->name,
        serialize($qt->settings),
      ]);
      $message = t('The qTip instance @name has been created.', array('@name' => $qt->name));
    }

    $query->execute();
    drupal_set_message($message, 'status');
    $form_state->setRedirect('author_pane_popup.qtip_list');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['author_pane_popup.admin_settings_form_qtip'];
  }

  /**
   * Helper function to convert the data on admin form into qtip presentation.
   */
  public function authorPanePopupConvertFormToQtips($form_state) {
    $qt = new \stdClass();
    $qt->name = $form_state['name'];

    if (isset($form_state['machine_name'])) {
      $qt->machine_name = $form_state['machine_name'];
    }

    $qt->settings = array(
      'content'  => array(
        'button' => $form_state['miscellaneous']['button'],
      ),
      'style'         => $form_state['style'],
      'position'      => $form_state['position'],
      'show'          => $form_state['show'],
      'hide'          => $form_state['hide'],
      'miscellaneous' => array(
        'button_title_text' => $form_state['miscellaneous']['button_title_text'],
      ),
    );
    return $qt;
  }

  /**
   * Load qTip Instance by machine name.
   */
  public function authorPanePopupLoadQtipInstance($machine_name) {
    $query = \Drupal::database()->select('author_pane_popup_qtip', 'qinsta');
    $query->fields('qinsta', ['machine_name', 'name', 'settings']);
    $query->condition('qinsta.machine_name', $machine_name);
    $query->range(0, 1);
    $qtip_instance = $query->execute()->fetchAssoc();
    if (!empty($qtip_instance)) {
      $qt = new \stdClass();
      $qt->name = $qtip_instance['name'];
      $qt->machine_name = $qtip_instance['machine_name'];
      $qt->settings = unserialize($qtip_instance['settings']);
    }
    return $qt;
  }

  /**
   * Determine if the machine name is in use.
   */
  public function authorPanePopupQtipMachineNameExists($entity_id, array $element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $machine_name = $values['machine_name'];
    $qt_exists = db_query_range('SELECT 1 FROM {author_pane_popup_qtip} WHERE machine_name = :name', 0, 1, array(':name' => $machine_name))->fetchField();
    return $qt_exists;
  }

  /**
   * Helper function to get options for tooltip positioning.
   */
  public function authorPanePopupQtipAdminTooltipPositionOptions($normal = TRUE) {
    $options = array(
      'top left'      => 'Top Left Corner',
      'top center'    => 'Top Center',
      'top right'     => 'Top Right Corner',
      'right top'     => 'Right Top Corner',
      'right center'  => 'Right Center',
      'right bottom'  => 'Right Bottom Corner',
      'bottom right'  => 'Bottom Right Corner',
      'bottom center' => 'Bottom Center',
      'bottom left'   => 'Bottom Left Corner',
      'left bottom'   => 'Left Bottom Corner',
      'left center'   => 'Left Center',
      'left top'      => 'Left Top',
      'center'        => 'Center',
    );

    if ($normal) {
      // Prepend a 'Normal' option onto the beginning of the array, if set.
      $normal = array('' => 'Normal');
      $options = $normal + $options;
    }

    return $options;
  }

  /**
   * Load Multiple qTip Instances from DB.
   */
  public static function authorPanePopupQtipLoadMultiple($select_options = FALSE) {
    $qtip = array();
    $query = \Drupal::database()->select('author_pane_popup_qtip', 'qinsta');
    $query->fields('qinsta', ['machine_name', 'name', 'settings']);
    $qtip_instances = $query->execute()->fetchAll();
    foreach ($qtip_instances as $qtip_instance) {
      if ($select_options) {
        $qtip[$qtip_instance->machine_name] = $qtip_instance->name;
      }
      else {
        $qtip[$qtip_instance->machine_name] = unserialize($qtip_instance->settings);
      }
    }
    return $qtip;
  }

}
