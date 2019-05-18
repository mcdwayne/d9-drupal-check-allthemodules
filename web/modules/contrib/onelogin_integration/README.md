# README
Author: Kevin Kromjong  
Email: k.kromjong@youwe.nl  
Company: [Youwe](https://youwe.nl)  

## Introduction
There are a lot of modules for Drupal 7 that let you login using OneLogin. However, the modules that are there for Drupal 8 lack documentation, proper coding or don't even work correctly. Therefore, we, Youwe, tried to built or own. As a starting point, we used the working Drupal 7 module from OneLogin themselve, which can be found[here](https://github.com/onelogin/drupal-saml/tree/master/onelogin_saml).

## Index
* 1 - General information
  * 1.1 External library
* 2 - Installation
  * 2.1 Composer
  * 2.2 Drush
  * 2.3 Manual
* 3 - Workflow
* 4 - Usage
* 5 - File/folder overview
  * 5.1 - Config
  * 5.2 - Controller
  * 5.3 - Form
  * 5.4 - Src
  * 5.5 - Project root

## 1 - General information
**Library**  
The modules uses the onelogin-saml library. 

## 2- Installation
### 2.1 Composer
If you installed the module through Composer, everything should be there and working as expected. The module is called OneLogin SAML and can be found within the <i>OneLogin</i> package.
### 2.2 Drush
-- To be written --

The modules uses the onelogin-saml library. You need to include this as well with the following command: ```"onelogin/php-saml": "^2.11"```.
### 2.3 Manually
-- To be written --

The modules uses the onelogin-saml library. You need to include this as well with the following command: ```"onelogin/php-saml": "^2.11"```.

## 3 - Usage
The usage of the module is as follows:
- You install the module
- If caching is enabled on the website, there may be a possibility that you'll need to clear the cache for the configuration menu item to become visible.
- Go to the admin configuration and click on <i>OneLogin Integration</i> under <i>People</i> or go to <i>admin/config/system/onelogin_integration</i>.
- The fields that are necessary for a correct working, are:
  - Identity Provider (IdP) Entity Id
  - Single Sign On Service Url
  - X.509 Certificate  
  
  If correctly configured, those URL's are provided by OneLogin. The Single Log Out Service Url is optional.

- After inserting the URL's, you'll need to alter the settings under <i>Options</i>, <i>Attribute Mapping</i> and <i>Role Mapping</i>
- The Role Mapping displays all the roles on the current website. The input field is used for the roles that are coming from OneLogin. So, for example, if a user that wants to log in has the roles admin and blogger, and the role inserted under "Administrator" is "admin", the user gets admin rights. If you want the people with the role "blogger" to also be an admin, you can insert it as well. Make sure it's comma seperated and does not have a space after the comma.  

  All the other settings are optional.

- After you are done, save the page and, just to be sure, clear the cache. 
- Log out
- If you do not have the user login block on the homepage, go to the user login page (/user).
- If correctly installed, there is a link now under the <i>username</i> and <i>password</i> fields called <i>Log in using SAML</i>
- When clicked, it will try to log in and/or create a user in Drupal with the OneLogin credentials given. If the roles coming from OneLogin do not match the roles from Drupal, the account will be blocked. In other words: if the mapping cannot be applied, then it is not clear which role the user should get and therefore the user is blocked untill it is solved. The roles are being synced everytime a user logs in, so when you alter the roles in OneLogin, you only have to log in again to see the changes.     
- If there is an error, the error message will be propmted at the top of the page. If not or if the error is not clear enough, enable <i>Debug mode</i> in the settings of the OneLogin module or check the errorlogs of your application/server.

## 4 - Workflow
The workflow of the module is as follows:
- When you click on the <i>Log in using SAML</i> link, it will go to the SSO route and execute the SSO function in the OneLoginIntegrationController
- This service gets all the data and sends a request to the OneLogin_Saml2 library to take care of the authentication. The instance of this library is generated through the SAMLAuthenticatorFactory. If it succeeds, it sends a response.
- The response triggers the ACS route and the ACS method in the OneLoginIntegrationController accordingly. This will take care of the finalization of the authentication.
- Once this has been succesfully completed, which means that the data and account details are succesfully confirmed by OneLogin and the library, it will process the post-login actions defined in the AuthenticationService.
- Two things happen in this service: the roles are synced. So when roles are added/removed from a user in OneLogin or when the mapping in Drupal has changed, the module will apply it. The second one is creating a new user when there is no user found in Drupal with the email address provided from OneLogin. The UserService takes care of this.
- After those two actions are succesfully completed, the new user is logged in with the correct roles or the user is created but blocked, when the Drupal roles defined in the mapping do not match the roles coming from OneLogin.   
## 5 - File/folder overview
The project tree is as follows:
```
OneLogin Integration
│
│──── config
│     └──── install 
│          └──── onelogin_saml.settings.yml 
│
│──── src
│     └──── Controller
│     │     └──── OneLoginSAMLController.php
│     └──── Form
│     │    └──── OneLoginSAMLAdminForm.php
│     │   AuthenticationService.php
│     │   AuthenticationServiceInterface.php
│     │   SAMLAuthenticatorFactory.php
│     │   SAMLAuthenticatorFactoryInterface.php
│     │   UserService.php
│     │   UserServiceInterface.php
│   
│  composer.json
│  LICENSE.md
│  onelogin_saml.info.yml
│  onelogin_saml.install
│  onelogin_saml.links.menu.yml
│  onelogin_saml.module
│  onelogin_saml.permissions.yml
│  onelogin_saml.routing.yml
│  onelogin_saml.services.yml
│  README.md
```

### 5 - File/folder overview
#### 5.1 - Config
**onelogin_saml.settings.yml**  
The settings file defines default values for the admin form. Some of the fields require some input when the module is just installed or some fallbacks. That's where this settings file kicks in. 

#### 5.2 - Controller
**OneLoginSAMLController.php**  
This controller takes care of the actions defined by the routes in the routes file. 

#### 5.3 - Form
**OneLoginSAMLAdminForm.php**  
The form file defines an admin form that can be reached through the backend. In this form, you insert the URL's that OneLogin gives you and configure how the application should behave.

#### 5.4 - Src
**AuthenticationService.php**  
The Authenticationservice takes care of the processes after a correct login response from OneLogin. It syncs the roles and creates a new user if the one from the request is not in the system yet.

**AuthenticationServiceInterface.php**  
The interface for the Authentication Service.

**SAMLAuthenticatorFactory.php**  
This factory creates an instance of the third-party library class Auth. The library itself can be found [here](https://github.com/onelogin/php-saml). The instance uses a default set of settings, mainly coming from the admin form in the backend, but it is possible to provide your own settings. In that case, the default settings and the given settings are merged into one settings variable and used as a parameter when the OneLogin_Saml2_Auth is instantiated.

**SAMLAuthenticatorFactoryInterface.php**
The interface for the SAML Authenticator Factory

**UserService.php**  
If it turns out that a new user has to be created, then this service is called to take care of that.

**UserServiceInterface.php**  
The interface for the User service.

#### 5.5 - Project root
**composer.json**  
One of the important parts of the composer.json file is downloading the external library (OneLogin_Saml2) that is used for authenticating the user through the process.

**onelogin_saml.info.yml**  
With the info file, the module is visible on the Drupal Extend page.

**onelogin_saml.install**  
The install file defines task that should be checked and set when the module is installed for the first time.

**onelogin_saml.links.menu.yml**  
This file creates a menu item on the admin configuration page.

**onelogin.saml.module**  
The module file contains all hooks that are not yet converted to Drupal 8's services / classes. For example, the hook_alter and hook_help hooks.

**onelogin_saml.permissions.yml**  
The permissions file defines permissions for configuring the settings.

**onelogin_saml.routing.yml**  
The routing file defines the routes and corresponding actions the system uses.

**onelogin_saml.services.yml**  
The services file defines the services used by the application.

