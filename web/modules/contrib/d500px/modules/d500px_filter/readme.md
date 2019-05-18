# 500px Integration Filter 
This module provides configurable filter to display 500px images. 
Allow users to embed a picture from 500px website in an editable content area.

## Filter Syntax
- **photoid**
ID of the photo the user wishes to embed. 
photoID is often part of the image URL, example:
```
https://500px.com/photo/98889053/
```

- **imagesize**
Values for imagesize is optional, if left off the default values configured 
on the input filter settings will be used.

### Filter Usage
```
[d500px photoid=<photo_id> imagesize=<imagesize>]
```
```
 [500px photoid=12345 imagesize=200]
```

## Image Sizes Available from 500px
### Standard Sizes

These are the standard cropped sizes:
<table id="image_sizes">
  <tr>
    <th>ID</th>
    <th>Dimensions</th>
  </tr>
  <tr><td>1</td><td>70px x 70px</td></tr>
  <tr><td>2</td><td>140px x 140px</td></tr>
  <tr><td>3</td><td>280px x 280px</td></tr>
  <tr><td>100</td><td>100px x 100px</td></tr>
  <tr><td>200</td><td>200px x 200px</td></tr>
  <tr><td>440</td><td>440px x 440px</td></tr>
  <tr><td>600</td><td>600px x 600px</td></tr>
</table>

These are the standard uncropped sizes:
<table id="image_sizes">
  <tr>
    <th>ID</th>
    <th>Dimensions</th>
  </tr>
  <tr><td>4</td><td>900px on the longest edge</td></tr>
  <tr><td>5</td><td>1170px on the longest edge</td></tr>
  <tr><td>6</td><td>1080px high</td></tr>
  <tr><td>20</td><td>300px high</td></tr>
  <tr><td>21</td><td>600px high</td></tr>
  <tr><td>30</td><td>256px on the longest edge</td></tr>
  <tr><td>31</td><td>450px high</td></tr>
  <tr><td>1080</td><td>1080px on the longest edge</td></tr>
  <tr><td>1600</td><td>1600px on the longest edge</td></tr>
  <tr><td>2048</td><td>2048px on the longest edge</td></tr>
</table>
