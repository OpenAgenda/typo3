<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

ExtensionUtility::registerPlugin(
    'Openagenda',
    'Agenda',
    'LLL:EXT:openagenda/Resources/Private/Language/locallang_tca.xlf:tt_content.list_type.openagenda_agenda'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['openagenda_agenda']='pages,layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['openagenda_agenda']='pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'openagenda_agenda',
    'FILE:EXT:openagenda/Configuration/Flexforms/PluginAgenda.xml'
);

ExtensionUtility::registerPlugin(
    'Openagenda',
    'Preview',
    'LLL:EXT:openagenda/Resources/Private/Language/locallang_tca.xlf:tt_content.list_type.openagenda_preview'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['openagenda_preview']='pages,layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['openagenda_preview']='pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'openagenda_preview',
    'FILE:EXT:openagenda/Configuration/Flexforms/PluginPreview.xml'
);
