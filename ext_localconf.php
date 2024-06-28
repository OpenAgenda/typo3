<?php
defined('TYPO3') or die();

use Openagenda\Openagenda\Controller\OpenagendaService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use Openagenda\Openagenda\Controller\OpenagendaController;

ExtensionUtility::configurePlugin(
    'Openagenda',
    'Agenda',
    [OpenagendaController::class => 'agenda, event, filtersCallback, ajaxCallback'],
    [OpenagendaController::class => 'agenda, event, filtersCallback, ajaxCallback']
);

ExtensionUtility::configurePlugin(
    'Openagenda',
    'Preview',
    [OpenagendaController::class => 'preview'],
    [OpenagendaController::class => 'preview']
);

ExtensionUtility::configurePlugin(
    'Openagenda',
    'Ajax',
    [OpenagendaController::class => 'ajaxCallback'],
    [OpenagendaController::class => 'ajaxCallback']
);

ExtensionUtility::configurePlugin(
    'Openagenda',
    'Filters',
    [OpenagendaController::class => 'filtersCallback'],
    [OpenagendaController::class => 'filtersCallback']
);

ExtensionManagementUtility::addService(
// Extension Key
	'openagenda',
	'agenda',
	'tx_openagenda_agenda',
	[
		'title' => 'OpenAgenda',
		'description' => 'Service for OpenAgenda',
		'subtype' => '',
		'available' => true,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'className' => OpenagendaService::class,
	],
);

call_user_func(function()
{
    $extensionKey = 'openagenda';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionKey,
        'setup',
        "@import 'EXT:openagenda/Configuration/TypoScript/setup.typoscript'"
    );
});