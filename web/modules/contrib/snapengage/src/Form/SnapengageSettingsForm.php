<?php

/**
 * @file
 * Contains \Drupal\extlink\Form\SnapengageSettingsForm.
 */

namespace Drupal\snapengage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textfield;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Displays the extlink settings form.
 */
class SnapengageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'snapengage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $form['account'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
      '#open' => TRUE,
    );

    $form['account']['snapengage_widget_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Widget ID'),
      '#default_value' => \Drupal::config('snapengage.settings')->get('snapengage_widget_id'),
      '#description' => t('Your widget ID. It will look somewhat like this: "abcdefgh-1234-ijkl-5678-mnopqrstuvwx". You can find it at <a href="@url">@url</a>.', array('@url' => 'https://secure.snapengage.com/widget')),
    );

    $form['account']['snapengage_widget_language'] = array(
      '#type' => 'language_select',
      '#title' => t('Widget language'),
      '#default_value' => \Drupal::config('snapengage.settings')->get('snapengage_widget_language'),
      '#options' => array(
        'default' => t('Default language of site. With URL detection.'),
        'user' => t("User's default language"),
      ),
      '#description' => t('Select how to choose language.'),
    );

    // Render the role overview.
    $form['role_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Role specific visibility settings'),
      '#open' => TRUE,
    );

    $roles = user_role_names();
    $form['role_vis_settings']['snapengage_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Role specific visibility'),
      '#default_value' => \Drupal::config('snapengage.settings')->get('snapengage_roles'),
      '#options' => $roles,
      '#description' => t('Show widget only for the selected role(s). If you select none of the roles, then all roles will see the widget. If a user has any of the roles checked, the widget will be visible to the user.'),
    );

    // Page specific visibility configurations.
    $form['page_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Page specific visibility settings'),
      '#open' => TRUE,
    );

    $access = \Drupal::currentUser()->hasPermission('use PHP for SnapEngage visibility');
    $visibility = \Drupal::config('snapengage.settings')->get('snapengage_visibility', 0);
    $pages = \Drupal::config('snapengage.settings')->get('snapengage_pages', '');

    if ($visibility == 2 && !$access) {
      $form['page_vis_settings'] = array();
      $form['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
      $form['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $pages);
    }
    else {
      $options = array(t('Add to every page except the listed pages.'), t('Add to the listed pages only.'));
      $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page. Remember to start every URL with /. Also if you have langauages enabed you nedd to set wildcard infront of the URLs, fex.: '*/admin/....'.",
        array(
          '%blog' => 'blog',
          '%blog-wildcard' => 'blog/*',
          '%front' => '<front>',
        )
      );

      if (\Drupal::moduleHandler()->moduleExists('php') && $access) {
        $options[] = t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
        $description .= ' ' . t('If you choose the PHP-mode, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
      }

      $form['page_vis_settings']['snapengage_visibility'] = array(
        '#type' => 'radios',
        '#title' => t('Add widget to specific pages'),
        '#options' => $options,
        '#default_value' => $visibility,
      );
      $form['page_vis_settings']['snapengage_pages'] = array(
        '#type' => 'textarea',
        '#title' => t('Pages'),
        '#default_value' => $pages,
        '#description' => $description,
        '#wysiwyg' => FALSE,
      );
    }

    return parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::configFactory()->getEditable('snapengage.settings')
      ->set('snapengage_widget_id', $values['snapengage_widget_id'])
      ->set('snapengage_widget_language', $values['snapengage_widget_language'])
      ->set('snapengage_roles', $values['snapengage_roles'])
      ->set('snapengage_visibility', $values['snapengage_visibility'])
      ->set('snapengage_pages', $values['snapengage_pages'])
      ->save();
    parent::SubmitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['snapengage.settings'];
  }

}
