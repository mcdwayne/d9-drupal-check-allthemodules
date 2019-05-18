let config = {};

// CMS specific
if(window.govready) {
  config = window.govready;
  config.cms = 'wordpress';
  config.pluginText = 'Plugin';
  config.cmsNice = 'Wordpress';
  let url = '/wp-admin/admin-ajax.php?';
  if(process.env.NODE_ENV === 'development') {
    url = 'http://localhost:8080/wp-admin/admin-ajax.php?';
  }
  config.apiUrl = url + 'action=govready_proxy&endpoint=/sites/' + config.siteId + '/';
  config.apiUrlNoSite = url + 'action=govready_proxy&endpoint=';
  config.apiTrigger = url + 'action=govready_v1_trigger';
  config.pluginUrl = 'https://wordpress.org/plugins/';
}
else if(d8GovreadySettings || (window.Drupal && window.Drupal.settings.govready)) {
  config = d8GovreadySettings ? d8GovreadySettings : window.Drupal.settings.govready;
  console.log(config);
  config.cms = 'drupal';
  config.pluginText = 'Module';
  config.cmsNice = 'Drupal';
  let url = '/govready/api?';
  config.apiTrigger = '/govready/trigger?endpoint=/sites/' + config.siteId + '/';
  if(process.env.NODE_ENV === 'development') {
    url = 'http://172.17.0.4/govready/api?';
    config.apiTrigger = 'http://172.17.0.4/govready/trigger?endpoint=/sites/' + config.siteId + '/';
  }
  config.apiUrl = url + 'action=govready_proxy&endpoint=/sites/' + config.siteId + '/';
  config.apiUrlNoSite = url + 'action=govready_proxy&endpoint=';
  config.pluginUrl = 'https://drupal.org/project/';
}
else {
  config = {};
}

// Date format
config.dateFormat = 'MMMM Do YYYY';

export function updateNonce(nonce) {
  config.govready_nonce = none;
}

export default config;