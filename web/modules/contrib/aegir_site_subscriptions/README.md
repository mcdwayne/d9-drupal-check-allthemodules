# Documentation for Aegir Site Subscriptions

## Set-up

### Aegir Configuration

These steps must be performed on the Aegir front-end site. 

1. Install, enable and configure [Aegir
SaaS](https://www.drupal.org/project/hosting_services) as per [that module's
documentation](https://matteobrusa.github.io/md-styler/?url=cgit.drupalcode.org/hosting_services/plain/submodules/hosting_saas/README.md).
1. On your Aegir site at Administration » Hosting » SaaS » Site Handovers, set
the client variables as follows.  This module uses these variable names to
communicate the client information.
    * Initial client user e-mail variable: *client_email*
    * Initial client user name variable: *client_name*

### Module Configuration

These steps must be performed on the site running this module, which is
accessible to your clients and allows them to choose and purchase their plans.
This site acts as a client for the purposes of communicating with the Aegir
server.

1. Enable whichever subscription service submodule you intend to use.  This
base module will get automatically enabled as part of that process.  For
example, if you intend to use [Recurly](https://recurly.com/), enable the *Aegir
Site Subscriptions: Recurly* (`aegir_site_subscriptions_recurly`) module.
1. Add the *Site: Edit own content* permission to roles subscribing to plans
that will need sites created.
1. Ensure that the hostname of your Aegir site is covered by a
`$settings['trusted_host_patterns']` entry in your in your `local.settings.php`.
1. Configure the Aegir service at Administration » Configuration » Web services
» Aegir.
1. Follow any additional instructions in your chosen subscription provider's
README.  For example, if you're using Recurly, follow [that README](https://matteobrusa.github.io/md-styler/?url=cgit.drupalcode.org/aegir_site_subscriptions/plain/modules/recurly/README.md).

## Development

### Adding a new subscription provider

To add a new subscription provider, the simplest method would be to copy one of
the existing submodules to a new directory, and modify the copy to match how
the new service works.  The process is basically:

1. Create a new module in `modules/`.
1. Add the service's webhook notification handlers to call the base module's API.
    * Look at an existing provider for examples of what needs to be done.
    * Common functionality can be placed in the base module to [eliminate duplication](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself).
1. Write a plugin that implements [SubscriptionProviderInterface](https://cgit.drupalcode.org/aegir_site_subscriptions/tree/src/Plugin/SubscriptionProviderInterface.php).
