<?php
namespace Slub\MpdbPresentation\Controller;

use Illuminate\Support\Collection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Slub\MpdbCore\Controller\AbstractController as CoreAbstractController;
use Slub\MpdbPresentation\Services\SearchServiceInterface;
use Slub\MpdbPresentation\Services\SearchServiceNotFoundException;

abstract class AbstractController extends CoreAbstractController
{
    const RESULT_COUNT = 25;
    const INDICES = [ 
        'person' => [ 
            'symbol' => '🧍',
            'controller' => 'Person' 
        ], 
        'work' => [ 
            'symbol' => '📄',
            'controller' => 'Work' 
        ],
        'published_item' => [ 
            'symbol' => '📕',
            'controller' => 'PublishedItem' 
        ],
        'instrument' => [ 
            'symbol' => '🎺',
            'controller' => 'Instrument' 
        ],
        'genre' => [ 
            'symbol' => '🎶',
            'controller' => 'Genre' 
        ]
    ];
    const EXT_NAME = 'MpdbPresentation';

    protected Collection $localizedIndices;
    protected SearchServiceInterface $searchService;

    /**
     * @throws SearchServiceNotFoundException
     */
    protected function initializeAction(): void
    {
        $this->localizedIndices = Collection::wrap(self::INDICES)->
            mapWithKeys(function ($array, $key) { return self::localizeIndex($array, $key); });

        $searchService = GeneralUtility::makeInstanceService('search');
        if (is_object($searchService)) {
            $this->searchService = $searchService;
        } else {
            throw new SearchServiceNotFoundException();
        }

        $this->searchService->
            setSize(self::RESULT_COUNT);
    }

    private static function localizeIndex(array $array, string $key): array
    {
        $body = $array;
        $translation = LocalizationUtility::translate($key, self::EXT_NAME);
        $body['translation'] = ucwords($translation);
        return [ $key => $body ];
    }
}
