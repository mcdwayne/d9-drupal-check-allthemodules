class php::mcrypt (
    $algorithms_dir = undef,
    $modes_dir = undef,
) {

    if !defined(Package['php5-mcrypt']) { package { 'php5-mcrypt': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/mcrypt.ini':
        content => template('php/etc/php5/mods-available/mcrypt.ini.erb'),
        require => Package['php5-mcrypt'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::mcrypt::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL mcrypt',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-mcrypt.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/mcrypt.ini'],
        notify   => Exec['php::restart'],
    }

}
