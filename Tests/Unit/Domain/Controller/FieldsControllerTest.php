<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use MASK\Mask\Controller\FieldsController;
use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Domain\Service\SettingsService;
use MASK\Mask\Helper\FieldHelper;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\TestingFramework\Core\BaseTestCase;

class FieldsControllerTest extends BaseTestCase
{
    public function loadElementDataProvider()
    {
        return [
            'Simple fields converted to fields array' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                    'tx_mask_field2',
                                    'header'
                                ],
                                'labels' => [
                                    'Field 1',
                                    'Field 2',
                                    'Core Header'
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input'
                                ],
                                'key' => 'field1',
                                'name' => 'string',
                                'description' => 'Field 1 Description',
                                'l10n_mode' => ''
                            ],
                            'tx_mask_field2' => [
                                'config' => [
                                    'eval' => 'int',
                                    'type' => 'input'
                                ],
                                'key' => 'field2',
                                'name' => 'integer',
                                'description' => 'Field 2 Description',
                                'l10n_mode' => 'exclude'
                            ],
                            'header' => [
                                'coreField' => 1,
                                'key' => 'header',
                                'name' => 'string'
                            ]
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                            'tx_mask_field2' => [
                                'tt_content' => [
                                    'tx_mask_field2' => 'tinytext'
                                ]
                            ]
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'string',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0
                            ]
                        ],
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field2',
                            'label' => 'Field 2',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'integer',
                            'icon' => '',
                            'description' => 'Field 2 Description',
                            'tca' => [
                                'l10n_mode' => 'exclude',
                                'config.eval.null' => 0
                            ]
                        ],
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'header',
                            'label' => 'Core Header',
                            'isMaskField' => false,
                            'name' => 'string',
                            'icon' => '',
                            'description' => '',
                            'tca' => []
                        ]
                    ]
                ]
            ],
            'Palette fields work' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                    'tx_mask_palette1',
                                ],
                                'labels' => [
                                    'Field 1',
                                    'Palette 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input'
                                ],
                                'key' => 'field1',
                                'name' => 'string',
                                'description' => 'Field 1 Description'
                            ],
                            'tx_mask_palette1' => [
                                'config' => [
                                    'type' => 'palette'
                                ],
                                'name' => 'palette',
                                'key' => 'palette1'
                            ],
                            'tx_mask_field2' => [
                                'config' => [
                                    'eval' => 'int',
                                    'type' => 'input'
                                ],
                                'key' => 'field2',
                                'name' => 'integer',
                                'description' => 'Field 2 Description',
                                'label' => [
                                    'element1' => 'Field 2'
                                ],
                                'inPalette' => 1,
                                'inlineParent' => [
                                    'element1' => 'tx_mask_palette1'
                                ],
                                'order' => [
                                    'element1' => 1
                                ]
                            ],
                            'header' => [
                                'coreField' => 1,
                                'key' => 'header',
                                'name' => 'string',
                                'inPalette' => 1,
                                'inlineParent' => [
                                    'element1' => 'tx_mask_palette1'
                                ],
                                'order' => [
                                    'element1' => 2
                                ],
                                'label' => [
                                    'element1' => 'Core Header'
                                ],
                            ]
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                            'tx_mask_field2' => [
                                'tt_content' => [
                                    'tx_mask_field2' => 'tinytext'
                                ]
                            ]
                        ],
                        'palettes' => [
                            'tx_mask_palette1' => [
                                'label' => 'Palette 1',
                                'showitem' => [
                                    'tx_mask_field2',
                                    'header'
                                ]
                            ]
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'string',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0
                            ]
                        ],
                        [
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_palette1',
                            'label' => 'Palette 1',
                            'isMaskField' => true,
                            'name' => 'palette',
                            'icon' => '',
                            'description' => '',
                            'tca' => [],
                            'fields' => [
                                [
                                    'fields' => [],
                                    'parent' => [
                                        'parent' => [],
                                        'newField' => false,
                                        'key' => 'tx_mask_palette1',
                                        'label' => 'Palette 1',
                                        'isMaskField' => true,
                                        'name' => 'palette',
                                        'icon' => '',
                                        'description' => '',
                                        'fields' => [],
                                        'tca' => []
                                    ],
                                    'newField' => false,
                                    'key' => 'tx_mask_field2',
                                    'label' => 'Field 2',
                                    'isMaskField' => true,
                                    'sql' => 'tinytext',
                                    'name' => 'integer',
                                    'icon' => '',
                                    'description' => 'Field 2 Description',
                                    'tca' => [
                                        'l10n_mode' => '',
                                        'config.eval.null' => 0
                                    ]
                                ],
                                [
                                    'fields' => [],
                                    'parent' => [
                                        'parent' => [],
                                        'newField' => false,
                                        'key' => 'tx_mask_palette1',
                                        'label' => 'Palette 1',
                                        'isMaskField' => true,
                                        'name' => 'palette',
                                        'icon' => '',
                                        'description' => '',
                                        'fields' => [],
                                        'tca' => []
                                    ],
                                    'newField' => false,
                                    'key' => 'header',
                                    'label' => 'Core Header',
                                    'isMaskField' => false,
                                    'name' => 'string',
                                    'icon' => '',
                                    'description' => '',
                                    'tca' => []
                                ]
                            ]
                        ],
                    ]
                ]
            ],
            'Inline fields work' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_inline1',
                                ],
                                'labels' => [
                                    'Inline 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_inline1' => [
                                'config' => [
                                    'type' => 'inline'
                                ],
                                'name' => 'inline',
                                'key' => 'inline1'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_inline1' => [
                                'tt_content' => [
                                    'tx_mask_inline1' => 'tinytext'
                                ]
                            ],
                        ],
                    ],
                    'tx_mask_inline1' => [
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input'
                                ],
                                'key' => 'field1',
                                'name' => 'string',
                                'description' => 'Field 1 Description',
                                'label' => 'Field 1',
                                'inlineParent' => 'tx_mask_inline1',
                                'order' => 1
                            ],
                            'tx_mask_field2' => [
                                'config' => [
                                    'eval' => 'int',
                                    'type' => 'input'
                                ],
                                'key' => 'field2',
                                'name' => 'integer',
                                'description' => 'Field 2 Description',
                                'label' => 'Field 2',
                                'inlineParent' => 'tx_mask_inline1',
                                'order' => 1
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tx_mask_inline1' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                            'tx_mask_field2' => [
                                'tx_mask_inline1' => [
                                    'tx_mask_field2' => 'tinytext'
                                ]
                            ],
                        ],
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_inline1',
                            'label' => 'Inline 1',
                            'isMaskField' => true,
                            'name' => 'inline',
                            'icon' => '',
                            'description' => '',
                            'sql' => 'tinytext',
                            'tca' => [
                                'config.appearance.collapseAll' => 1,
                                'config.appearance.levelLinksPosition' => 'top',
                                'config.appearance.showPossibleLocalizationRecords' => 1,
                                'config.appearance.showAllLocalizationLink' => 1,
                                'config.appearance.showRemovedLocalizationRecords' => 1,
                                'ctrl.iconfile' => '',
                                'ctrl.label' => '',
                                'l10n_mode' => '',
                            ],
                            'fields' => [
                                [
                                    'fields' => [],
                                    'parent' => [
                                        'fields' => [],
                                        'parent' => [],
                                        'newField' => false,
                                        'key' => 'tx_mask_inline1',
                                        'label' => 'Inline 1',
                                        'isMaskField' => true,
                                        'name' => 'inline',
                                        'icon' => '',
                                        'description' => '',
                                        'sql' => 'tinytext',
                                        'tca' => [
                                            'config.appearance.collapseAll' => 1,
                                            'config.appearance.levelLinksPosition' => 'top',
                                            'config.appearance.showPossibleLocalizationRecords' => 1,
                                            'config.appearance.showAllLocalizationLink' => 1,
                                            'config.appearance.showRemovedLocalizationRecords' => 1,
                                            'ctrl.iconfile' => '',
                                            'ctrl.label' => '',
                                            'l10n_mode' => '',
                                        ],
                                    ],
                                    'newField' => false,
                                    'key' => 'tx_mask_field1',
                                    'label' => 'Field 1',
                                    'isMaskField' => true,
                                    'sql' => 'tinytext',
                                    'name' => 'string',
                                    'icon' => '',
                                    'description' => 'Field 1 Description',
                                    'tca' => [
                                        'l10n_mode' => '',
                                        'config.eval.null' => 0
                                    ]
                                ],
                                [
                                    'fields' => [],
                                    'parent' => [
                                        'fields' => [],
                                        'parent' => [],
                                        'newField' => false,
                                        'key' => 'tx_mask_inline1',
                                        'label' => 'Inline 1',
                                        'isMaskField' => true,
                                        'name' => 'inline',
                                        'icon' => '',
                                        'description' => '',
                                        'sql' => 'tinytext',
                                        'tca' => [
                                            'config.appearance.collapseAll' => 1,
                                            'config.appearance.levelLinksPosition' => 'top',
                                            'config.appearance.showPossibleLocalizationRecords' => 1,
                                            'config.appearance.showAllLocalizationLink' => 1,
                                            'config.appearance.showRemovedLocalizationRecords' => 1,
                                            'ctrl.iconfile' => '',
                                            'ctrl.label' => '',
                                            'l10n_mode' => '',
                                        ],
                                    ],
                                    'newField' => false,
                                    'key' => 'tx_mask_field2',
                                    'label' => 'Field 2',
                                    'isMaskField' => true,
                                    'sql' => 'tinytext',
                                    'name' => 'integer',
                                    'icon' => '',
                                    'description' => 'Field 2 Description',
                                    'tca' => [
                                        'l10n_mode' => '',
                                        'config.eval.null' => 0
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'Old allowedFileExtensions path works and imageoverlaypalette default 1' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                ],
                                'labels' => [
                                    'Field 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'inline',
                                    'filter' => [
                                        [
                                            'parameters' => [
                                                'allowedFileExtensions' => 'jpg'
                                            ]
                                        ]
                                    ]
                                ],
                                'options' => 'file',
                                'key' => 'field1',
                                'name' => 'file',
                                'description' => 'Field 1 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'file',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'allowedFileExtensions' => 'jpg',
                                'config.appearance.fileUploadAllowed' => 1,
                                'imageoverlayPalette' => 1
                            ]
                        ],
                    ]
                ]
            ],
            'CTypes loaded' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                ],
                                'labels' => [
                                    'Field 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'cTypes' => ['a', 'b'],
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_table' => 'tt_content'
                                ],
                                'key' => 'field1',
                                'name' => 'content',
                                'description' => 'Field 1 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'content',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'cTypes' => [
                                    'a',
                                    'b'
                                ],
                                'config.appearance.levelLinksPosition' => 'top'
                            ]
                        ],
                    ]
                ]
            ],
            'CTypes defaults to empty array' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                ],
                                'labels' => [
                                    'Field 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_table' => 'tt_content'
                                ],
                                'key' => 'field1',
                                'name' => 'content',
                                'description' => 'Field 1 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'content',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'cTypes' => [],
                                'config.appearance.levelLinksPosition' => 'top'
                            ]
                        ],
                    ]
                ]
            ],
            'Old date formats converted to new' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                    'tx_mask_field2',
                                ],
                                'labels' => [
                                    'Field 1',
                                    'Field 2'
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input',
                                    'dbType' => 'date',
                                    'eval' => 'date',
                                    'renderType' => 'inputDateTime',
                                    'range' => [
                                        'lower' => '2021-01-01'
                                    ]
                                ],
                                'key' => 'field1',
                                'name' => 'date',
                                'description' => 'Field 1 Description'
                            ],
                            'tx_mask_field2' => [
                                'config' => [
                                    'type' => 'input',
                                    'dbType' => 'datetime',
                                    'eval' => 'date',
                                    'renderType' => 'inputDateTime',
                                    'range' => [
                                        'lower' => '2021-01-01 10:10'
                                    ]
                                ],
                                'key' => 'field2',
                                'name' => 'datetime',
                                'description' => 'Field 2 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                            'tx_mask_field2' => [
                                'tt_content' => [
                                    'tx_mask_field2' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'date',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0,
                                'config.range.lower' => '01-01-2021'
                            ]
                        ],
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field2',
                            'label' => 'Field 2',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'datetime',
                            'icon' => '',
                            'description' => 'Field 2 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0,
                                'config.range.lower' => '10:10 01-01-2021'
                            ]
                        ],
                    ]
                ]
            ],
            'Timestamp fields converted to date format' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                ],
                                'labels' => [
                                    'Field 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'int,date',
                                    'renderType' => 'inputDateTime',
                                    'default' => 1623081120,
                                    'range' => [
                                        'lower' => 1623081120,
                                        'upper' => 1623081120
                                    ]
                                ],
                                'key' => 'field1',
                                'name' => 'timestamp',
                                'description' => 'Field 1 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'timestamp',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0,
                                'config.default' => date('d-m-Y', 1623081120),
                                'config.range.lower' => date('d-m-Y', 1623081120),
                                'config.range.upper' => date('d-m-Y', 1623081120),
                                'config.eval' => 'date'
                            ]
                        ]
                    ]
                ]
            ],
            'Unknown config options removed' => [
                [
                    'tt_content' => [
                        'elements' => [
                            'element1' => [
                                'color' => '#000000',
                                'icon' => 'fa-icon',
                                'key' => 'element1',
                                'label' => 'Element 1',
                                'description' => 'Element 1 Description',
                                'columns' => [
                                    'tx_mask_field1',
                                ],
                                'labels' => [
                                    'Field 1',
                                ]
                            ]
                        ],
                        'tca' => [
                            'tx_mask_field1' => [
                                'config' => [
                                    'type' => 'input',
                                    'foo' => 'bar',
                                    'baz' => [
                                        'fizz' => 'boo'
                                    ]
                                ],
                                'key' => 'field1',
                                'name' => 'string',
                                'description' => 'Field 1 Description'
                            ],
                        ],
                        'sql' => [
                            'tx_mask_field1' => [
                                'tt_content' => [
                                    'tx_mask_field1' => 'tinytext'
                                ]
                            ],
                        ]
                    ]
                ],
                'tt_content',
                'element1',
                [
                    'fields' => [
                        [
                            'fields' => [],
                            'parent' => [],
                            'newField' => false,
                            'key' => 'tx_mask_field1',
                            'label' => 'Field 1',
                            'isMaskField' => true,
                            'sql' => 'tinytext',
                            'name' => 'string',
                            'icon' => '',
                            'description' => 'Field 1 Description',
                            'tca' => [
                                'l10n_mode' => '',
                                'config.eval.null' => 0,
                            ]
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider loadElementDataProvider
     */
    public function loadElement($json, $table, $elementKey, $expected)
    {
        $GLOBALS['TCA']['tt_content']['columns']['header'] = [
            'config' => [
                'type' => 'input'
            ]
        ];
        $settingsServiceProphecy = $this->prophesize(SettingsService::class);
        $settingsServiceProphecy->get()->willReturn([]);
        $storageRepository = new StorageRepository($settingsServiceProphecy->reveal());
        $storageRepository->setJson($json);

        $iconFactory = $this->prophesize(IconFactory::class);
        $icon = new Icon();
        $icon->setMarkup('');
        $iconFactory->getIcon(Argument::cetera())->willReturn($icon);

        $fieldHelper = new FieldHelper($storageRepository);

        $configurationLoader = new \MASK\Mask\Tests\Unit\Helper\FakeConfigurationLoader();
        $fieldsController = new FieldsController($storageRepository, $fieldHelper, $iconFactory->reveal(), $configurationLoader);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getQueryParams()->willReturn(['type' => $table, 'key' => $elementKey]);

        self::assertEquals($expected, json_decode($fieldsController->loadElement($request->reveal())->getBody()->getContents(), true));
    }
}