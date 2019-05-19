INTRODUCTION
------------

This module provides a configuration entity, **Integration**, and a connection
service, **Connection**, which, when combined, will allow a developer to set up
integrations and connect to them with ease.

When used correctly, this module will save you from having to add connection
details in your code, and will put control of integrations into site
administrators' hands. It reduces the amount of hardcoding required and allows
the config integrations to be stored as exportable config.

There is an interface which allows administrators to view and edit the
integrations. On its own, this module does nothing except to provide a framework
for connecting to other sites.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/simple_integrations

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/simple_integrations


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * This module is mostly useful for REST connections. If you need to extend this
   for use with SOAP connections, the Meng AsyncSoap library works really well:
   https://packagist.org/packages/meng-tian/async-soap-guzzle


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * This module provides a configuration entity type, Integration. There is no
   way to add these entities through the user interface, but there is an admin
   interface which lists all Integration entities on the site, and provides a
   way of testing the connections.

   - If you want to create an Integration entity, you must add it in the
     `config/install/` directory of a custom module. These values are editable
     through the user interface, under Configuration > Integrations.

     There is an example config file in the `config/example/` directory which
     will give you an idea of how to structure the config file. Yours should be
     called `simple_integrations.integration.example.yml` where `example` is the
     ID of the integration as defined in the file.

   - An Integration must include an external end point and an authentication
     type. If no authentication is required, then you can set it to 'none'.

   - If an Integration is not set to 'Active', no requests should be made. There
     is some rudimentary prevention in place for GET requests in this situation.

   - If an Integration is in 'debug mode', you can trigger logging. This is
     useful for recording which requests are made with which parameters etc. It
     os up to the developer to make sure that debug logging happens - it's just
     a useful flag.

 * There is a Connection client which extends Drupal's core httpClient service,
   providing some default functions to configure any requests you may make.

   - In order to create a Connection, you must provide a valid Integration. The
     Integration is the source of any configuration for a Connection, so you
     won't be able to create a Connection unless you've got an Integration to
     feed it. (If you don't need to provide details from an Integration, just
     use Drupal's core httpClient service.)

   - There are two ways to create a Connection. You can create it on a page
     using a controller, by extending the **ConnectionController** class. See
     `src/Controller/ConnectionTestController` for an example of this. You will
     have a fully-fledged Connection client that is linked to the Integration
     you provide.

   - Alternatively, you can instantiate it directly:

        $client = new ConnectionClient();
        $client->setIntegration($integration);
        $client->configure();

        $request = $client->get($client->endPoint, $client->config);


MAINTAINERS
-----------

Current maintainers:
 * Sophie Shanahan-Kluth (Sophie.SK) - https://drupal.org/user/1540896

This project has been sponsored by:
 * Microserve Ltd
   Microserve has been creating brilliant Drupal websites for companies from
   financial services to non-profits, NGOs and local government since 2004.
   Visit https://microserve.io/ for more information.
