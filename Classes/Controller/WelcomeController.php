<?php

namespace Slub\MpdbPresentation\Controller;

use \Illuminate\Support\Collection;
use \Illuminate\Support\Str;
use \Psr\Http\Message\ResponseInterface;
use \TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use \Slub\MpdbPresentation\Domain\Model\Publisher;

/***
 *
 * This file is part of the "Publisher Database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2021 Matthias Richter <matthias.richter@slub-dresden.de>, SLUB Dresden
 *
 ***/
/**
 * PublishedItemController
 */
class WelcomeController extends AbstractController
{

    /**
     * action welcome
     * 
     * @return void
     */
    public function welcomeAction(): ResponseInterface
    {
        $bodyText = $this->configurationManager->getContentObject()->getFieldVal('bodytext');
        $and = LocalizationUtility::translate('LLL:EXT:mpdb_presentation/Resources/Private/Language/locallang:and');

        $entityCount = $this->searchService->
            setIndex(PublishedItemController::TABLE_INDEX_NAME)->
            count();

        $publishers = $this->searchService->
            reset()->
            setIndex(Publisher::INDEX_NAME)->
            search()->
            pluck('_source')->
            map(function ($publisher) { return self::getPublisherName($publisher); })->
            join(', ', ' ' . $and . ' ');

        $processedText = Str::of($bodyText)->replace('{{ publishers }}', $publishers)->
            replace('{{ count }}', $entityCount);

        $this->view->assign('processedText', $processedText);

        return $this->htmlResponse();
    }

    private static function getPublisherName(Collection $publisher): string
    {
        return $publisher->get('name') . ' (' . $publisher->get('shorthand') . ')';
    }

}
