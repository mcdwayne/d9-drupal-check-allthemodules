(function (drupalSettings) {
tarteaucitron.user.ekomiCertId = drupalSettings.ekomi.value;
(tarteaucitron.job = tarteaucitron.job || []).push('ekomi');
})(drupalSettings);