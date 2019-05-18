
# Jumper
Jumper provides a simple integration with Jump.js, a small, modern,
dependency-free smooth scrolling library.

## REQUIREMENTS
- Core block.module.
- [Blazy](http://dgo.to/blazy),
  No Blazy lazy load library is needed/ loaded. However Blazy is needed to
  reduce dups for jQuery replacement methods which is provided by Blazy
  separately.
- Jump library:
  + Download Jump from https://github.com/callmecavs/jump.js
  + Extract, rename **jump.js-master** to **jump**, so the assets are at:

    **/libraries/jump/dist/jump.min.js**


## OPTIONAL REQUIREMENTS
- If you need to support old browsers (IE9), include `requestAnimationFrame`
  polyfill yourself into your own custom libraries from any below:

  + https://www.paulirish.com/2011/requestanimationframe-for-smart-animating/
  + https://gist.github.com/paulirish/1579671

  Note: IE10+ supports rAF, no need to include it.


## INSTALLATION
Install the module as usual:

  Visit _/admin/modules_, and enable the Jumper module.

More info can be found on:
[Installing Drupal 8 Modules](https://drupal.org/node/1897420)


## USAGE
1. Visit _/admin/structure/block_
   Place the provided Jumper block into footer, or any relevant region.

2. Adjust a few settings accordingly, including custom jump links.


## CUSTOM USAGE
1. In you Drupal body text, hit `Source` if using CKEditor, add a class `jumper`
   to any link / button element containing attributes `[data-target]`, or
   `[href]`, with the relevant target to scroll to, e.g.:

   ````
   <a href="#main" class="jumper">Jump</a>

   <a href="#map" class="jumper">Visit our office!</a>
   ````
     or

   ````
   <a href="#" data-target="#main" class="jumper">Jump</a>
   ````
     or

   ````
   <button data-target="#main" class="jumper">Jump</button>
   ````

2. Include the provided block via block admin, or load the `jumper/load` library
   as needed accordingly. No need to manually load, if the block is there.

3. To position the Jumper block anywhere, use a simple CSS override, e.g.:

   Position the Jumper block at the bottom left corner.
   ````
   .jumper.jumper--block {
     left: 15px;
   }
   ````

   Or center it at the bottom of page.
   ````
   .jumper.jumper--block {
     left: 50%;
     right: auto;
     -ms-transform: translateX(-50%) translateY(100%);
     transform: translateX(-50%) translateY(100%);
   }
   ````

   ````
   .is-jumper-visible .jumper.jumper--block {
     -ms-transform: translateX(-50%) translateY(0);
     transform: translateX(-50%) translateY(0);
   }
   ````

   By default, Jumper block is positioned at the bottom right.


## STYLINGS
The styling is very basic. Use your own icons and CSS to override defaults.
Specific to the provided block, it has additional class `jumper--block` with
particular stylings for a single jumper block.


## FEATURES
* Vanilla JS, no jQuery, nor jQuery UI, required.
* Uses requestAnimationFrame, natively performant.
* A`Jump to Top` or `Jump Anywhere` block which can be placed via block admin.
* Jump anywhere for extra configurable links, as seen at a single page website.
* A few basic sensible stylings. Use your own CSS to place the block anywhere.

## WHY SHOULD I USE IT?
I can put the markups and embed it in my theme, why should I use a module?
Depending on your design challenges. A few reasons:

1. You can place a block anywhere, or exclude it at ease via UI. You don't want
   a short page like _Contact us_ to have it, exclude it via UI just as easily.
2. You can put it at a particular section of the page, not only left, or right.
3. You are beginning to love Drupal way, not WordPress one.
4. Or you are beginning to be sick of being told:

   **Just put a library in your theme.info, add a line, and you are done!**


## RELATED MODULES

http://drupal.org/project/back_to_top

**Differences:**

Jumper doesn't use jQuery, nor jQuery UI. It uses native
`requestAnimationFrame`, core block for placement, and can be used to jump
anywhere with configurable links for smooth scrolling.
Persuading existing module to depend on Blazy is more likely out of question
than creating a new one in the same direction as Blazy.

http://drupal.org/project/lory

Lory module may need jumper.module for its optional feature:
arrowDown.


## KNOWN ISSUES
The `jumper` namespace is because `jump` is already reserved by `jump` menu
module for different purposes. However `jumper` is still based on original
jump.js intended singleton. Why not `jumpjs`, or `jumpingjackflash`? Maybe,
but the author said:
"... naming it according to your preference".


## AUTHOR/MAINTAINER/CREDITS
* [Gaus Surahman](https://drupal.org/user/159062)
* https://www.drupal.org/node/2847807/committers
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.
* [Michael Cavalea](https://github.com/callmecavs/jump.js)
