<?php 
defined('TYPO3_MODE') or die();

$_EXTKEY = 'ns_instagram';

/***************
 * Plugin
 */
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'NS.NsInstagram',
    'Feed',
    'Instagram Feed'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'NS.NsInstagram',
    'Imagegallery',
    'Instagram Image Gallery'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'NS.NsInstagram',
    'Phototile',
    'Instagram Phototile'
);
    

/* Flexform setting  */
$pluginSignature = str_replace('_','',$_EXTKEY) . '_' . 'feed';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForm/Feed.xml');

$pluginSignature = str_replace('_','',$_EXTKEY) . '_' . 'imagegallery';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForm/ImageGallery.xml');

$pluginSignature = str_replace('_','',$_EXTKEY) . '_' . 'phototile';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, 'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForm/PhotoTile.xml');
