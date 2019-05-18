OAuth2 JWT SSO
------------
OAuth2 JWT Single Sign On Module configures Drupal to use remote and centralized authentication service. This module works with any SSO provider which uses OAuth2 as the authentication framework, and JWT as the Bearer token. Therefore, this module works with Drupal's own [OAuth 2.0](https://www.drupal.org/project/simple_oauth).

### Advantages:
- Unlike the legacy SSO solutions like [Bakery Single Sign-On System](https://www.drupal.org/project/bakery), OAuth2 JWT SSO does not have the limitation of SSO on within sub-domains
- The authentication provider server can be developed on any technology
- SSO works for both human users and web services

### Use Cases:
- Let your Drupal site to use external authentication provider developed with Node.js
- Configure your swam of Drupal microservicecs to use one centralized authentication
- Use [OAuth 2.0](https://www.drupal.org/project/simple_oauth) to provide a SSO solution to other technologies like Java. (In this use case, you do not need this module.)

### Supported Authentication Workflow:
- Password Grant: configure your Drupal login form to use remote authentication server.
- Authorization Code Grant: redirect your user to login on the authentication server

### Dependencies, and Installation:
- "league/oauth2-client": "2.2.*"
- "lcobucci/jwt": "^3.2"

To install this module with Composer,

Use composer update drupal/oauth2_jwt_sso --with-dependencies to update OAuth2 JWT SSO to a new release.

See [Using Composer in a Drupal project](https://www.drupal.org/node/2404989)for more information.

How does this work?
- [Stateless authentication with OAuth 2 and JWT - JavaZone 2015](https://www.slideshare.net/alvarosanchezmariscal/stateless-authentication-with-oauth-2-and-jwt-javazone-2015?next_slideshow=1)
- [Stateless Auth using OAuth2 & JWT](https://www.slideshare.net/gauravroy/stateless-auth-using-oauth2-jwt)
