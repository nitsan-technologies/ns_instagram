<?php

defined('TYPO3') || defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ns_instagram/Configuration/TSconfig/ContentElementWizard.tsconfig">'
);
