SUMMARY - Bulk user registration
=================================
To import content for User register or import from CSV file

REQUIREMENTS
------------

This module requires the following:
A CSV file for content type to import.
The .csv file can have two or more columns,
eg:- username, email, status,role
The first row should be machine name for the user information
and the following rows will be taken as data.
Refer the example given in the module folder CSV_article.csv file

INSTALLATION
-------------
Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8

CONFIGURATION
-------------

After successfully installing the module bulk user import or registration,
you can import user data for the selected role type via
file import.

Install the module bulk_user_registration.

Go to Configuration and select User import from
Content Authoring.
 
It will redirect you to User Import Form, with three
fields: User role, Override role and Import file.

Select the Role, this will have all the user required information
available in the application

Before choosing csv file, check weather the first row contains 
all the machine names from the user info like username,email,status and role.

The file should be CSV file.

Please give write permission for your sites/default/files/ folder 
to write the log file.
{{Under development log section.}}

Mandatory Columns is CSV:
=========================

username - username of the user
email - email of the user

role - it will work based on your selection for overriding role.

Field Mapping:
=============
username -> username
email -> email
status -> make user active
role -> auto assign user role

Check the attached CSV file for Sample.

Click on Import which redirects you to admin/content

