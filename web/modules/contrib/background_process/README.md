<?php

/**
 * @file
 * Module Installation Note.
 */
?>
INTRODUCTION
------------

The Background Process module provides an API and hooks
which allow running code or cron jobs as a background process.
Which means that the process is not called via the web server like
Drupal's cron,and therefore isn't subject to socket timeout
and other timeout issues related to the web server and PHP configuration.

INSTALLATION
------------

Download and install as usual.  Composer install is forthcoming.

DEPENDENCIES
------------

Progress (https://www.drupal.org/project/progress)

USAGE
-----

Executing a background process:

<?php
$handle = background_process_start([$myobject, 'mymethod']);

$handle = background_process_start(['myclass', 'mystaticfunction']);

?>


Executing arbitrary http requests:

The following code shows how to fetch data
via http in "parallel" (asynchronous non-blocking mode).
It uses an API similar to that of HTTPRL but "slightly" different,
in order for the caller to gain more control of the requests.
Plans to join forces with HTTPRL are in place.

Drupal way: blocking/synchronous:

<?php
$r = [];
for ($i = 0; $i < 10; $i++) {
  $response = \Drupal::httpClient()->get('http://www.example.com/stuff/' . $i, ['headers' => ['Accept' => 'text/plain']]);
  $r[] = (string) $response->getBody();
}
print_r($r);
?>


Background process way:
non-blocking/a-synchronous (limited to 5 concurrent connections):

<?php
$r = [];
for ($i = 0; $i < 10; $i++) {
  $r[] = background_process_http_request('http://www.example.com/stuff/' . $i, ['postpone' => TRUE]);
}
background_process_http_request_process($r, ['limit' => 5]);
print_r($r);
?>


Background process way:
non-blocking/a-synchronous with callback (limited to 5 concurrent connections):

<?php

/**
 * Implements for Callback.
 */
function mycallback($result) {
  // Do something with $result.
}

$r = [];
for ($i = 0; $i < 10; $i++) {
  $r[] = background_process_http_request('http://www.example.com/stuff/' . $i, ['postpone' => TRUE, 'callback' => 'mycallback']);
}
background_process_http_request_process($r, ['limit' => 5]);
print_r($r);
?>


Pool management:

In multiserver environments, it is possible to delegate requests to specific
hosts by declaring service groups and service hosts in the settings file.
Example

<?php
$settings['background_process_service_hosts'] = [
  'ultimate_cron_poorman' => [
    'base_url' => 'http://192.168.1.100',
    'http_host' => 'mysite.example.com',
  ],
  'www1' => [
    'base_url' => 'http://my-username:my-password@192.168.1.101',
    'http_host' => 'mysite.example.com',
  ],
  'www2' => [
    'base_url' => 'http://192.168.1.102',
    'http_host' => 'mysite.example.com',
  ],
  // Apache server status declaration for www1.
  'www1_ass' => [
    'base_url' => 'http://192.168.1.101/server-status',
    'http_host' => '192.168.1.101',
  ],
];

$settings['background_process_service_groups'] = [
  'default' => [
    'hosts' => ['ultimate_cron_poorman', 'www1', 'www2'],
  ],
  'cron' => [
    'hosts' => ['ultimate_cron_poorman'],
  ],
  'services' => [
    'hosts' => ['www1', 'www2'],
  ],
];
?>


Default service host:

If a process is started using a service host which is not defined,
the service host "default" will be used. If "default" is not defined in the 
"background_process_service_hosts" variable, Background Process will fallback
to the "determined default" service host, and ultimately $base_url.

The "determined default" service host, is a service host
which Background Process tries to determine either upon module enable,
or manually through the "Determine default service host"
button on the settings page.
Load balancing

Background Process offers load balancing.
Background Process implements a load balancer based on random choice.
Background Process Apacher Server Status implements
a load balancer based on most idle workers.

Modules can implement their own kind of load balancing
if they wish by implementing hook_service_group().
See the implementation of the load balancers in the
Background Process and Background Process Apacher Server Status module.

The random load balancer from the Background Process
module is used if none is specified.

<?php
$settings['background_process_service_groups'] = [
  // "default" uses the "mymodule_myloadbalancermethod" load balancer.
  'default' => [
    'hosts' => ['ultimate_cron_poorman', 'www1', 'www2'],
    'method' => 'mymodule_myloadbalancermethod',
  ],
  // "cron" uses the "background_process_service_group_random" load balancer
  // (not relevant, as there's only one host though)
  'cron' => [
    'hosts' => ['ultimate_cron_poorman'],
  ],
  // "services" uses the "background_process_ass_service_group_idle" balancer.
  'services' => [
    'hosts' => ['www1', 'www2'],
    'method' => 'background_process_ass_service_group_idle',
  ],
  // "misc" uses the "background_process_service_group_random" load balancer.
  'misc' => [
    'hosts' => ['www1', 'www2'],
  ],
];
?>


BUNDLED Modules
---------------

Background Batch:

This modules takes over the existing Batch API and
runs batch jobs in a background process.
This means that if you leave the batch page, the jobs continues,
and you can return to the progress indicator later.

Batch jobs are delegated to the service host "background_batch" if defined.

To programmatically launch a background batch job,
just use background_batch_process_batch() instead of batch_process.
Snippet from Batch API:

<?php

/**
 * Implements Batch Example.
 */
function batch_example($options1, $options2, $options3, $options4) {
  $batch = [
    'operations' => [
      ['batch_example_process', [$options1, $options2]],
      ['batch_example_process', [$options3, $options4]],
    ],
    'finished' => 'batch_example_finished',
    'title' => t('Processing Example Batch'),
    'init_message' => t('Example Batch is starting.'),
    'progress_message' => t('Processed @current out of @total.'),
    'error_message' => t('Example Batch has encountered an error.'),
    'file' => drupal_get_path('module', 'batch_example') . '/batch_example.inc',
  ];
  batch_set($batch);
  background_batch_process_batch('node/1');
}

?>


Background Process Apache Server Status:

In case a background process dies in such a way that cleanup failed,
this module checks if the process is running by using 
the apache mod_status module. ExtendedStatus must be on.

A definition per service host is needed for Apache Server Status to work.
The name of the apache server status service host
is the correlating name + '_ass'. See the above code example.

CAVEATS
-------

Load balancing:

Sessions cannot be carried through from one vhost to another.
This can cause problems when using a
specific service group for Background Batch,
if the vhosts in the service group differ.

IIS:

When using IIS, you may want to configure your webserver to
allow double escaping (allowDoubleEscaping="true").
If not, background process handles that contain special characters
(e.g. ':', '/', etc.) will not work.

Cron:

If you're looking for a way to launch cron jobs in a background process,
checkout Ultimate Cron which uses event subscribers for running cron jobs.
