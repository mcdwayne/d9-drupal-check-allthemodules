MailMe is a module built for Drupal 8. Once enabled within the theme it will allow for the placement of a simple block with a field for a 'Name', 'Email', 'Subject', and Message'. Once these fields are filled out and submitted it will send an email to the user who installed the module or a user can enter in an email in the administration panel.

In my application of the block it is hidden until a button is clicked. It will then appear over the content of the page. That explains the size and structure of the block. Anyone is free to modify this module completely.

To Install:

Place the module w/ files into the modules folder.
Enable the module.
Place the MailMe block anywhere you would like.
Test!
Troubleshooting: One issue I struggled with was getting the form to send mail without any additional modules. While it does work without installing anything else, you might need to install the SendMail package on your server (sudo apt-get install sendmail).

Once that is installed it might be necessary to go into your hosts file (/etc/hosts) and add the following line to the top without the quotes: "127.0.0.1 localhost.localdomain localhost YOUR SERVER'S HOSTNAME"