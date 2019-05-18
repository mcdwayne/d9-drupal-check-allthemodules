((Drupal, drupalTranslations, wp) => {
  // Not really using translations? Get out.
  if (!drupalTranslations) {
    return false;
  }

  const translations = {
    '': {
      lang: 'pt',
      plural_forms: 'nplurals=2; plural=(n != 1)',
    },
  };

  Object.entries(drupalTranslations.strings['']).map(t => {
    translations[t[0]] = t[1];
  });

  wp.i18n.setLocaleData(translations);

  console.log(wp.i18n);
})(Drupal, drupalTranslations, wp);
