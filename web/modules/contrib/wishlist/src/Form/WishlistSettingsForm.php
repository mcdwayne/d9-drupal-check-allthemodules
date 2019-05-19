<?php
/**
 * @file
 * Wishlist settings form.
 */

namespace Drupal\wishlist\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class WishlistSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'wishlist_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wishlist.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('wishlist.settings');

    $form["wishlist_help"] = array(
      '#type' => 'textarea',
      '#title' => t("Explanation or submission guidelines"),
      '#default_value' => $config->get("wishlist_help"),
      '#cols' => 55,
      '#rows' => 4,
      '#description' => t("This text will be displayed at the top of the wishlist item submission form.  Useful for helping or instructing your users."),
    );

    $form["wishlist_block_max_names"] = array(
      '#type' => 'textfield',
      '#title' => t("Maximum number of names to show in Wishlists block"),
      '#default_value' => $config->get("wishlist_block_max_names"),
      '#size' => 2,
      '#maxlength' => 2,
      '#description' => t("The maximum number of wishlists to display in the block."),
    );

    $form["wishlist_item_list_max_description"] = array(
      '#type' => 'textfield',
      '#title' => t("Item list description limit"),
      '#default_value' => $config->get("wishlist_item_list_max_description"),
      '#size' => 2,
      '#maxlength' => 3,
      '#description' => t("Limit the length of the description that is shown on the view of all wishlist items."),
    );

    $form['wishlist_currency'] = array(
      '#type' => 'fieldset',
      '#title' => t('Currency'),
      '#collapsible' => FALSE,
    );
    $form['wishlist_currency']["wishlist_show_currency"] = array(
      '#type' => 'checkbox',
      '#title' => t('Show the currency field'),
      '#return_value' => TRUE,
      '#default_value' => $config->get("wishlist_show_currency"),
      '#description' => t('By default the currency field for the price can be set by the user on each wishlist item.  If your site only needs to deal in a single currency, you can hide the field.  All entries will be saved with the default currency set below.'),
    );

    $form['wishlist_currency']["wishlist_default_currency"] = array(
      '#type' => 'textfield',
      '#title' => t('Default currency'),
      '#default_value' => $config->get("wishlist_default_currency"),
      '#size' => 3,
      '#maxlength' => 3,
      '#description' => t("Enter the default three letter ISO currency code for new wishlist items."),
    );

    $form['wishlist_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Misc. options'),
      '#collapsible' => FALSE,
    );

    $form['wishlist_options']["wishlist_url_in_new_window"] = array(
      '#type' => 'checkbox',
      '#title' => t("Open URL links in new window"),
      '#return_value' => TRUE,
      '#default_value' => $config->get("wishlist_url_in_new_window"),
      '#description' => t("When checked clicking on either the primary or secondary URL fields will open a new browser window."),
    );

    $form['wishlist_hideopts'] = array(
      '#type' => 'fieldset',
      '#title' => t('Item purchased status protection options'),
      '#collapsible' => FALSE,
    );
    $form['wishlist_hideopts']["wishlist_hide_purchase_info_anonymous"] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide the purchase information from anonymous users'),
      '#return_value' => TRUE,
      '#default_value' => $config->get("wishlist_hide_purchase_info_anonymous"),
      '#description' => t('Check this box to hide the purchase information about an item for anonymous users.  When this is checked, only authenticated users will be able to see whether an item remains available to purchase.  This will also remove all links from purchase URLs to prevent an unwitting anonymous user from purchasing an item without indicating it was purchased.  This is a way to prevent your users from peeking at the wishlist by visiting the site anonymously.'),
    );

    $form['wishlist_hideopts']['wishlist_hide_purchase_info_own'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide the purchase information from the user on their own wishlist by default'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('wishlist_hide_purchase_info_own'),
      '#description' => t('Select this box to hide the purchase information about an item for the owner of the wishlist.  When this is checked users will not see the purchased information on their wishlist entries by default.  They will need to explicitly choose to reveal purchased items.'),
    );
    $form['wishlist_hideopts']['wishlist_hide_admins_own_items'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hide your (the admin) wishlist items when viewing the wishlist administration screen'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('wishlist_hide_admins_own_items'),
      '#description' => t('This only applies to site administrators.  If checked, the site administrator will not see information about their own items in the wishlist administration screen'),
    );

    $form["wishlist_item_expire_days"] = array(
      '#type' => 'textfield',
      '#title' => t('Days before a fully purchased item is hidden'),
      '#default_value' => $config->get('wishlist_item_expire_days'),
      '#size' => 2,
      '#maxlength' => 2,
      '#description' => t("The number of days to leave an item on a user's public wishlist after it has been fully purchased.  After this many days, the item will be moved to the user's private wishlist.  It will not be deleted.  Set to 0 to disable automatic purchased item hiding."),
    );

    $form["wishlist_display_count"] = array(
      '#type' => 'textfield',
      '#title' => t('Number of items to display in the user\'s wishlist summary'),
      '#default_value' => $config->get('wishlist_display_count'),
      '#size' => 2,
      '#maxlength' => 2,
      '#description' => t('This is the number of items to display on the wishlist summary page.  This value will be applied to both the public wishlist and the user\'s private wishlist.'),
    );

    $form['wishlist_table'] = array(
      '#type' => 'fieldset',
      '#title' => t('Control which columns are displayed'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $columns = array(
      'wishlist_show_action' => t('Action'),
      'wishlist_show_title' => t('Title'),
      'wishlist_show_description' => t('Description'),
      'wishlist_show_priority' => t('Priority'),
      'wishlist_show_cost' => t('Cost'),
      'wishlist_show_quantity' => t('Quantity'),
      'wishlist_show_urls' => t('URLs'),
      'wishlist_show_updated' => t('Last updated'),
    );

    // @todo check array in schema
    $form['wishlist_table']['wishlist_showcolumn'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Show the following columns in the wishlist table view'),
      '#default_value' => $config->get('wishlist_showcolumn'),
      '#options' => $columns,
      '#description' => t('This controls which column show up in the wishlist summary view.'),
    );

    $form['wishlist_user_name_token'] = array(
      '#type' => 'textfield',
      '#title' => t('Tokens for user name'),
      '#default_value' => $config->get('wishlist_user_name_token'),
      '#size' => 60,
      '#maxlength' => 255,
      '#description' => t('Replace giftee user name with tokens. Token module must be enabled. Leave blank to use standard user name.'),
    );

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['wishlist_user_name_token_help'] = array(
        '#title' => t('Replacement patterns'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      $form['wishlist_user_name_token_help']['help'] = array(
        '#theme' => 'token_tree',
        '#token_types' => array('user'),
        '#global_types' => FALSE,
        '#click_insert' => TRUE,
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('wishlist.settings');
    $values = $form_state->getValues();
    $config->set('wishlist_help', $values['wishlist_help']);
    $config->set('wishlist_block_max_names', $values['wishlist_block_max_names']);
    $config->set('wishlist_item_list_max_description', $values['wishlist_item_list_max_description']);
    $config->set('wishlist_show_currency', $values['wishlist_show_currency']);
    $config->set('wishlist_default_currency', $values['wishlist_default_currency']);
    $config->set('wishlist_url_in_new_window', $values['wishlist_url_in_new_window']);
    $config->set('wishlist_hide_purchase_info_own', $values['wishlist_hide_purchase_info_own']);
    $config->set('wishlist_hide_admins_own_items', $values['wishlist_hide_admins_own_items']);
    $config->set('wishlist_item_expire_days', $values['wishlist_item_expire_days']);
    $config->set('wishlist_display_count', $values['wishlist_display_count']);
    $config->set('wishlist_showcolumn', $values['wishlist_showcolumn']);
    $config->set('wishlist_user_name_token', $values['wishlist_user_name_token']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}