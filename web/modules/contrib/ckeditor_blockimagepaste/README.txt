A CKEditor plugin which prevents users to paste an image into the editor.

Why?
Because If you copy/paste an image directly into CKEditor, it converts it
into base64 and the code is saved to the database, like so:
<img alt="" src="data:image/png;base64, lots and lots of characters />.

This can cause various problems and generally is not desirable.

Alternative approach:
You could also block img all together by removing them from allowed tags in
the formats config. But then you might still have an issue with a "full html"
format which also is able to use the editor.

Install:
Enable module and enable the plugin in each desirable format.

Related issues:
#2824087: Copy/pasting images into CKEditor results in data URIs being embedded
