class php::imagick (
    $locale_fix = undef,
    $progress_monitor = undef,
) {

    if !defined(Package['php5-imagick']) { package { 'php5-imagick': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/imagick.ini':
        content => template('php/etc/php5/mods-available/imagick.ini.erb'),
        require => Package['php5-imagick'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::imagick::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL imagick',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-imagick.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/imagick.ini'],
        notify   => Exec['php::restart'],
    }

}
