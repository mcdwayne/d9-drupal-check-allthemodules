class apache::mod::pagespeed (
    $authz_require = undef,
) {

    if !defined(Package['wget']) { package { 'wget': } }

    exec { 'apache::mod::pagespeed::download':
        command => 'wget https://dl-ssl.google.com/dl/linux/direct/mod-pagespeed-stable_current_amd64.deb',
        cwd     => '/usr/local/src',
        creates => '/usr/local/src/mod-pagespeed-stable_current_amd64.deb',
        require => Package['wget'],
    }

    exec { 'apache::mod::pagespeed::install':
        command => 'dpkg -i /usr/local/src/mod-pagespeed-stable_current_amd64.deb',
        creates => [
            '/usr/lib/apache2/modules/mod_pagespeed_ap24.so',
            '/etc/apache2/mods-available/pagespeed.load',
            '/etc/apache2/mods-available/pagespeed.conf',
        ],
        require => [
            Package['apache2'],
            Exec['apache::mod::pagespeed::download'],
            ],
    }

    exec { 'apache::mod::pagespeed::enable':
        command => 'a2enmod pagespeed',
        creates => [
            '/etc/apache2/mods-enabled/pagespeed.load',
            '/etc/apache2/mods-enabled/pagespeed.conf',
        ],
        require => [
            Exec['apache::mod::pagespeed::install'],
            Package['apache2'],
        ],
        notify => Service['apache2'],
    }

    file { '/etc/apache2/mods-available/pagespeed.conf':
        content => template('apache/etc/apache2/mods-available/pagespeed.conf.erb'),
        require => Exec['apache::mod::pagespeed::enable'],
        notify  => Service['apache2'],
    }

}
