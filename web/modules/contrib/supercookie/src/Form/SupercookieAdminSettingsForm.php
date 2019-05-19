<?php
namespace Drupal\supercookie\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;

/**
 * Configure supercookie settings for this site.
 */
class SupercookieAdminSettingsForm extends ConfigFormBase {

  protected $supercookieManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'supercookie_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['supercookie.settings'];
  }

  /**
   * Administrative settings for Supercookie.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->supercookieManager = \Drupal::service('supercookie.manager');
    $path = \Drupal::moduleHandler()
      ->getModule('supercookie')
      ->getPath();

    $database = Database::getConnectionInfo();
    $date_formatter = \Drupal::service('date.formatter');

    drupal_set_message(t('Changing the %expiration, %cookie or %header values will invalidate all current supercookie data. Users will receive a new supercookie when they next visit the site.', array(
      '%expiration' => t('Cookie expiration'),
      '%cookie' => t('Cookie name'),
      '%header' => t('HTTP header'),
    )), 'warning', FALSE);

    $form['intro'] = array(
      '#markup' => '<p>' . t("Supercookie values will be unique across @report within the specified expiration interval.</p>", array(
        '@report' => Link::fromTextAndUrl(t('all site visitors'), Url::fromRoute('supercookie.admin_report'))->toString(),
      )),
    );

    $period_expire_strings = [];
    $period_expire = array(
      300, 900, 1800, 3600, 10800, 21600, 32400, 43200, 86400, 172800, 345600, 604800, 2419200, 7776000, 31536000,
    );
    foreach ($period_expire as $interval) {
      $period_expire_strings[] = $date_formatter->formatInterval($interval);
    }
    $period_expire = array(
      'calendar_day' => t('Calendar day'),
    ) + array_combine($period_expire, $period_expire_strings);

    $period_pageview_strings = [];
    $period_pageview = array(
      5, 10, 20, 30, 60, 120, 300, 900, 1800, 3600, 10800, 21600, 32400, 43200,
    );
    foreach ($period_pageview as $interval) {
      $period_pageview_strings[] = $date_formatter->formatInterval($interval);
    }
    $period_pageview = array_combine($period_pageview, $period_pageview_strings);

    $form['options'] = array(
      '#type' => 'details',
      '#title' => t('Options'),
      '#open' => TRUE,
    );

    $form['options']['supercookie_expire'] = array(
      '#type' => 'select',
      '#title' => t('Cookie expiration'),
      '#description' => t('All site visitors will have a unique supercookie within this interval.'),
      '#default_value' => $this->supercookieManager->config['supercookie_expire'],
      '#options' => $period_expire,
    );

    $form['options']['supercookie_pageview_average'] = array(
      '#type' => 'select',
      '#title' => t('Page view average'),
      '#description' => t("This interval represents the average page view time for users on your site. You can use the number reported by your site's metrics service provider, or make a reasonable guess."),
      '#default_value' => $this->supercookieManager->config['supercookie_pageview_average'],
      '#options' => $period_pageview,
    );

    $form['options']['supercookie_track_nid'] = array(
      '#type' => 'checkbox',
      '#title' => t('Track view count of node pages.'),
      '#default_value' => $this->supercookieManager->config['supercookie_track_nid'],
    );

    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $form['options']['supercookie_track_tid'] = array(
        '#type' => 'checkbox',
        '#title' => t('Track view count of terms on node pages.'),
        '#default_value' => $this->supercookieManager->config['supercookie_track_tid'],
      );
    }

    $form['options']['supercookie_honor_dnt'] = array(
      '#type' => 'checkbox',
      '#title' => t("Honor users' @dnt browser settings.", array(
        '@dnt' => Link::fromTextAndUrl(t('DNT'), Url::fromUri('https://en.wikipedia.org/wiki/Do_Not_Track'))->toString(),
      )),
      '#description' => t('It is recommended that responsible site owners leave this option enabled. In keeping with the spirit of this option, legacy data collected for individual users will be removed if they have the DNT browser setting enabled.'),
      '#default_value' => $this->supercookieManager->config['supercookie_honor_dnt'],
    );

    $form['options']['supercookie_geolocation'] = array(
      '#type' => 'checkbox',
      '#title' => t("Store users' geolocation coordinates."),
      '#description' => t('This option may or may not prompt a user to share geolocation, depending on browser agent and settings. You may wish to leave this disabled in some cases.'),
      '#default_value' => $this->supercookieManager->config['supercookie_geolocation'],
    );

    $mongodb_enable = <<<MONGODB
# Allow supercookie to set Composer dependencies and update to include the MongoDB driver:
mv $path/.composer.json $path/composer.json;
composer drupal-update;

# Add connection info in settings.php:
\$settings['mongodb'] = array(
  'default' => array(
    'host' => 'mongodb://localhost:27017',
    'db' => '{$database['default']['database']}',
  ),
);
MONGODB;

    $form['options']['supercookie_mongodb'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use MongoDB collection for data storage (recommended for high traffic sites).'),
      '#description' => t("If you have the @mongodb_extension installed and want to use MongoDB for data storage, do the following:<br><br><pre>@mongodb_enable</pre>.", array(
        '@mongodb_extension' => Link::fromTextAndUrl(t('<code>mongodb</code> PHP extension'), Url::fromUri('http://php.net/manual/en/set.mongodb.php'))->toString(),
        '@mongodb_enable' => $mongodb_enable,
      )),
      '#default_value' => $this->supercookieManager->config['supercookie_mongodb'],
      '#disabled' => (!extension_loaded('mongodb') || !file_exists(DRUPAL_ROOT . '/vendor/mongodb/mongodb/src/functions.php')),
    );

    $form['obfuscation'] = array(
      '#type' => 'details',
      '#title' => t('Obfuscation'),
      '#description' => t("Supercookies have been criticized as a means of silently gathering user data. While this module only stores a hash of the user agent and server-side variables collected from the user, you may still wish to obfuscate the default machine names and @alias (the current alias is %alias).", array(
        '@alias' => Link::fromTextAndUrl(t('alias the "supercookie" path'), Url::fromRoute('path.admin_overview'))->toString(),
        '%alias' => \Drupal::service('path.alias_manager')->getAliasByPath('/supercookie'),
      )),
      '#open' => TRUE,
    );

    $form['obfuscation']['supercookie_name_server'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie name'),
      '#default_value' => $this->supercookieManager->config['supercookie_name_server'],
      '#required' => TRUE,
      '#maxlength' => 20,
    );

    $form['obfuscation']['supercookie_name_header'] = array(
      '#type' => 'textfield',
      '#title' => t('HTTP header'),
      '#default_value' => $this->supercookieManager->config['supercookie_name_header'],
      '#required' => TRUE,
      '#maxlength' => 20,
    );

    return parent::buildForm($form, $form_state);
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

    $config = $this
      ->config('supercookie.settings')
      ->set('supercookie_expire', $form_state->getValue('supercookie_expire'))
      ->set('supercookie_pageview_average', $form_state->getValue('supercookie_pageview_average'))
      ->set('supercookie_name_header', $form_state->getValue('supercookie_name_header'))
      ->set('supercookie_name_server', $form_state->getValue('supercookie_name_server'))
      ->set('supercookie_honor_dnt', $form_state->getValue('supercookie_honor_dnt'))
      ->set('supercookie_geolocation', $form_state->getValue('supercookie_geolocation'))
      ->set('supercookie_mongodb', $form_state->getValue('supercookie_mongodb'))
      ->set('supercookie_track_nid', $form_state->getValue('supercookie_track_nid'))
      ->set('supercookie_track_tid', $form_state->getValue('supercookie_track_tid'))
      ->save();

    drupal_get_messages();
    drupal_set_message(t('The configuration options have been saved.'));

    $truncate = FALSE;
    if (!$truncate && $config->get('supercookie_expire') != $form['options']['supercookie_expire']['#default_value']) {
      $truncate = TRUE;
    }
    if (!$truncate && $config->get('supercookie_name_server') != $form['obfuscation']['supercookie_name_server']['#default_value']) {
      $truncate = TRUE;
    }
    if (!$truncate && $config->get('supercookie_name_header') != $form['obfuscation']['supercookie_name_header']['#default_value']) {
      $truncate = TRUE;
    }
    if ($truncate) {
      if ($config->get('supercookie_mongodb') && class_exists('\MongoDB\Client')) {
        $this->supercookieManager
          ->getMongoCollection()
          ->drop();
      }
      else {
        \Drupal::database()
          ->delete('supercookie')
          ->execute();
      }

      drupal_set_message(t('Cleared all supercookie sessions.'));
    }
    else {
      drupal_set_message(t('All data has been preserved.'));
    }

    parent::submitForm($form, $form_state);
  }

}
