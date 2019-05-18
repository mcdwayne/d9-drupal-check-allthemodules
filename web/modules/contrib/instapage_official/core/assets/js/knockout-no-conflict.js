/* globals ko */
var instapageKO = ko;

instapageKO.observable.fn.setBusy = function setBusy(value) {
  this.busy = value;
}
instapageKO.observable.fn.isBusy = function isBusy() {
  return (typeof this.busy === 'undefined') ? false : this.busy;
}

window.instapageKO = instapageKO;
