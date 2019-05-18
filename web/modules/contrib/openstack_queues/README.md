[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/VeggieMeat/openstack_queues/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/VeggieMeat/openstack_queues/?branch=8.x-1.x)
[![Code Coverage](https://scrutinizer-ci.com/g/VeggieMeat/openstack_queues/badges/coverage.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/VeggieMeat/openstack_queues/?branch=8.x-1.x)
[![Build Status](https://travis-ci.org/VeggieMeat/openstack_queues.svg?branch=8.x-1.x)](https://travis-ci.org/VeggieMeat/openstack_queues)

Configuration
-------------

Connection information can be configured at admin/config/system/openstack-queues

If you need separate sets of credentials for different queues, they can be configured at
admin/config/system/openstack-queues/{queue_name} (i.e. admin/config/system/openstack-queues/update_fetch_tasks for
the Update queue).

The YAML format for configuration is as follows (using "default" and "update_fetch_tasks" as examples):

```
openstack_queues.settings.default:
  client_id: ''
  auth_url: 'https://identity.api.rackspacecloud.com/v2.0/'
  credentials:
    username: ''
    apiKey: ''
  region: ''
  prefix: ''
  
openstack_queues.settings.update_fetch_tasks:
  client_id: ''
  auth_url: 'https://identity.api.rackspacecloud.com/v2.0/'
  credentials:
    username: ''
    apiKey: ''
  region: ''
  prefix: ''
```

`client_id` must be a UUID to identify this particular API client. REQUIRED.
`auth_url` is the URL to the identity service.
`username` is your API username.
`apiKey` is your API key.
`region` is your Openstack region.
`prefix` is used to namespace your queues. It is OPTIONAL, but RECOMMENDED especially if you are running multiple sites
under the same account.

If you want to use Openstack Queues as the default queue manager, add the following to
your site's `settings.php`:

```
  $settings['queue_default'] = 'queue.openstack';
```

Alternatively, you can also use Openstack Queues for specific queues:

```
  $settings['queue_{queue_name}'];
```