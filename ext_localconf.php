<?php

use \Slub\MpdbPresentation\Controller\IndexController;
use \Slub\MpdbPresentation\Controller\PersonController;
use \Slub\MpdbPresentation\Controller\WorkController;
use \Slub\MpdbPresentation\Controller\PublishedItemController;
use \Slub\MpdbPresentation\Controller\InstrumentController;
use \Slub\MpdbPresentation\Controller\FormController;
use \Slub\MpdbPresentation\Controller\WelcomeController;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'MpdbPresentation',
            'Mpdbpublisherapi',
            [
                \Slub\MpdbPresentation\Controller\ApiController::class => 'api'
            ],
            // non-cacheable actions
			[ ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'MpdbPresentation',
            'Mpdbwelcome',
            [
                WelcomeController::class => 'welcome'
            ],
            // non-cacheable actions
			[ ]
        );

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'MpdbPresentation',
            'Mpdbresearch',
            [
                IndexController::class => 'search',
                PersonController::class => 'show',
                WorkController::class => 'show',
                PublishedItemController::class => 'show',
                InstrumentController::class => 'show',
                FormController::class => 'show'
            ],
            // non-cacheable actions
            [
            ]
        );

        // wizards
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            'mod {
                wizards.newContentElement.wizardItems.plugins {
                    elements {
                        tx_mpdbpresentation_mpdbresearch {
                            iconIdentifier = mpdb_res_icon
                            title = LLL:EXT:mpdb_presentation/Resources/Private/Language/locallang.xlf:start_title
                            description = LLL:EXT:mpdb_presentation/Resources/Private/Language/locallang.xlf:start_description
                            tt_content_defValues {
                                CType = list
                                list_type = tx_mpdbpresentation_mpdbresearch
                            }
                        }
                        mpdbpresentation_mpdbwelcome {
                            iconIdentifier = mpdb_wel_icon
                            title = LLL:EXT:mpdb_presentation/Resources/Private/Language/locallang.xlf:welcome_title
                            description = LLL:EXT:mpdb_presentation/Resources/Private/Language/locallang.xlf:welcome_description
                            tt_content_defValues {
                                CType = list
                                list_type = tx_mpdbpresentation_mpdbwelcome
                            }
                        }
                    }
                    show = *
                }
           }'
        );

        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		
        $iconRegistry->registerIcon(
            'mpdb_presentation-work',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_icon_work.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-person',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_icon_person.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-item',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_icon_item.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-gnd',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_icon_gnd.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-search',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/mpdb_icon_search.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-plugin-fepublisherdb',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/user_plugin_fepublisherdb.svg']
        );
        $iconRegistry->registerIcon(
            'mpdb_presentation-plugin-fewelcome',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:mpdb_presentation/Resources/Public/Icons/user_plugin_welcome.svg']
        );
    }
);
