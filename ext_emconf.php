<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'OpenAgenda',
    'description' => 'Display calendars from https://openagenda.com on your site.',
    'state' => 'stable',
	'version' => '1.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Openagenda\\Openagenda\\' => 'Classes/',
        ],
    ],
];
