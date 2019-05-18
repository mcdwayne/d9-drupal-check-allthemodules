Installing Acquia ContentHub
============================

ContentHub is a set of modules designed to work in concert to effectively move content and configuration from one Drupal site to another. These modules include the core "Acquia ContentHub" module, a "Publisher" module and a "Subscriber" module. While various other module are available, all functionally work with these other three modules to bring a set of predictable best-practices to bear when syndicating your content.

Requirements
^^^^^^^^^^^^

- PHP 7.0+
- Drupal 8.6
- Drush 9
- acquia/content-hub-php:~2
- zendframework/zend-diactoros:^1.8
- symfony/psr-http-message-bridge:^1.0
- drupal/depcalc:dev-1.x

The Acquia ContentHub Module
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Within the Acquia ContentHub module reside the settings, and basic handling to syndicate your Drupal entities. To get started with ANY ContentHub build, you must first install and configure the main Acquia ContentHub module.

To do this:

- Visit your "Extend" page and enable the module.
- Visit **Manage » Configuration » Web Services » Acquia ContentHub** and configure your connection to the Acquia ContentHub Service.

|unconfigured|

.. highlights:: **Before you continue** If you do not have ContentHub Service credentials, you'll need to `obtain them`_.

Configuring your ContentHub Connection
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Once you have obtained your credentials, you can proceed by submitting them through the configuration form. In addition to supplied credentials like Hostname, API Key and Secret Key, you will need to pick a unique (to your subscription) Client name to refer to this site on an on-going basis and ensure you've provided a publicly accessible URL for your site.

**Client Name**
Pick something that should communicate to any member of your team to which site this name refers. Save your configuration and continue. If after saving the configuration screen returns with a success, you are ready to move to the next step. If it doesn't, please jump to `troubleshooting`_.

**Publicly Accessible URL**
ContentHub will attempt to figure this out based on Drupal's knowledge, but this won't work in all situations and will obviously fail for local development builds. If you need to get local development working, please check our documentation on working with `ngrok`_.

.. _ngrok: https://docs.acquia.com/lift/contenthub/best-practices/

Webhooks & The Publicly Accessible URL
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Webhooks are how the ContentHub Service communicates with your Drupal site. Through webhooks, you'll be notified of successful content updates, indexing, purging and much more. Without a webhook, your site cannot operate dependably. This is the purpose of the **Publicly Accessible URL**. Armed with this information, the ContentHub Service will check if your site is publicly accessible, and if the registration information works properly, it will register the proper webhook end point on your site to send updates.

Why register a webhook?
^^^^^^^^^^^^^^^^^^^^^^^

Webhooks are used to communicate small but meaningful information payloads between your publisher and subscriber sites. These are crucial for both publishers and subscribers since they inform sites of various states in the content syndication life cycle. **A site without registered webhooks will not function properly.**

.. toctree::
   :maxdepth: 1
   :caption: Install Topics:

   install/obtain
   install/troubleshooting

.. |unconfigured| image:: install/contenthub-blank-settings.gif
.. _obtain them: install/obtain.html
.. _troubleshooting: install/troubleshooting.html
