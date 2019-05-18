# Healthcheck

Don't just hope your site is healthy, give it a healthcheck!

Healthcheck is like your site's own personal physician. It periodically runs checks against your site to determine if something has changed, gone wrong, or been misconfigured.

## Installation

Install Healthcheck like you would any other Drupal 8 module:

1. Download the module using one of the following two methods:
   * Download the module to `<your_site>/modules`
   * Use Composer: `composer require drupal/healthcheck`
2. Enable the module under **Admin &gt; Extend**
3. Under **Admin &gt; People**, grant the **Access Healthcheck** permission as needed.

## Basic Use

By default, Healthcheck runs in "ad hoc mode", where reports are only run on demand. 

To run an ad hoc healthcheck:

1. Log in as someone with the **Access Healthcheck** permission.
2. Navigate to **Admin &gt; Reports &gt; Healthcheck**.

## Background reporting

The real power of Healthcheck is to run new reports in the background.
This allows you site to be constantly monitored for best practices,
usage, and performance configuration. 

To enable background reports:

1. Login as someone with the **Access Healthcheck** permission.
2. Navigate to **Admin &gt; Config &gt; System &gt; Healthcheck**.
3. Under **Background Processing**, select how often to run Healthcheck
in the background.

## Enabling notifications

By default, Healthcheck will write to the results of background checks
to the Drupal log. This is simple, but not the only way to get reports.

Healthcheck includes several modules that provide additional notification
methods:

* **Healthcheck Email** sends background reports and critical findings
to you via email.
* **Healthcheck Webhook** posts critical findings to a JSON endpoint such
as Zapier. Zapier can then be configured to forward the message to a chat
system like Slack.

To configure notifications:

1. Login as someone with admin authority.
2. Navigate to **Admin &gt; Extend**, and enable one or more of the above notification modules as needed.
3. Navigate to **Admin &gt; Config &gt; System &gt; Healthcheck**.
4. Enable **Background processing**.
5. Configure notifications as needed.

## Historical reporting

To log how the health of your site changes over time, you can use the included
**Healthcheck Historical** module.

To enable historical reporting:

1. Login as someone with admin authority.
2. Navigate to **Admin &gt; Extend**, and enable **Healthcheck Historical**.
3. Navigate to **Admin &gt; Config &gt; System &gt; Healthcheck**.
4. Enable **Background processing**.
5. Under **Historical Reporting** select how long reports should be retained.
