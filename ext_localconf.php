<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
	{

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NS.NsInstagram',
            'Feed',
            [
                'Feed' => 'list'
            ],
            // non-cacheable actions
            [
                'Feed' => ''
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NS.NsInstagram',
            'Imagegallery',
            [
                'ImageGallery' => 'list'
            ],
            // non-cacheable actions
            [
                'ImageGallery' => ''
            ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'NS.NsInstagram',
            'Phototile',
            [
                'PhotoTile' => 'list'
            ],
            // non-cacheable actions
            [
                'PhotoTile' => ''
            ]
        );
    },
    $_EXTKEY
);
