/* code modified from ColdFusion's cfdump code */
function dBug_toggleRow(source) {
  'use strict';

  var $target = (document.all) ? source.parentElement.cells[1] : source.parentNode.lastChild;
  dBug_toggleTarget($target, dBug_toggleSource(source));
}
function dBug_toggleSource(source) {
  'use strict';

  if (source.style.fontStyle === 'italic') {
    source.style.fontStyle = 'normal';
    source.title = 'click to collapse';
    return 'open';
  } else {
    source.style.fontStyle = 'italic';
    source.title = 'click to expand';
    return 'closed';
  }
}
function dBug_toggleTarget(target, switchToState) {
  'use strict';

  target.style.display = (switchToState === 'open') ? '' : 'none';
}
function dBug_toggleTable(source) {
  'use strict';

  var $i;
  var $target;
  var $switchToState = dBug_toggleSource(source);
  if (document.all) {
    var $table = source.parentElement.parentElement;
    for ($i = 1; $i < $table.rows.length; $i++) {
      $target = $table.rows[$i];
      dBug_toggleTarget($target, $switchToState);
    }
  }
  else {
    var table = source.parentNode.parentNode;
    for ($i = 1; $i < table.childNodes.length; $i++) {
      $target = table.childNodes[$i];
      if ($target.style) {
        dBug_toggleTarget($target, $switchToState);
      }
    }
  }
}
