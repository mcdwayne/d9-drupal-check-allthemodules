/**
 * This is a generic concrete class for loading components.
 * - Manually load in components by providing an array of component elements.
 * - Autoload components on page load.
 * - Load components on demand when a compnent element is scolled, resized or
 *   orientationchanged into view.
 */

// Lib imports
import {
    enableProdMode,
    Injector,
    ApplicationRef,
    NgModuleRef,
    NgZone,
    NgModuleFactory,
    ReflectiveInjector,
    ApplicationInitStatus,
    SystemJsNgModuleLoader
} from '@angular/core';
import {Observable} from 'rxjs/Observable';
import 'rxjs/add/observable/fromEvent';
import 'rxjs/add/operator/debounceTime';
// External imports
import {LazyLoadComponent} from 'helpers/lazy-load-component';

if (drupalSettings.pdb.ng2.development_mode === 0) {
  enableProdMode();
}

export class ScrollLoader {
  public componentIds: string[];

  constructor(public appModule: NgModuleRef<any>, public components: any) {
    this.componentIds = Object.keys(components);
    this.subscribe();
  }

  /**
   * Initialize any components in view on page load.
   */
  public initialize(): void {
    // Get the components wrapper selector to search for components.
    // Defaults to body.
    // let componentsWrapper = drupalSettings.pdb.ng2.components_wrapper;
    let componentsWrapper = 'body';

    let content = document.querySelector(componentsWrapper);

    if (content) {
      this.checkElements(this.componentIds);
    }
  }

  /**
   * Subscribe to window scroll, resize and orientationchange events, binding
   * a checkElements call to check if components are now in view.
   */
  public subscribe(): void {
    Observable.fromEvent(window, 'scroll')
        .debounceTime(200)
        .subscribe(x => {
          this.checkElements(this.componentIds);
        });

    Observable.fromEvent(window, 'resize')
        .debounceTime(200)
        .subscribe(x => {
          this.checkElements(this.componentIds);
        });

    Observable.fromEvent(window, 'orientationchange')
        .subscribe(x => {
          this.checkElements(this.componentIds);
        });
  }

  /**
   * Unsubscribe elements which have already been bootstrapped by deleting from
   * elements array.
   *
   * @param {string} id of element to be unsubscribed
   */
  public unsubscribe(id: string): void {
    if (this.componentIds.length) {
      let index = this.componentIds.indexOf(id);

      if (index > -1) {
        // immutable splice
        this.componentIds = [
          ...this.componentIds.slice(0, index),
          ...this.componentIds.slice(index + 1)
        ];
      }
    }
  }

  /**
   * Helper function to check if any of the elements are in view. If an element
   * is in view, its corresponding component is loaded and initialized
   *
   * @param {Array.<string>} ids of component elements
   */
  public checkElements(ids: string[]): void {
    ids.forEach((id, index) => {
      let el = document.getElementById(id);

      if (el && this.elementInViewport(el)) {
        // assuming if innerHTML is empty module has not been loaded
        if (el.innerHTML.length === 0) {
          // Define ngClassName based on component settings or build default
          // ngClassName based on element value.
          let ngClassName = (typeof this.components[id]["ngClassName"] === 'string') ?
              this.components[id]["ngClassName"] : this.convertToNgClassName(this.components[id]["element"]);
          let selector = '#' + id;
          this.bootstrapComponent(id, ngClassName, selector);
        }
      }
    });
  }

  /**
   * Bootstraps individual component into the DOM using System.js.
   *
   * @param {string} id - component element id
   * @param {string} ngClassName - Component Class Name
   * @param {string} selector - selctor of DOM element to bootstrap into
   */
  public bootstrapComponent(id: string, ngClassName: string, selector: string): void {
    let componentFile = Drupal.url(drupalSettings.pdb.ng2.components[id]["uri"]) + '/index';

    // load and compile the module lazy loaded
    const ngModuleLoader = this.appModule.injector.get(SystemJsNgModuleLoader);

    return ngModuleLoader.loadAndCompile(`${componentFile}#${ngClassName}`)
        .then(moduleFactory => {
          // use dynamic bootstrap function
          this.bootstrapWithCustomSelector(moduleFactory, selector, ngClassName);
        })
        .then(() => {
          // successfully bootstrapped, stop checking if in viewport
          this.unsubscribe(id);
        });
  }

  /**
   * Dynamically bootstrap a component into a selector.
   * This is... no longer a hack !o!
   *
   * @param {NgModuleFactory<any>} moduleFactory  Module to bootstrap
   * @param {string}               selector       The DOM element to bootstrap into
   */
  public bootstrapWithCustomSelector(moduleFactory: NgModuleFactory<any>, selector: string, ngClassName: string): void {
    const ngZone = this.appModule.injector.get(NgZone);

    ngZone.run(() => {
      // Get the parent injector and create the module
      const parentInjector = ReflectiveInjector.resolveAndCreate([], this.appModule.injector);
      const ngModule = moduleFactory.create(parentInjector);

      // Some dependencies from this module
      const appRef = ngModule.injector.get(ApplicationRef);
      const inj = ngModule.injector.get(Injector);
      const initStatus = ngModule.injector.get(ApplicationInitStatus);
      const lazyLoad = ngModule.injector.get(LazyLoadComponent);

      if (!lazyLoad) {
        throw(`${ngClassName} is not using the LazyLoadComponent. This is
          necessary to bootstrap the component. Check the docs.`);
      }

      initStatus.donePromise.then(() => {
        // Get the component factory and create it
        const compFactory = ngModule.componentFactoryResolver
            .resolveComponentFactory(lazyLoad);
        const compRef = compFactory.create(inj, [], selector);

        // Register the change detector to the app and trigger the first detection
        compRef.changeDetectorRef.detectChanges();
        appRef.registerChangeDetector(compRef.changeDetectorRef);
      });
    });
  }

  /**
   * Helper function to convert component name to Angular 2 ClassName.
   *
   * @param {string} - elementName in form "wu-favorites"
   * @returns {string} - ng2 class name in form "WuFavorites"
   */
  public convertToNgClassName(elementName: string): string {
    return (elementName.toLowerCase().charAt(0).toUpperCase() +
        elementName.slice(1))
            .replace(/-(.)/g, (match, group1) => group1.toUpperCase()) +
        'Module';
  }

  /**
   * Checks to see if an element is in view.
   *
   * @param {object} el - element to check
   * @returns {boolean} - in viewport
   *
   * @see based on SO answer: http://stackoverflow.com/a/23234031/1774183
   * Fixes firefox issue and also loads if any part of element is in
   * view. Returns elements not notInView (in viewport).
   */
  public elementInViewport(el: HTMLElement): boolean {
    let rect;

    if (this.supportsBoundingClientRect()) {
      rect = el.getBoundingClientRect();
    } else {
      rect = this.androidChromeBoundingClientRect(el);
    }

    return !(
        rect.bottom < 0 ||
        rect.right < 0 ||
        rect.left > (window.innerWidth || document.documentElement.clientWidth) ||
        rect.top > (window.innerHeight || document.documentElement.clientHeight)
    );
  }

  /**
   * Gets the offset of the element based on the viewport
   * This is a fallback for Chrome Android
   *
   * @param {object} el - element to check
   * @returns {object} - the 4 points offset
   */
  public androidChromeBoundingClientRect(el: HTMLElement): {
    top: number, bottom: number, right: number, left: number
  } {
    const top = el.offsetTop - window.scrollY;
    const bottom = top + el.clientHeight;
    const left = el.offsetLeft - window.scrollX;
    const right = left + el.clientWidth;

    return {top, bottom, right, left};
  }

  /**
   * Checks if a browser is Chrome Android
   *
   * @returns {boolean} - If browser is chrome mobile or not
   */
  public supportsBoundingClientRect(): boolean {
    return !/android.*chrome\/[.0-9]+/i.test(window.navigator.userAgent);
  }
}
