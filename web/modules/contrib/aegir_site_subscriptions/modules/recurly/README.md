# Documentation for Aegir Site Subscriptions: Recurly

## Installation and Set-up

1. Enable this module.
1. Follow the instructions in [the base module's README](https://matteobrusa.github.io/md-styler/?url=cgit.drupalcode.org/aegir_site_subscriptions/plain/README.md).
1. Set up your [Recurly](https://recurly.com/) account, by configuring it as desired.
1. Connect to the Recurly service (configured above, in the Recurly
Configuration section) at Administration » Configuration » Web services »
Recurly.
1. Add the *Access Recurly subscription pages* permission to the role for subscribing users.
1. In each plan's configuration, in the Hosted Payment Pages section, be sure to
set the *Return URL after success* field to the following, where `example.com`
is your site running this module:
    * `https://example.com/subscription/success`
