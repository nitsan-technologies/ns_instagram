<?php
defined('TYPO3_MODE') || die('Access denied.');

if (version_compare(TYPO3_branch, '11.0', '>=')) {
    $moduleClass = \NITSAN\NsInstagram\Controller\InstagramFeedsController::class;
} else {
    $moduleClass = 'InstagramFeeds';
}


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'NITSAN.NsInstagram',
    'Instagramfeeds',
    [
        $moduleClass => 'getfeeeds',
    ],
    // non-cacheable actions
    [
        $moduleClass => ''
    ]
);
   

if (version_compare(TYPO3_branch, '7.0', '>')) {
    if (TYPO3_MODE === 'BE') {
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
    }
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['nsinstagram_instagramfeeds'] = 'NITSAN\\NsInstagram\\Hooks\\PageLayoutView';
