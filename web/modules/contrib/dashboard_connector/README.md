# PNX Dashboard Connector

This module provide a connector to the PNX Dashboard API to send module update
status information.

## Configuration

The module can be configured from `/admin/config/development/pnx-dashboard`

By default, the module is disabled for development environments to avoid posting
checks for dev sites.

The recommended approach is to set the `enabled` flag, `client_id`, `site_id`, and `env`
via `settings.php`:

```php
// Dashboard settings.
$config['dashboard_connector.settings']['enabled'] = TRUE;
$config['dashboard_connector.settings']['base_uri'] = 'https://status.previousnext.com.au';
$config['dashboard_connector.settings']['client_id'] = 'agov_promo';
$config['dashboard_connector.settings']['site_id'] = 'agov_promo_' . getenv('SKIPPER_ENV') ?: 'local');
$config['dashboard_connector.settings']['env'] = getenv('SKIPPER_ENV');
$config['dashboard_connector.settings']['username'] = skpr_config('dashboard.username') ?: 'connector';
$config['dashboard_connector.settings']['password'] = skpr_config('dashboard.password') ?: 'secret';
```
