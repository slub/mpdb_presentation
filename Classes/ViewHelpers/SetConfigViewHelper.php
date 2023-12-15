<?php

namespace Slub\MpdbPresentation\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class SetConfigViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('config', 'array', 'The current configuration', true);
        $this->registerArgument('key', 'string', 'The key to be set', true);
        $this->registerArgument('value', 'string', 'The value to be set', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    )
    {
        $config = $arguments['config'];
        $key = $arguments['key'];
        $value = $arguments['value'];

        if ($value == '') {
            unset($config[$key]);
        } else {
            $config[$key] = $value;
        }

        return $config;
    }
}
