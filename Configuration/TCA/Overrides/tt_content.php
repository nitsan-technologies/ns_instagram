<?php

defined('TYPO3') || defined('TYPO3_MODE') or die();

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if (version_compare($typo3VersionArray['version_main'], '11', '>=')) {
    $extName = 'NsInstagram';
} else {
    $extName = 'NITSAN.NsInstagram';
}

/***************
 * Plugin
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $extName,
    'Instagramfeeds',
    'LLL:EXT:ns_instagram/Resources/Private/Language/locallang_db.xlf:tx_ns_instagram_instagramfeeds.name',
    'ext-ns-instagram-icon',
    'plugins'
);

/* Flexform setting  */
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['nsinstagram_instagramfeeds'] = 'recursive,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['nsinstagram_instagramfeeds'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'nsinstagram_instagramfeeds',
    'FILE:EXT:ns_instagram/Configuration/FlexForm/Instagramfeeds.xml'
);
