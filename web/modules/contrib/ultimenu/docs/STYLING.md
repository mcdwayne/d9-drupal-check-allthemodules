***
***

# STYLING
Please ignore any documentation if you are already an expert. This is for the
sake of documentation completion for those who might need it. If it is not you.

## Undestanding off-canvas:
The off-canvas requires you to understand the HTML structure of your page. You
may want to edit your **page.html.twig** later manually.
Press F12 at any browser, inspect element. The following is a simplified Bartik:

````
<body class="is-ultimenu-canvas">
  <div id="page-wrapper">
    <div id="page">
      <!-- This element, out of canvas by default, can only exist once. -->
      <header id="header" class="is-ultimenu-canvas-off"></header>

      <!-- These elements will be pushed out of canvas once the above is in. -->
      <div class="highlighted is-ultimenu-canvas-on"></div>
      <div class="featured-top is-ultimenu-canvas-on"></div>
      <div id="main-wrapper" class="is-ultimenu-canvas-on"></div>
      <div class="site-footer is-ultimenu-canvas-on"></div>
    </div>
  </div>
</body>

````

## Alternative layout:
````
<body class="is-ultimenu-canvas">
  <div class="l-page">
    <!-- This element, out of canvas by default, can only exist once. -->
    <header class="l-header is-ultimenu-canvas-off"></header>

    <!-- Only one element at the same level here, put it into the container. -->
    <div class="l-main is-ultimenu-canvas-on">
      <div class="l-highlighted"></div>
      <div class="l-featured-top"></div>
      <div class="l-content"></div>
      <div class="l-footer"></div>
    </div>
  </div>
</body>

````

## Another alternative layout:
````
<body class="is-ultimenu-canvas">

  <!-- The page container is placed below header, yet the same rule applies. -->

  <!-- This element, out of canvas by default, can only exist once. -->
  <header class="l-header is-ultimenu-canvas-off"></header>

  <!-- Only one element at the same level here, put it into the container. -->
  <div class="l-page is-ultimenu-canvas-on">
    <div class="l-highlighted"></div>
    <div class="l-featured-top"></div>
    <div class="l-content"></div>
    <div class="l-footer"></div>
  </div>
</body>

````

Note the 2 CSS classes **is-ultimenu-BLAH**, except body, is added by JavaScript
based on Steps #4 (**CONFIGURING OFF-CANVAS MENU**), if using the provided
default values. However you might notice a slight FOUC (flash of unstyled
contents). It is because JS is hit later after CSS. To avoid such FOUC, you just
have to hard-code those 2 classes directly into your own working theme, either
via `template_preprocess`, or TWIG hacks.

The JavaScript will just ignore or follow later, no problem.

1. Body: **is-ultimenu-canvas**. This has been provided by this module.
2. Header: **is-ultimenu-canvas-off**

   You can also put this class into any region inside Header depending on your
   design needs. Just not as good as when placed in top-level **#header** alike.
   This element, out of canvas by default, can only exist once.
3. Any element below header at the same level: **is-ultimenu-canvas-on**.

   These elements, on canvas by default, will be pushed out of canvas once
   the off-canvas element is in.

## DOS and DONTS
1. Don't add those 2 later classes to `.is-ultimenu-canvas-off` parent elements.
   Otherwise breaking the fixed element positioning. They must all be on the
   same level.
2. Do try it with core Bartik with default values. Once you know it is working,
   apply it to your own theme.
3. Do sync your hard-coded classes with the provided configurations. Once
   all looks good and working as expected, you can even leave these two options
   empty. They are just to help you speed debugging your off-canvas menu via UI.
4. Do override the relevant `ultimenu.css`, and `ultimenu.offcanvas.css` files.
5. Do keep those classes, unless you know what you are doing.


## Iconized title
Ultimenu supports a simple iconized title in a pipe delimiter title.
The title must start with either generic **icon-** or fontawesome **fa-** and
separated by a pipe (|):

* icon-ICON_NAME|Title
* fa-ICON_NAME|Title

**For example:**

* icon-home|Home
* icon-mail|Contact us

Adjust the icon styling accordingly, like everything else.

**Repeat!**

Do not always change your Menu item title, else its region will be gone.
Unless using the recommended HASHed region names.

Feel free to change the icon name any time, as it doesn't affect the region key.


## CSS Classes:
The following is a simplified Ultiemnu block container.
````
<div class="block block-ultimenu">
  <ul class="ultimenu ultimenu--main">

    <li class="ultimenu__item has-ultimenu">

      <a class="ultimenu__link">Home</a>

      <section class="ultimenu__flyout">
        <div class="ultimenu__region region">Region (blocks + submenu)</div>
      </section>

    </li>

  </ul>
</div>
````

* **BODY.is-ultimenu-canvas--hover**: if off-canvas is enabled for mobile only,
  indicating the main menu **has** hoverable states.
* **BODY.is-ultimenu-canvas--active**: if off-canvas is enabled for both mobile
  and desktop, indicating the main menu **has no** hoverable states, defined
  via `ultimenu_preprocess_html()`.
  Otherwise this class is only available for mobile only, defined by JS.
* **UL.ultimenu**: the menu UL tag.
* **UL.ultimenu--hover**: the menu UL tag can have hoverable states.
* **UL.ultimenu > li**: the menu LI tag.
* **UL.ultimenu > LI.has-ultimenu**: contains the flyout, to differ from regular
  list like when using border-radius, etc.
* **SECTION.ultimenu__flyout**: the ultimenu dropdown aka flyout.
* **DIV.ultimenu__region**: the ultimenu region inside the flyout container.
* **A.ultimenu__link**: the menu-item A tag.
* **SPAN.ultimenu__icon**: the menu-item icon tag.  
* **SPAN.ultimenu__title**: the menu-item title tag, only output if having icon.
* **A > SMALL**: the menu-item description tag, if enabled.

A very basic layout is provided to display them properly. Skinning is all yours.
To position the flyout may depend on design:

* Use relative `UL` to have a very wide flyout that will stick to menu `UL`.
  This is the default behavior.
* Use relative `LI` to have a smaller flyout that will stick to a menu `LI`:

  `ul.ultimenu > li { position: relative; }`

  If you do this, you may want to add a regular CSS rule `min-width: 600px;`
  (for example) to prevent it from shrinking to its parent `LI` width. Each `LI`
  item has relevant CSS classes, adjust width for each item as needed.
  See `ultimenus.extras.css` for sample about this.

To center the flyout, use negative margin technique:

```
  .ultimenu__flyout {
    left: 50%;
    margin-left: -480px; /* half of width */
    width: 960px;
  }
```

Or with a more modern technique, add prefixes for old browsers:

```
  .ultimenu__flyout {
    left: 50%;
    transform: translateX(-50%); /* half of width */
  }
```

Adjust the margin and width accordingly. The rule: margin is half of width.


## More ideas for positioning:

- Centered to menu bar, like ESPN
- Always left to menu bar
- Always right to menu bar
- Centered to menu item
- Left to menu item, like Reuters
- Right to menu item

When placing vertical Ultimenu in sidebar, make sure to add position relative
to the sidebar selector, and add proper **z-index**, otherwise it is possible
that the flyout will be dropped behind some content area. Covered by the
optional **ultimenu.extras.css** for now.
