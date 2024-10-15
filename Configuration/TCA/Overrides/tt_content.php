<?php

declare(strict_types=1);

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

(static function (): void {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        // extension name, matching the PHP namespaces (but without the vendor)
        'MpdbPresentation',
        // arbitrary, but unique plugin name (not visible in the backend)
        'Mpdbwelcome',
        // plugin title, as visible in the drop-down in the backend, use "LLL:" for localization
        'Music publisher database welcome plugin'
    );
})();

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['tx_mpdbpresentation_mpdbresearch'] = 'mpdb_res_icon';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['tx_mpdbpresentation_mpdbwelcome'] = 'mpdb_wel_icon';

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'] = [
    'mpdbpresentation_mpdbwelcome' => 'bodytext'
];
