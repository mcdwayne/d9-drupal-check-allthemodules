Pseudo Title
==========
Pseudo Title creates a pseudo field that can be used to move up and down the content of the title in the same
administration page where the display of other fields are managed.

The problem that this module addresses is that Drupal 8 doesn't show the Title field in the 
/admin/structure/types/manage/[CONTENT_TYPE]/display page. Therefore; if your page layout requires that the title of a
node in full-view be placed after the output of other fields, then you have to do it by other means.

Installation and Usage
======================
It is important to understand that after installing this module, node pages will have two ways of showing the title:
1- The default output of the title configured on the blocks page (/admin/structure/block).
2- The new output of the title produced by this module configured on the field display page of each content type.

If you want to use the normal output of the title on a content type, then go to the
/admin/structure/types/manage/[CONTENT_TYPE]/display page and disable the Pseudo Title.

If you want to use the output of this module to show the title of all nodes in full-view, then remove the output of the
block title in the block administration page.

You can also mix and match, making some content types use the output of this module and others use the default output,
by managing accordingly the settings of the title block and the settings of the pseudo title on the fields display page.

Theming
=======
If you need to change the HTML produced by this module, make a copy of the pseudo-title.html.twig file and add it to
your theme. Edit this file to fit your needs and finally rebuild the Drupal cache to see the result.

Author/Maintainers
======================
- Juan Martinez <jcmartinez at makers365 DOT com> http://www.makers365.com

