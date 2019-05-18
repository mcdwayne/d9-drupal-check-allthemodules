<?php

namespace Drupal\bandsintown\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Plugin implementation of the 'bandsintown' widget.
 *
 * @FieldWidget(
 *   id = "bandsintown",
 *   module = "bandsintown",
 *   label = @Translation("Bandsintown"),
 *   field_types = {
 *     "bandsintown"
 *   }
 * )
 */
class BandsintownWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $url = Url::fromUri('http://www.bandsintown.com/facebookapp?came_from=' . BANDSINTOWN_FACEBOOKAPP_CAME_FROM, ['attributes' => ['target' => '_blank']]);
    $fb_app_link = \Drupal::service('link_generator')->generate(t('Get the Facebook app'), $url);

    $module_config = \Drupal::config('bandsintown.settings');

    $element += array(
      '#element_validate' => array(array(get_class($this), 'validateFormElement')),
    );
    $element['data_artist'] = [
      '#type'          => 'textfield',
      '#title'         => t('Artist'),
      '#required'      => TRUE,
      '#default_value' => isset($items[$delta]->data_artist) ? $items[$delta]->data_artist : '',
      '#placeholder'   => t('Artist'),
    ];
    $element['data_display_limit'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Tour Dates Widget display limit'),
      '#default_value' => isset($items[$delta]->data_display_limit) ? $items[$delta]->data_display_limit : 3,
      '#description'   => $this->t('Number of shows to display. if the artist has more concerts than the limit, a "Show All Dates" link will appear below the concerts to expand the list.'),
    );
    $element['data_text_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget text color'),
      '#default_value' => isset($items[$delta]->data_text_color) ? $items[$delta]->data_text_color : '#000000',
      '#description'   => $this->t('Color of the text inside the widget.'),
    );
    $element['data_link_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget link color'),
      '#default_value' => isset($items[$delta]->data_link_color) ? $items[$delta]->data_link_color : '#000000',
      '#description'   => $this->t('Color of the links inside the widget.'),
    );
    $element['data_separator_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget separator color'),
      '#default_value' => isset($items[$delta]->data_separator_color) ? $items[$delta]->data_separator_color : '#e9e9e9',
      '#description'   => $this->t('Border color separating table rows.'),
    );
    $element['data_div_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget div id'),
      '#default_value' => isset($items[$delta]->data_div_id) ? $items[$delta]->data_div_id : NULL,
      '#description'   => $this->t('This allows you to specify a div for the widget`s content to appear in when it is rendered. if not given, the widget will be rendered in-place as the page loads.'),
    );
    $element['data_facebook_page_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget Facebook page id'),
      '#default_value' => isset($items[$delta]->data_facebook_page_id) ? $items[$delta]->data_facebook_page_id : NULL,
      '#description'   => $this->t('This is used to lookup an artist by Facebook page id. If found, the artist with the matching page id will be used, otherwise the artist name will be used. The data-artist param is still required when using this option.'),
    );
    // Tour Widget v2 only
    if ($module_config->get('widget_version')) {
      $element['data_link_text_color'] = array(
        '#type'          => 'color',
        '#title'         => $this->t('Tour Dates Widget link text color'),
        '#default_value' => isset($items[$delta]->data_link_text_color) ? $items[$delta]->data_link_text_color : '#FFFFFF',
        '#description'   => $this->t('Text color for the event buttons.'),
      );
      $element['data_background_color'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget background color'),
        '#default_value' => isset($items[$delta]->data_background_color) ? $items[$delta]->data_background_color : 'transparent',
        '#description'   => $this->t('Background color for the widget.'),
      );
      $element['data_popup_background_color'] = array(
        '#type'          => 'color',
        '#title'         => $this->t('Tour Dates Widget popup background color'),
        '#default_value' => isset($items[$delta]->data_popup_background_color) ? $items[$delta]->data_popup_background_color : '#FFFFFF',
        '#description'   => $this->t('Background color for event popup pages.'),
      );
      $element['data_font'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget font name'),
        '#default_value' => isset($items[$delta]->data_font) ? $items[$delta]->data_font : 'Helvetica',
        '#description'   => $this->t('Font for the widget.'),
      );
      $element['data_widget_width'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget width'),
        '#default_value' => isset($items[$delta]->data_widget_width) ? $items[$delta]->data_widget_width : '100%',
        '#description'   => $this->t('Widget width in CSS width format i.e. "350px" or "50%". Switches to mobile friendly layout at 414px.'),
      );
      $element['data_display_local_dates'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget display local dates'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_display_local_dates) ? $items[$delta]->data_display_local_dates : FALSE,
        '#description'   => $this->t('If set to true, the browser will prompt the user for location information and show local events at the top of the event list.'),
      );
      $element['data_display_past_dates'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget display past dates'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_display_past_dates) ? $items[$delta]->data_display_past_dates : TRUE,
        '#description'   => $this->t('If set to true, shows past dates in addition to upcoming dates.'),
      );
      $element['data_auto_style'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget auto style'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_auto_style) ? $items[$delta]->data_auto_style : FALSE,
        '#description'   => $this->t('If true, the widget will use the parent page\'s styling to "guess" at good options for its styling: any additional specified options will take precedence over auto style options.'),
      );
    }
    else {
      $element['data_force_narrow_layout'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget force narrow layout'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_force_narrow_layout) ? $items[$delta]->data_force_narrow_layout : FALSE,
        '#description'   => $this->t('If true, concerts will always be displayed in narrow/2-column format.'),
      );
      $element['data_bg_color'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget background color'),
        '#default_value' => isset($items[$delta]->data_bg_color) ? $items[$delta]->data_bg_color : 'none',
        '#description'   => $this->t('Background color of the widget.'),
      );
      $element['data_width'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget width'),
        '#default_value' => isset($items[$delta]->data_width) ? $items[$delta]->data_width : '100%',
        '#description'   => $this->t('Example: "350px" or "50%". Pixel width < 275px will always display concerts in narrow/3-column format.'),
      );
      $element['data_bandsintown_footer_link'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget Bandsintown footer link'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_bandsintown_footer_link) ? $items[$delta]->data_bandsintown_footer_link : FALSE,
        '#description'   => $this->t('If true, a table row with a link to bandsintown.com will be inserted below concerts and "show all dates" link.'),
      );
      $element['data_notify_me'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget notify me'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_notify_me) ? $items[$delta]->data_notify_me : TRUE,
        '#description'   => $this->t('If true, a link to track the artist using our Facebook app will appear when there are no upcoming or local dates. Get the @link', ['@link' => $fb_app_link]),
      );
      $element['data_share_links'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget share links'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => isset($items[$delta]->data_share_links) ? $items[$delta]->data_share_links : TRUE,
        '#description'   => $this->t('If true, links to share the "data-share-url" option on Facebook and Twitter will appear at the top of the widget.'),
      );
      $element['data_share_url'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget share url'),
        '#default_value' => isset($items[$delta]->data_share_url) ? $items[$delta]->data_share_url : '',
        '#description'   => $this->t('Used for the link to share on Facebook and Twitter if the "data-share-links" option is true.'),
      );
    }
    // Track button
    $element['button_size'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button size'),
      '#options'       => array(
        'large' => $this->t('LARGE'),
        'small' => $this->t('SMALL'),
      ),
      '#default_value' => isset($items[$delta]->button_size) ? $items[$delta]->button_size : 'large',
      '#description'   => $this->t('Track button size'),
    );
    $element['button_display_tracker_count'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button display tracker count'),
      '#options'       => array(
        $this->t('FALSE'),
        $this->t('TRUE'),
      ),
      '#default_value' => isset($items[$delta]->button_display_tracker_count) ? $items[$delta]->button_display_tracker_count : TRUE,
      '#description'   => $this->t('Track button display tracker count'),
    );
    $element['button_text_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button text color'),
      '#default_value' => isset($items[$delta]->button_text_color) ? $items[$delta]->button_text_color : '#ffffff',
      '#description'   => $this->t('Track button text color'),
    );
    $element['button_background_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button background color'),
      '#default_value' => isset($items[$delta]->button_background_color) ? $items[$delta]->button_background_color : '#22cb65',
      '#description'   => $this->t('Track button background color'),
    );
    $element['button_hover_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button hover color'),
      '#default_value' => isset($items[$delta]->button_hover_color) ? $items[$delta]->button_hover_color : '#1dac56',
      '#description'   => $this->t('Track button hover color'),
    );
    $element['button_height'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Track button height'),
      '#default_value' => isset($items[$delta]->button_height) ? $items[$delta]->button_height : 32,
      '#description'   => $this->t('Track button height'),
    );
    $element['button_width'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Track button width'),
      '#default_value' => isset($items[$delta]->button_width) ? $items[$delta]->button_width : 165,
      '#description'   => $this->t('Track button width'),
    );
    $element['button_scrolling'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button scrolling'),
      '#options'       => array(
        'no'  => $this->t('NO'),
        'yes' => $this->t('YES'),
      ),
      '#default_value' => isset($items[$delta]->button_scrolling) ? $items[$delta]->button_scrolling : 'no',
      '#description'   => $this->t('Track button scrolling'),
    );
    $element['button_frameborder'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button frameborder'),
      '#options'       => array(
        $this->t('0'),
        $this->t('1'),
      ),
      '#default_value' => isset($items[$delta]->button_frameborder) ? $items[$delta]->button_frameborder : 0,
      '#description'   => $this->t('Track button frameborder'),
    );
    $element['button_style'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Track button style'),
      '#default_value' => isset($items[$delta]->button_style) ? $items[$delta]->button_style : 'border:none; overflow:hidden;',
      '#description'   => $this->t('Track button style'),
    );
    $element['button_allowtransparency'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button allowtransparency'),
      '#options'       => array(
        $this->t('FALSE'),
        $this->t('TRUE'),
      ),
      '#default_value' => isset($items[$delta]->button_allowtransparency) ? $items[$delta]->button_allowtransparency : TRUE,
      '#description'   => $this->t('Track button allowtransparency'),
    );
    return $element;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    $data_bg_color = $element['data_bg_color']['#value'];
    $data_width = $element['data_width']['#value'];
    $data_share_url = $element['data_share_url']['#value'];
    $data_div_id = $element['data_div_id']['#value'];
    $data_background_color = $element['data_background_color']['#value'];
    $data_widget_width = $element['data_widget_width']['#value'];

    if ($data_bg_color && !preg_match("/^(none|[#][0-9a-fA-F]{3}|[#][0-9a-fA-F]{6})$/", $data_bg_color)) {
      $form_state->setValueForElement($element['data_bg_color'], $data_bg_color);
      $form_state->setError($element['data_bg_color'], t('Wrong hex value!'));
    }
    if ($data_background_color && !preg_match("/^(transparent|[#][0-9a-fA-F]{3}|[#][0-9a-fA-F]{6})$/", $data_background_color)) {
      $form_state->setValueForElement($element['data_background_color'], $data_background_color);
      $form_state->setError($element['data_background_color'], t('Wrong hex value!'));
    }
    if ($data_width && !preg_match("/^([0-9]{1,3}px|[0-9]{1,3}%)$/", $data_width)) {
      $form_state->setValueForElement($element['data_width'], $data_width);
      $form_state->setError($element['data_width'], t('Wrong width value!'));
    }
    if ($data_widget_width && !preg_match("/^([0-9]{1,3}px|[0-9]{1,3}%)$/", $data_widget_width)) {
      $form_state->setValueForElement($element['data_widget_width'], $data_widget_width);
      $form_state->setError($element['data_widget_width'], t('Wrong width value!'));
    }
    if ($data_share_url && !(UrlHelper::isValid($data_share_url, TRUE))) {
      $form_state->setValueForElement($element['data_share_url'], $data_share_url);
      $form_state->setError($element['data_share_url'], t('Wrong url value!'));
    }
    if ($data_div_id && !preg_match("/^([0-9a-z_-]+)$/", $data_div_id)) {
      $form_state->setValueForElement($element['data_div_id'], $data_div_id);
      $form_state->setError($element['data_div_id'], t('Wrong div id value!'));
    }
  }

}
