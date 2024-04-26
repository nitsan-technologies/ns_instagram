<?php

defined('TYPO3') || defined('TYPO3_MODE') || die('Access denied.');

// Adding fields to the tt_content table definition in TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ns_instagram', 
    'Configuration/TypoScript', 
    '[NITSAN] Instagram'
);
