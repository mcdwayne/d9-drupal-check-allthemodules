<?php

namespace Drupal\bandsintown\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Provides Bandsintown Block.
 *
 * @Block(
 *   id = "bandsintown_block",
 *   admin_label = @Translation("Bandsintown"),
 * )
 */
class BandsintownBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $service = \Drupal::service('bandsintown.helper');
    $settings = array();
    foreach ($this->getConfiguration() as $setting => $value) {
      if ($service->isBandsintown($setting, 'data') || $service->isBandsintown($setting, 'button')) {
        $settings[$setting] = $value;
      }
    }

    // Retrieve "widget_version" module setting.
    $module_config = \Drupal::config('bandsintown.settings');
    $version = $module_config->get('widget_version') ? '_v2' : '';

    $block = array();
    $block['#theme'] = 'bandsintown';
    $block['#settings'] = $settings;
    $block['#attached']['library'][] = 'bandsintown/bit_widget' . $version;
    foreach ($this->getConfiguration() as $key => $value) {
      $block['#' . $key] = $value;
    }
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('access bandsintown')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $service = \Drupal::service('bandsintown.helper');
    $settings = array();
    $bandsintown_settings = $service->bandsintownSettings();
    foreach ($bandsintown_settings as $key => $setting) {
      $settings[$key] = $setting['value'];
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $module_config = \Drupal::config('bandsintown.settings');

    $url = Url::fromUri('http://www.bandsintown.com/facebookapp?came_from=' . BANDSINTOWN_FACEBOOKAPP_CAME_FROM, ['attributes' => ['target' => '_blank']]);
    $fb_app_link = \Drupal::service('link_generator')->generate(t('Get the Facebook app'), $url);

    $form['tour_settings'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Tour Dates Widget settings'),
      '#open'  => TRUE,
    );
    $form['tour_settings']['data_artist'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget artist'),
      '#default_value' => $config['data_artist'],
      '#description'   => $this->t('Artist title'),
      '#required'      => TRUE,
    );
    $form['tour_settings']['data_display_limit'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Tour Dates Widget display limit'),
      '#default_value' => $config['data_display_limit'],
      '#description'   => $this->t('Number of shows to display. if the artist has more concerts than the limit, a "Show All Dates" link will appear below the concerts to expand the list.'),
    );
    $form['tour_settings']['data_text_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget text color'),
      '#default_value' => $config['data_text_color'],
      '#description'   => $this->t('Color of the text inside the widget.'),
    );
    $form['tour_settings']['data_link_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget link color'),
      '#default_value' => $config['data_link_color'],
      '#description'   => $this->t('Color of the links inside the widget.'),
    );
    $form['tour_settings']['data_separator_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Tour Dates Widget separator color'),
      '#default_value' => $config['data_separator_color'],
      '#description'   => $this->t('Border color separating table rows.'),
    );
    $form['tour_settings']['data_div_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget div id'),
      '#default_value' => $config['data_div_id'],
      '#description'   => $this->t('This allows you to specify a div for the widget`s content to appear in when it is rendered. if not given, the widget will be rendered in-place as the page loads.'),
    );
    $form['tour_settings']['data_facebook_page_id'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Tour Dates Widget Facebook page id'),
      '#default_value' => $config['data_facebook_page_id'],
      '#description'   => $this->t('This is used to lookup an artist by Facebook page id. If found, the artist with the matching page id will be used, otherwise the artist name will be used. The data-artist param is still required when using this option.'),
    );
    // Tour Widget v2 only
    if ($module_config->get('widget_version')) {
      $form['tour_settings']['data_link_text_color'] = array(
        '#type'          => 'color',
        '#title'         => $this->t('Tour Dates Widget link text color'),
        '#default_value' => $config['data_link_text_color'],
        '#description'   => $this->t('Text color for the event buttons.'),
      );
      $form['tour_settings']['data_background_color'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget background color'),
        '#default_value' => $config['data_background_color'],
        '#description'   => $this->t('Background color for the widget.'),
      );
      $form['tour_settings']['data_popup_background_color'] = array(
        '#type'          => 'color',
        '#title'         => $this->t('Tour Dates Widget popup background color'),
        '#default_value' => $config['data_popup_background_color'],
        '#description'   => $this->t('Background color for event popup pages.'),
      );
      $form['tour_settings']['data_font'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget font name'),
        '#default_value' => $config['data_font'],
        '#description'   => $this->t('Font for the widget.'),
      );
      $form['tour_settings']['data_widget_width'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget width'),
        '#default_value' => $config['data_widget_width'],
        '#description'   => $this->t('Widget width in CSS width format i.e. "350px" or "50%". Switches to mobile friendly layout at 414px.'),
      );
      $form['tour_settings']['data_display_local_dates'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget display local dates'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_display_local_dates'],
        '#description'   => $this->t('If set to true, the browser will prompt the user for location information and show local events at the top of the event list.'),
      );
      $form['tour_settings']['data_display_past_dates'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget display past dates'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_display_past_dates'],
        '#description'   => $this->t('If set to true, shows past dates in addition to upcoming dates.'),
      );
      $form['tour_settings']['data_auto_style'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget auto style'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_auto_style'],
        '#description'   => $this->t('If true, the widget will use the parent page\'s styling to "guess" at good options for its styling: any additional specified options will take precedence over auto style options.'),
      );
    }
    else {
      $form['tour_settings']['data_force_narrow_layout'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget force narrow layout'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_force_narrow_layout'],
        '#description'   => $this->t('If true, concerts will always be displayed in narrow/2-column format.'),
      );
      $form['tour_settings']['data_bg_color'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget background color'),
        '#default_value' => $config['data_bg_color'],
        '#description'   => $this->t('Background color of the widget.'),
      );
      $form['tour_settings']['data_width'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget width'),
        '#default_value' => $config['data_width'],
        '#description'   => $this->t('Example: "350px" or "50%". Pixel width < 275px will always display concerts in narrow/3-column format.'),
      );
      $form['tour_settings']['data_bandsintown_footer_link'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget Bandsintown footer link'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_bandsintown_footer_link'],
        '#description'   => $this->t('If true, a table row with a link to bandsintown.com will be inserted below concerts and "show all dates" link.'),
      );
      $form['tour_settings']['data_notify_me'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget notify me'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_notify_me'],
        '#description'   => $this->t('If true, a link to track the artist using our Facebook app will appear when there are no upcoming or local dates. Get the @link', ['@link' => $fb_app_link]),
      );
      $form['tour_settings']['data_share_links'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Tour Dates Widget share links'),
        '#options'       => array(
          $this->t('FALSE'),
          $this->t('TRUE'),
        ),
        '#default_value' => $config['data_share_links'],
        '#description'   => $this->t('If true, links to share the "data-share-url" option on Facebook and Twitter will appear at the top of the widget.'),
      );
      $form['tour_settings']['data_share_url'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Tour Dates Widget share url'),
        '#default_value' => $config['data_share_url'],
        '#description'   => $this->t('Used for the link to share on Facebook and Twitter if the "data-share-links" option is true.'),
      );
    }
    // Track button
    $form['button_settings'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Track button settings'),
      '#open'  => TRUE,
    );
    $form['button_settings']['button_size'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button size'),
      '#options'       => array(
        'large' => $this->t('LARGE'),
        'small' => $this->t('SMALL'),
      ),
      '#default_value' => $config['button_size'],
      '#description'   => $this->t('Track button size'),
    );
    $form['button_settings']['button_display_tracker_count'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button display tracker count'),
      '#options'       => array(
        $this->t('FALSE'),
        $this->t('TRUE'),
      ),
      '#default_value' => $config['button_display_tracker_count'],
      '#description'   => $this->t('Track button display tracker count'),
    );
    $form['button_settings']['button_text_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button text color'),
      '#default_value' => $config['button_text_color'],
      '#description'   => $this->t('Track button text color'),
    );
    $form['button_settings']['button_background_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button background color'),
      '#default_value' => $config['button_background_color'],
      '#description'   => $this->t('Track button background color'),
    );
    $form['button_settings']['button_hover_color'] = array(
      '#type'          => 'color',
      '#title'         => $this->t('Track button hover color'),
      '#default_value' => $config['button_hover_color'],
      '#description'   => $this->t('Track button hover color'),
    );
    $form['button_settings']['button_height'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Track button height'),
      '#default_value' => $config['button_height'],
      '#description'   => $this->t('Track button height'),
    );
    $form['button_settings']['button_width'] = array(
      '#type'          => 'number',
      '#title'         => $this->t('Track button width'),
      '#default_value' => $config['button_width'],
      '#description'   => $this->t('Track button width'),
    );
    $form['button_settings']['button_scrolling'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button scrolling'),
      '#options'       => array(
        'no'  => $this->t('NO'),
        'yes' => $this->t('YES'),
      ),
      '#default_value' => $config['button_scrolling'],
      '#description'   => $this->t('Track button scrolling'),
    );
    $form['button_settings']['button_frameborder'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button frameborder'),
      '#options'       => array(
        $this->t('0'),
        $this->t('1'),
      ),
      '#default_value' => $config['button_frameborder'],
      '#description'   => $this->t('Track button frameborder'),
    );
    $form['button_settings']['button_style'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Track button style'),
      '#default_value' => $config['button_style'],
      '#description'   => $this->t('Track button style'),
    );
    $form['button_settings']['button_allowtransparency'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Track button allowtransparency'),
      '#options'       => array(
        $this->t('FALSE'),
        $this->t('TRUE'),
      ),
      '#default_value' => $config['button_allowtransparency'],
      '#description'   => $this->t('Track button allowtransparency'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $data_bg_color = $values['tour_settings']['data_bg_color'];
    $data_width = $values['tour_settings']['data_width'];
    $data_share_url = $values['tour_settings']['data_share_url'];
    $data_div_id = $values['tour_settings']['data_div_id'];
    $data_background_color = $values['tour_settings']['data_background_color'];
    $data_widget_width = $values['tour_settings']['data_widget_width'];

    if ($data_bg_color && !preg_match("/^(none|[#][0-9a-fA-F]{3}|[#][0-9a-fA-F]{6})$/", $data_bg_color)) {
      drupal_set_message(t('Wrong hex value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_bg_color', t('Wrong hex value!'));
    }
    if ($data_background_color && !preg_match("/^(transparent|[#][0-9a-fA-F]{3}|[#][0-9a-fA-F]{6})$/", $data_background_color)) {
      drupal_set_message(t('Wrong hex value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_background_color', t('Wrong hex value!'));
    }
    if ($data_width && !preg_match("/^([0-9]{1,3}px|[0-9]{1,3}%)$/", $data_width)) {
      drupal_set_message(t('Wrong hex value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_width', t('Wrong width value!'));
    }
    if ($data_widget_width && !preg_match("/^([0-9]{1,3}px|[0-9]{1,3}%)$/", $data_widget_width)) {
      drupal_set_message(t('Wrong hex value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_widget_width', t('Wrong width value!'));
    }
    if ($data_share_url && !(UrlHelper::isValid($data_share_url, TRUE))) {
      drupal_set_message(t('Wrong url value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_share_url', t('Wrong url value!'));
    }
    if ($data_div_id && !preg_match("/^([0-9a-z_-]+)$/", $data_div_id)) {
      drupal_set_message(t('Wrong div id value!'), 'error');
      // TODO: Get rid of drupal_set_message() when
      // https://www.drupal.org/node/2537732 is fixed.
      // setErrorByName is not working for now.
      $form_state->setErrorByName('data_div_id', t('Wrong div id value!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (array('tour_settings', 'button_settings') as $value) {
      foreach ($form_state->getValue($value) as $k => $v) {
        $this->setConfigurationValue($k, $v);
      }
    }
  }

}
