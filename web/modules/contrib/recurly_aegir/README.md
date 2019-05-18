# Recurly Aegir Documentation

## Set-up

### Aegir Configuration

1. Install, enable and configure [Aegir
SaaS](https://www.drupal.org/project/hosting_services) as per [that module's
documentation](https://matteobrusa.github.io/md-styler/?url=cgit.drupalcode.org/hosting_services/plain/submodules/hosting_saas/README.md).
1. On your Aegir site at Administration » Hosting » SaaS » Site Handovers, set
the client variables as follows.  This module uses these variable names to
communicate the client information.
    * Initial client user e-mail variable: *client_email*
    * Initial client user name variable: *client_name*

### Recurly Configuration

1. Set up your [Recurly](recurly.com) account, by configuring it as desired.
1. In each plan's configuration, in the Hosted Payment Pages section, be sure to
set the *Return URL after success* field to the following, where `example.com`
is your site running this module:
    * `https://example.com/subscription/success`

### Module Configuration

1. Enable the module.
1. Add the *Site: Edit own content* permission to roles subscribing to plans
that will need sites created.
1. Ensure that the hostname of your Aegir site is covered by a
`$settings['trusted_host_patterns']` entry in your in your *settings.php*
or *settings.local.php*.
1. Connect to the Recurly service (configured above, in the Recurly
Configuration section) at Administration » Configuration » Web services »
Recurly.
1. Configure the Aegir service (configured above, in the Aegir Configuration
section) at Administration » Configuration » Web services » Recurly » Aegir.
