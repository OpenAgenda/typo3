<?php
defined('TYPO3') or die();

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

call_user_func(function()
{
    $extensionKey = 'openagenda';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        $extensionKey,
        'setup',
        "@import 'EXT:openagenda/Configuration/TypoScript/setup.typoscript'"
    );
});