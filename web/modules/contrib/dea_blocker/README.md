# Introduction
DEA Blocker protects Drupal form email fields by disallowing mailbox addresses
from a custom blacklist of Disposable Email Address domains.
Its main usage is to avoid user registration with temporary/disposable email
addresses (DEA).

It can also be used to protect other Drupal forms like comments, webforms and
newsletter subscriptions.

DEA Blocker has an internal blacklist used to validate form email fields and make
them invalid if their value is a mailbox from a blacklisted domain.
It is possible to customize the blacklist (domains, subdomains and regular
expressions are supported) and also the list of protected forms.

Blacklist can also be used to block valid (not DEA) domains like
`foo.com`,`bar.net`, etc.

DEA Blocker can be configured to automatically update its blacklist with a JSON file.
It comes preconfigured to use the public list available at
https://github.com/ivolo/disposable-email-domains.
See "Public list of DEA domains" below.


# Installation
Install the module and enable it as usual, then open its configuration page at
Configuration | People | DEA Blocker (/admin/config/people/dea_blocker).

Select which forms DEA Blocker will protect then add your own domains
(or paste a publicly available DEA list, see below) to the blacklist.


# Public list of DEA domains
A good list of DEA domains is available here:

- GitHub project: https://github.com/ivolo/disposable-email-domains
- JSON list: https://github.com/ivolo/disposable-email-domains/raw/master/index.json 
