define drush::instance (
    $version = $title,
    $installdir = '/usr/local/share',
) {

    if !defined(Package['php5-cli']) { package { 'php5-cli': } }
    if !defined(Package['php5-dev']) { package { 'php5-dev': } }
    if !defined(Package['php-console-table']) { package { 'php-console-table': } }
    if !defined(Package['wget']) { package { 'wget': } }

    if !defined(Exec["drush::install::${version}"]) {
        exec { "drush::install::${version}":
            command => "wget -q -O - https://github.com/drush-ops/drush/archive/${version}.tar.gz | tar -C ${installdir} -zxf -",
            creates => "${installdir}/drush-${version}",
            require => Package['wget'],
        }
    }

}
