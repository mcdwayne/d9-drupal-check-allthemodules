***
***

## TROUBLESHOOTING

* Be sure the amount of Views results are matching the amount of the grid boxes
   **minus** stamp. The same rule applies to either GridStack or custom-defined
   grids. This is easily fixed via Views UI under **Pager**.
* Follow the natural order keyed by index if trouble with multiple breakpoint
  image styles.
* Clear cache when updating cached Gridstack otherwise no changes are visible
  immediately. You can also disable the Gridstack cache during work.
* Check **Use CSS background** if images are not filling out the grids,
  otherwise you may have to define a unique image style per grid via the
  provided UI.


Below are valid for v0.2.5 below, for the sake of documentation:

* At admin UI, some grid/box may be accidentally hidden at smaller width. If
  that happens, try giving column and width to large value first to bring
  them back into the viewport. And when they are composed, re-adjust them.
  Or hit Clear, Load Grid, Save & Continue buttons to do the reset.
  At any rate saving the form as is with the mess can have them in place till
  further refinement.
* Use the resizable handlers to snap to grid if box dragging doesn't snap.
  Be gentle with it to avoid abrupt mess.
* If trouble at frontend till Gridstack library is decoupled from jQuery UI or
  at least till jQuery related issues resolved, for its static grid, check the
  option **Load jQuery UI**. If you are a JS guy, you know where the problem is.
  A temporary hacky solution is also available:
  /admin/structure/gridstack/ui


## KNOWN ISSUES
* Having the exact amount of items to match the optionset grids is a must.
* If you have more items than designated optionset grids, the surplus will not
  be displayed as otherwise broken grid anyway.
* If you have less items, the GridStack will not auto-fix it for you.
  Simply providing the exact amount of items should make it gapless grid again.
* Not compatible with Responsive image OOTB. GridStack has its own managed
  multi-styled images per breakpoint.
* Nested grids are only supported by static grid Bootstrap/ Foundation, not JS.
* Grids may have weird aspect ratio with margins.
   **Solutions**:

   * `Vertical margin` = 0
   * `No horizontal margin` enabled
   * Adjust `Cell height`
