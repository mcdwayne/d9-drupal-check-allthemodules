<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Element\MMRepeatlist.
 */

namespace Drupal\monster_menus\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element which allows the user to manipulate groups of other
 * form elements.
 *
 * @FormElement("mm_repeatlist")
 */
class MMRepeatlist extends FormElement {

  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input'                      => TRUE,
      '#default_value'              => array(),
      '#process'                    => [[$class, 'processGroup'],
                                        [$class, 'process']],
      '#pre_render'                 => [[$class, 'preRenderGroup'],
                                        [$class, 'preRender']],
      '#attached'                   => array('library' => array('monster_menus/mm')),
      '#mm_list_id'                 => '',                    // REQUIRED: form ID of DIV containing elements to repeat
      '#mm_list_inputs_per_row'     => '',                    // REQUIRED: number of values per row in repeatable DIV and #default_value
      '#mm_list_min'                => 1,                     // min number of rows
      '#mm_list_max'                => 0,                     // max number of rows
      '#mm_list_add_button'         => $this->t('Add a Row'), // text label for the 'add' button
      '#mm_list_buttons_underneath' => TRUE,
      '#mm_list_readonly'           => FALSE,                 // display the data rows as text instead of form inputs
      '#mm_list_reorder'            => FALSE,                 // show the up/down arrows in each row
      '#theme'                      => 'mm_repeatlist',
      '#theme_wrappers'             => ['form_element'],
    );
  }

  /**
   * Add Javascript code to a page, allowing the user to repeat a set of form
   * fields any number of times without having to submit the form multiple
   * times.
   *
   * To specify which data is repeatable, use '#theme_wrappers' =>
   * array('container') to construct a new container DIV and pass its ID in the
   * mm_repeatlist form element using the #mm_list_id parameter. In order for
   * the code to work properly, the container DIV MUST come before the
   * mm_repeatlist form element in the form array.
   *
   * Default values for the data fields can be passed using the #default_value
   * parameter. It should contain an array of arrays, one per row of data to
   * pre-populate in the form. To parse the data after it has been submitted by
   * the user, call mm_ui_parse_repeatlist().
   *
   * Required fields in the form element:
   *   #mm_list_id                 Form ID of DIV containing repeatable children
   *   #mm_list_inputs_per_row     Number of values per row in repeatable DIV
   *                               and #default_value
   *
   * Optional fields in the form element:
   *   #mm_list_min                Min number of rows (default: 1)
   *   #mm_list_max                Max number of rows (default: 0)
   *   #mm_list_buttons_underneath Show the action buttons underneath the data
   *                               rows, instead of to the right (default:
   *                               FALSE)
   *   #mm_list_reorder            Show up/down arrows in each row, allowing
   *                               user to change the order (default: FALSE)
   *
   * Caveats:
   *   - In order for the code to work properly, the DIV container MUST come
   *     before the mm_repeatlist form element in the form array
   *   - The '#multiple' option is not supported for the 'select' type.
   *   - The 'file' and 'password' types are not supported.
   *   - If using the 'date' type, you must be sure to allocate three elements
   *     per row in the '#default_value' field, and increase the
   *     '#mm_list_inputs_per_row' accordingly.
   *
   * Example: Create a form element that allows the user to enter up to 10 rows
   * of data in two textfields per row. The form is pre-populated with some
   * default values:
   *
   *     $form['name_age'] = array(
   *        '#theme_wrappers' => array('container'),
   *        '#id' => 'name_age',
   *        '#attributes' => array('class' => array('hidden')));
   *     $form['name_age']['name'] = array(
   *       '#type' => 'textfield',
   *       '#title' => t('Your first name'),
   *       '#description' => t('What is your first name?'));
   *     $form['name_age']['age'] = array(
   *       '#type' => 'textfield',
   *       '#title' => t('Your age'),
   *       '#description' => t('What is your age?'));
   *
   *     $form['grouping'] = array(
   *       '#type' => 'details',
   *       '#open' => TRUE);
   *     $form['grouping']['data'] = array(
   *       '#type' => 'mm_repeatlist',
   *       '#title' => t('Tell us about yourself'),
   *       '#mm_list_id' => 'name_age',
   *       '#mm_list_inputs_per_row' => 2,
   *       '#default_value' => array(
   *           array('name1', '18'),
   *           array('name2', '26')));
   *
   * @param array $element
   *   The form element to display
   * @return array
   *   The modified form element
   */
  public static function preRender($element) {
    if (isset($elt['#mm_list_instance'])) {
      return $element;
    }
    $_mmlist_instance = &drupal_static('_mmlist_instance', 0);

    $max = intval($element['#mm_list_max']);
    $min = intval($element['#mm_list_min']);

    $label_above_actions = $element['#mm_list_buttons_underneath'] ? '&nbsp;' : '';
    $cat_label = $element['#title'] . (!empty($element['#required']) ? ' <span class="form-required" title="' . t('This field is required.') . '">*</span>' : '');

    $flags = array('narrow_actions' => TRUE);
    if ($element['#mm_list_buttons_underneath']) {
      $flags['action_under'] = TRUE;
    }

    if ($element['#mm_list_readonly']) {
      $labels = array();
      $label_add_set = "''";
      $flags['readonly'] = TRUE;
    }
    else {
      $labels = mm_ui_mmlist_labels();
      $labels[5] = '';  // no edit
      if (!$element['#mm_list_reorder']) {
        $labels[0] = $labels[1] = $labels[3] = $labels[4] = '';
      }
      $label_add_set = $max == 1 ? '' : $element['#mm_list_add_button'];
    }

    if (isset($element['#name'])) {
      $name = $element['#name'];
    }
    else {
      $name = $element['#parents'][0];
      if (count($element['#parents']) > 1) {
        $name .= '[' . join('][', array_slice($element['#parents'], 1)) . ']';
      }
    }

    $class = MMCatlist::addClass($element);
    $objname = $element['#mm_list_id'];

    $imgpath = base_path() . drupal_get_path('module', 'monster_menus') . '/images';
    $del_confirm = t("Are you sure you want to delete this row?\n\n(You can skip this alert in the future by holding the Shift key while clicking the Delete button.)");

    $value = $element['#value'];
    if (!is_array($value)) {
      $value = $value[0] == '{' ? mm_ui_parse_repeatlist($value, $element['#mm_list_inputs_per_row']) : array();
    }
    if ($min) {
      $value = array_pad($value, $min, array());
    }

    $settings = [
      'isSearch'           => mm_ui_is_search(),
      'where'              => NULL,
      'listObjDivSelector' => "#$objname",
      'outerDivSelector'   => "div[name=mm_list$_mmlist_instance]",
      'hiddenName'         => $name,
      'add'                => $value,
      'autoName'           => NULL,
      'parms'              => [
        'minRows'           => $min,
        'maxRows'           => $max,
        'flags'             => $flags,
        'addCallback'       => 'listAddCallback',
        'dataCallback'      => 'listDataCallback',
        'labelAboveList'    => $cat_label,
        'labelAboveActions' => $label_above_actions,
        'labelAddList'      => $label_add_set,
        'imgPath'           => $imgpath,
        'delConfirmMsg'     => $del_confirm,
        'labelTop'          => $labels[0],
        'labelUp'           => $labels[1],
        'labelX'            => $labels[2],
        'labelBott'         => $labels[3],
        'labelDown'         => $labels[4],
        'labelEdit'         => $labels[5],
      ],
    ];
    $element['#attached']['drupalSettings']['MM']['mmListInit'][$_mmlist_instance] = $settings;
    $element += [
      '#mm_list_instance' => $_mmlist_instance++,
      '#mm_list_class' => $class,
      '#mm_list_desc' => !empty($element['#description']) ? $element['#description'] : '',
    ];
    return $element;
  }

}