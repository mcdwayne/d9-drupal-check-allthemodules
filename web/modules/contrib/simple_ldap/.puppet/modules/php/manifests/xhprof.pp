class php::xhprof (
    $output_dir = undef,
) {

    if !defined(Package['php5-xhprof']) { package { 'php5-xhprof': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/xhprof.ini':
        content => template('php/etc/php5/mods-available/xhprof.ini.erb'),
        require => Package['php5-xhprof'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::xhprof::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL xhprof',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-xhprof.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/xhprof.ini'],
        notify   => Exec['php::restart'],
    }

}
