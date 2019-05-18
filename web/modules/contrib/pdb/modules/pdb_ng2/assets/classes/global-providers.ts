/**
 * Load shared global services and inject as providers in main app bootstrap to
 * create one shared service instance between all components.
 */
export class GlobalProviders {
  constructor(private injectables: any) {}

  /**
   * @return {Promise<any>} array of promises returned from System.import.
   */
  public importGlobalInjectables(): Promise<any>[] {
    const importPromises: Promise<any>[] = [];

    for (let instanceId in this.injectables) {
      // Must use use absolute path in System.import.
      const component = this.injectables[instanceId];
      const componentName = this.convertToNgClassName(component.element);

      importPromises.push(System.import(`/${component.uri}/globals`)
          .then(component => ({component, componentName}))
          .catch(this.onMissingProvidersFileError.bind(this, componentName)));
    }

    return importPromises;
  }

  /**
   * @param  {any[]} globals array of imported module definitions.
   */
  public createGlobalProvidersArray(globals: any[]): any {
    let globalProviders: any[] = [];
    let globalImports: any[] = [];

    for (let mod of globals) {
      const globalsName = `${mod.componentName}Globals`;

      if (!(globalsName in mod.component)) {
        this.onMissingGlobalsExportError(mod.componentName);
      }

      if (!('providers' in mod.component[globalsName])) {
        this.onMissingProvidersExportError(mod.componentName);
      }

      if (!('imports' in mod.component[globalsName])) {
        this.onMissingModulesExportError(mod.componentName);
      }

      globalProviders = [...globalProviders, ...mod.component[globalsName].providers];
      globalImports = [...globalImports, ...mod.component[globalsName].imports];
    }

    const globalProvidersNames = this.symbolsToNames(globalProviders);
    const globalModulesNames = this.symbolsToNames(globalImports);

    // tslint:disable:no-console
    console.log(`Global available providers ${globalProvidersNames}`);
    // tslint:disable:no-console
    console.log(`Global available modules ${globalModulesNames}`);

    return {globalProviders, globalImports};
  }

  /**
   * Helper function to convert component name to Angular 2 ClassName.
   *
   * @param {string} - elementName in form "wu-favorites"
   * @returns {string} - ng2 class name in form "WuFavorites"
   */
  private convertToNgClassName(elementName: string): string {
    return (elementName.toLowerCase().charAt(0).toUpperCase() +
    elementName.slice(1))
        .replace(/-(.)/g, (match, group1) => group1.toUpperCase());
  }

  private symbolsToNames(symbols: any[]): string {
    return symbols
        .map(symb => symb.name)
        .filter((el, i, arr) => arr.lastIndexOf(el) === i)
        .join(', ');
  }

  private onMissingProvidersFileError(componentName: string): void {
    throw(`${componentName} does not have a global-providers file to load
      All lazy loaded components need a global-providers file. Check the docs`);
  }

  private onMissingProvidersExportError(componentName: string): void {
    throw(`${componentName} does not have a "providers" property array
      in the globals file. Check the docs`);
  }

  private onMissingModulesExportError(componentName: string): void {
    throw(`${componentName} does not have an "imports" property array
      in the globals file. Check the docs`);
  }

  private onMissingGlobalsExportError(componentName: string): void {
    throw(`${componentName} does not have a "${componentName}Globals" exported
      in the globals file. Check the docs`);
  }
}
