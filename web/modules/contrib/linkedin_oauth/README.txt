INSTALL:
========

- Create LinkedIn application: https://www.linkedin.com/developer/apps
    - enable all default application permissions
    - OAuth 2.0 Authorized Redirect URLs: your site url, trailing "user/linkedin-oauth/return" (ex.: http://example.com/user/linkedin-oauth/return)
- Install dependency: "composer install" or "composer require linkedinapi/linkedin"
- enable module
- configure the module: admin/config/people/linkedin-oauth
