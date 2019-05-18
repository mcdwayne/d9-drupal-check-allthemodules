<?php

namespace Drupal\finteza_analytics;

/**
 * Provides plugin configuration.
 */
class FintezaAnalyticsConfig {
  const PING_INSTALL_URL = 'https://content.mql5.com/tr?event=Plugin%2BDrupal%2BActivate&id=cbgspzdebnimbhhkhankjnebjfajvaceho&ref=https%3A%2F%2Fwww.finteza.com%2F';
  const PING_UNINSTALL_URL = 'https://content.mql5.com/tr?event=Plugin%2BDrupal%2BDeactivate&id=cbgspzdebnimbhhkhankjnebjfajvaceho&ref=https%3A%2F%2Fwww.finteza.com%2F';
  const API_URL = 'https://panel.finteza.com/register?utm_source=drupal.admin&utm_term=finteza.register&utm_content=finteza.plugin.drupal&utm_campaign=finteza.drupal';
  const REGISTRATION_URL = 'https://www.finteza.com/en/register?utm_source=drupal.admin&amp;utm_medium=link&amp;utm_term=finteza.register&amp;utm_content=finteza.plugin.drupal&amp;utm_campaign=finteza.drupal';
  const DASHBOARD_URL = 'https://panel.finteza.com?utm_source=drupal.admin&amp;utm_medium=link&amp;utm_content=finteza.plugin.drupal&amp;utm_term=finteza.panel&amp;utm_campaign=finteza.drupal';
  const PASSWORD_RECOVERY_URL = 'https://panel.finteza.com/recovery?utm_source=drupal.admin&amp;utm_medium=link&amp;utm_term=finteza.password.recovery&amp;utm_content=finteza.plugin.drupal&amp;utm_campaign=finteza.drupal';
  const PRIVACY_URL = 'https://www.finteza.com/en/privacy?utm_source=drupal.admin&amp;utm_medium=link&amp;utm_term=finteza.privacy.policy&amp;utm_content=finteza.plugin.drupal&amp;utm_campaign=finteza.drupal';
  const AGREEMENT_URL = 'https://www.finteza.com/en/agreement?utm_source=drupal.admin&amp;utm_medium=link&amp;utm_term=finteza.subscription.agreement&amp;utm_content=finteza.plugin.drupal&amp;utm_campaign=finteza.drupal';
  const WEBSITE_URL = 'https://www.finteza.com?utm_source=drupal.admin&utm_medium=link&utm_term=finteza.website&utm_content=finteza.plugin.drupal&utm_campaign=finteza.drupal';
  const DEMO_URL = 'https://panel.finteza.com/login?login=demo@finteza.com&pass=fintezademo7&utm_source=drupal.admin&utm_medium=link&utm_content=finteza.plugin.drupal&utm_term=finteza.demo&utm_campaign=finteza.drupal';
  const CKEDITOR_URL = 'https://www.drupal.org/project/ckeditor';

}
