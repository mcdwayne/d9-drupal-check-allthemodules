Installation instructions for vcard module:

1) Install Contact_Vcard_Build from PEAR:
   Install File_IMC from PEAR:

       $ pear install File_IMC

   - OR -

       $ pear install Contact_Vcard_Build

   or download it and extract it into your include_path configured in your
   php.ini.

2) Enable vcard module on administer->modules

3) Configure the profile field mappings on Administer->Site configuration->vCard
   (?q=admin/config/people/vcard)

4) Configure the permissions on Administer->People->Permissions
   (?q=admin/people/permissions)

5) Go to Configuration->People->Vcard and check "Show vCard download link on
   user's profile" and "Show hCard on user's profile" to show vCard and hCard on
   your user profile page.
   Create User fields that you want and map them with your VCard fields listed
   in the drop down to show data of those fields in your vCard.

6) Go to Configuration->People->Account Settings and click on "Manage
   Display" tab. You can drag and drop the fields, the way you want to show them
   on the user profile page.
