Password Strength
======================

This is a Drupal 8 module that adds a password strength plugin to the Password Policy module. Implemented from 
https://github.com/bjeavons/zxcvbn-php

The purpose of this module is to provide a score for the strength of a password, 0 being very weak, 4 being very strong.
 This score is based on the concept of "matchers". A matcher is an algorithm that evaluates a specific characteristic of
 a password. More details can be found at https://github.com/bjeavons/zxcvbn-php

The great part of this tool is that you can create a constraint for a password based on this scoring system. This 
score abstracts all of the complex logic and password strength evaluation for a very simple and easy-to-use way to 
evaluate the strength of a password. This is in contrast to stricter regulations which need explicitly configured 
policies for passwords. This is a generic approach which promotes usability and still advocates for strong passwords.

**Installing**

-  Ensure that Composer Manager is enabled and properly installed (https://www.drupal.org/node/2405811)
-  Download and enable the module
-  Create or update a password policy (admin/config/security/password-policy)
-  On the second step under "Add Constraint", select "Password Strength" and click "Configure Constraint Settings"
-  Select the minimum score for your policy (0 is very weak, 4 is very strong) and click "Save"
-  Finish the steps for the policy to save

**Configure**

-  Go to Password Policy Zxcvbn's configuration page (admin/config/security/password_strength/settings)
-  Turn on or off matcher algorithms based on your site needs


**Architecture**

-  Password strength is based on the Zxcvbn library, is pulled in via Composer Manager, and loads the library from 
    https://github.com/bjeavons/zxcvbn-php
-  The module ships with a plugin system around matchers to allow for swappable matching algorithms to influence the
    score for customized needs
