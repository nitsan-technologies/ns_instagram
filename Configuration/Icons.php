<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-ns-instagram-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ns_instagram/Resources/Public/Icons/ns_instagram.svg',
    ],
];
