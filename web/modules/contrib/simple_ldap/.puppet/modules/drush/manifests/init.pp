class drush (
    $version = '6.4.0',
    $installdir = '/usr/local/share',
) {

    drush::instance { "drush::${version}":
        version    => $version,
        installdir => $installdir,
    }

    file { "${installdir}/drush":
        ensure  => "${installdir}/drush-${version}",
        require => Drush::Instance["drush::${version}"],
    }

    file { '/usr/local/bin/drush':
        ensure  => "${installdir}/drush/drush",
        require => File["${installdir}/drush"],
    }

}
