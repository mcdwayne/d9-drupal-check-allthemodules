ABOUT OAUTH2
------------

OAuth 2.0 is the next evolution of the OAuth protocol which was originally
created in late 2006. OAuth 2.0 focuses on client developer simplicity while
providing specific authorization flows for web applications, desktop
applications, mobile phones, and living room devices. This specification is
being developed within the IETF OAuth WG and is based on the OAuth WRAP
proposal.

The latest version of the spec is can be found at:
<http://tools.ietf.org/html/draft-ietf-oauth-v2>

ABOUT DRUPAL OAUTH2.0
---------------------

OAuth2.0 implements the IETF OAuth2.0 draft fir yse wutg Drupal and acts as a
support module for other modules that wish to use OAuth2.0.

This module currently use the OAuth2.0 PHP library, which is originally
licensed under the MIT license, and it to be found at Google code:
<http://code.google.com/p/oauth2-php/>. The maintainers of this module will NOT
accept any patches to that library if they haven't been submitted to the
original project first. This is to avoid any license hijacking, and to further
the development of a common OAuth2.0 library for PHP.

This module support both standalone mode (e.g. acting as authorization server)
and proxy mode (e.g. acting as resourse server). Server operate in proxy mode
will redirect all its oauth2-php logic to remote authorization server,
including both access token issue, validate and expire. In order to provide
remote services for resource server, authorization server should install
Services 3.x (<http://drupal.org/project/services>).

This module also provides OAuth2.0 authentication for the Service 3.x module.

INSTALLATION
------------

\*\* WARNING! This version is not suitable for production use yet!

Before start with module installation please download oauth2-php from
<http://code.google.com/p/oauth2-php/> and extract it under either following
directory:

-   oauth2-php under your Drupal oauth2 modules, e.g. OAuth2.inc will therefore
    under: sites/all/modules/oauth2/oauth2-php/lib/OAuth2.inc
-   If Libraries API (<http://drupal.org/project/libraries>) is activated,
    extract oauth2-php under sites/all/libraries, e.g. OAuth2.inc will
    therefore under: sites/all/libraries/oauth2-php/lib/OAuth2.inc

OAuth2.0 coming with number of submodules, each provide different
functionalities:

-   Core functionalities:
    -   oauth2 : Provides oauth2-php library linkage and default setup, etc.
    -   oauth2\_provider: Provides functionality for OAuth2.0 when acting as a
        provider.
    -   oauth2\_consumer: Extend OAuth2.0 Server Identifer with consumer support.
-   Configuration data containers:
    -   oauth2\_client: Handle OAuth2.0 Client Identifer as Drupal entity.
    -   oauth2\_server: Handle OAuth2.0 Server Identifer as Drupal entity.
    -   oauth2\_scope: Handle OAuth2.0 Scope Identifer as Drupal entity.
-   3rd party modules integration:
    -   services\_oauth2: Provides OAuth2.0 authentication for the Services 3.x
        module.
    -   oauth2\_resources: Integrate with Services 3.x, provide APIs for remote
        resource server running in proxy mode.

Besides manually activate above submodules with your own combination, OAuth2.0
also provide following dummy packages for resolve submodule dependency:

-   oauth2\_authorize\_server: Activate all submodules for acting as
    authorization server. Support Services 3.x integration by default.
-   oauth2\_resource\_server: Activate all submodules for acting as resource
    server. By default it should combine with a remote authorization server
    for centralized token management.

CONFIGURATION
-------------

Typical configuration consists of an authorization server and a client in either proxy mode or client mode

-   Authorization server
-   Enable OAuth2.0 Authorization Server dummy package
-   Configure client identifier (admin/structure/oauth2)
-   Configure Services 3.x endpoint with OAuth2.0 authentication
-   Set OAuth2.0 provider mode to standalone mode

-   Client in proxy mode
-   Enable OAuth2.0 Proxy Mode dummy package
-   Configure server identifier
-   Set OAuth2.0 provider mode to proxy mode
-   Use the standard Drupal login form with username and password from Authorization server

-   Client in client mode
-   Enable OAuth2.0 Client Mode dummy package
-   Configure server identifier
-   Set OAuth2.0 provider mode to client mode
-   You will find a block named 'OAuth2 Login'. It will provide a link to the Authorization server.
    Log in the Authorization server and you will be redirected back to client side logged in

LIST OF MAINTAINERS
-------------------

PROJECT OWNER
M: Edison Wong <hswong3i@pantarei-design.com.com>
S: maintained
W: <http://pantarei-design.com/>
