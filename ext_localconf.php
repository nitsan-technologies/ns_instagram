<?php

defined('TYPO3') || defined('TYPO3_MODE') || die('Access denied.');

$typo3VersionArray = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionStringToArray(
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()
);

if (version_compare($typo3VersionArray['version_main'], '11', '>=')) {
    $moduleClass = \NITSAN\NsInstagram\Controller\InstagramFeedsController::class;
    $moduleName = 'NsInstagram';
} else {
    $moduleClass = 'InstagramFeeds';
    $moduleName = 'NITSAN.NsInstagram';
}


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    $moduleName,
    'Instagramfeeds',
    [
        $moduleClass => 'getfeeeds',
    ],
    // non-cacheable actions
    [
        $moduleClass => ''
    ]
);

$icons = [
    'ext-ns-instagram-icon' => 'ns_instagram.svg',
];
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
foreach ($icons as $identifier => $path) {
    $iconRegistry->registerIcon(
        $identifier,
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:ns_instagram/Resources/Public/Icons/' . $path]
    );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_instagram/Configuration/TSconfig/ContentElementWizard.tsconfig">'
);
