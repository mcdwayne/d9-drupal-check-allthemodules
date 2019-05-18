README - Corporate Login
-----------------------------

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration

INTRODUCTION
------------

Corporate Login Module helps you to reduce the number of registration done from
a same domain name. Once a user login with their corporate email, the domain
will be saved and no further sign up by same domain users required, instead they
can login through Corporate sign up.
 
For example Let us assume one website consists of Survey / Quiz which has some
prizes and voucher to be won. But only a logged in user can take it up. In that
scenario if that web application has corporate login module installed then one
person from organization have to get the admin access and rest all the employees
of the organization can easily login through corporate login.

INSTALLTION
-----------

1) Copy corporatelogin directory to your modules directory.

2) Enable the module at module configuration page.

CONFIGURATION
-------------

How to Use Corporate Login?

 - Once the module installed a new role called "corporate" will be created
 automatically.

 - Create a new user and assign a role as corporate, or assign a role as
 corporate to any existing user.

 - Once the user created / updated , it will shown in below configuration page 

 - Configuration URL
    admin/config/user-interface/corporate-account.  

 - Select Corporate Domain Name, which you want to apply and
 save the configuration.

 - Corporate User Login will appear in the user/login page automatically for
 Anonymous user in Main Content region.