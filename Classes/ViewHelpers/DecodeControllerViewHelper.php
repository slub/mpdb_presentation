<?php

namespace Slub\MpdbPresentation\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use Slub\MpdbPresentation\Controller\AbstractController;

class RenderTypeViewHelper extends AbstractTagBasedViewHelper
{

    protected $tagName = 'span';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('type', 'string', 'The type which needs to be rendered', true);
    }

    public function render()
    {
        $type = $this->arguments['type'];
        $symbol = AbstractController::INDICES[$type];
        $tranlation = LocalizationUtility::translate($type, AbstractController::EXT_NAME);
        $content = $symbol . ' ' . $translation;

        return $this->tag->setContent($content)->render();
    }
}
