<?php

use \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'mpdb_api_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_api_icon.svg'
    ],
    'mpdb_res_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_res_icon.svg'
    ],
    'mpdb_wel_icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_wel_icon.svg'
    ]
];
