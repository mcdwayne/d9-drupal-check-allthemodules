*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

                                AGE CALCULATOR

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

                            Author : Kunal Kursija

                Supporting organization: Blisstering Solutions

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

CONTENTS OF THIS FILE:
---------------------

- Introduction
- Requirements
- Installation
- Permissions
- User Interface
- Un-installation
- Maintainers

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

INTRODUCTION:
------------

Age calculator - Provides a very simple Ajax-Based user interface (Block) from
where users can calculate their age.

Only user input is going to be their birth date and the date on which they want
their date to be calculated on.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

REQUIREMENTS:
------------

As for now there are no requirements other than Drupal - Core.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

INSTALLATION:
------------

To install this module
1) Go to extend
2) Find 'Age Calculator'
3) Check the box on left and save.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

PERMISSIONS:
-----------

This module defines a permission named 'Congifure age calculator output'.
Users with this permission can control output format of age-calculator block.
To alter the permissions go to 'admin/people/permissions'.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

USER INTERFACE:
--------------

1) Block for users:

After the installation is complete, A block with a very simple UI is created in
hidden mode.
This block can be enabled in any desired region of your theme.
To enable the block go to admin/structure/block.
Place the 'Age Calculator' block in any desired region and configure it
accordingly.

User Input will be
- Date of birth
- Age on date (This can be any date you want to calculate your age on.)

2) Output age format:

Site-Administrators can control how the output format of the age will be.
This can be configured from admin/config/people/age-calculator/settings.
Below listed are the currently supported age formats.
- Y years M months D days
- M months D days
- W Weeks D days
- D days
- H hours (Approximate)
- M minutes (Approximate)
- S seconds (Approximate)

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

UN-INSTALLATION:
---------------

To un-install the module,
1) Go to admin/modules/uninstall
2) Find age calculator
3) Check the checkbox and save.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

MAINTAINERS:
-----------

Current maintainers:
 * Kunal Kursija - https://www.drupal.org/user/2126548

This project has been sponsored by:
 * Blisstering Solutions: Drupal Services, Solutions and Products Company.
 * iKsula Services pvt. ltd.

*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*

                                Thank You !
