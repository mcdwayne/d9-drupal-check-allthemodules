Block upload
============
Block upload - it's a simple module, which allow users to upload images/files 
into field without node edit page. You can add this block on node pages. 
Choose field you want to upload files and set permission. 
So, for example, users can add new photos into node without full node 
edit rights. You also can quickly add new files into node, avoid edit form.

Usage
=====
Install module and go to Blocks management page, than find Block upload. 
On edit page choose file/image field you wish to use and save settings. 
Choose block position and here it is. Additionally set user permissions to 
allow uploads.

Integration
===========
Integration with Plupload integration module what makes available to use 
multiupload and drag&drop features. 
To enable it, install Plupload integration according to it requirements. 
Than go to block upload config and tip use plupload option. This will switch 
single form to plupload widget.

Multiple block feature.
=======================
On settings page /admin/config/content/block_upload you can set number of 
blocks. Run update.php to move your current block field settings and you'll 
also need manually set visibility and other core block settings for the new 
"Block Upload 1".
