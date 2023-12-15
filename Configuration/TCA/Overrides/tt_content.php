<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        // extension name, matching the PHP namespaces (but without the vendor)
        'MpdbPresentation',
        // arbitrary, but unique plugin name (not visible in the backend)
        'Mpdbresearch',
        // plugin title, as visible in the drop-down in the backend, use "LLL:" for localization
        'Music publisher database research plugin'
    );
})();

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['tx_mpdbpresentation_mpdbresearch'] = 'mpdb_res_icon';
$GLOBALS['TCA']['tt_content']['types']['tx_mpdbpresentation_mpdbresearch'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
           --palette--;;general,
           header; Title,
           bodytext;LLL:EXT:core/Resources/Private/Language/Form/locallang_ttc.xlf:bodytext_formlabel,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
           --palette--;;hidden,
           --palette--;;acces,
        ',
    'columnsOverrides' => [
        'bodytext' => [
            'config' => [
                'enableRichtext' => true,
                'richtextConfiguration' => 'default'
            ]
        ]
    ]
];
