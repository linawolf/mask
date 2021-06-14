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

namespace MASK\Mask\Controller;

use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Enumeration\FieldType;
use MASK\Mask\Helper\ConfigurationLoaderInterface;
use MASK\Mask\Helper\FieldHelper;
use MASK\Mask\Utility\AffixUtility;
use MASK\Mask\Utility\DateUtility;
use MASK\Mask\Utility\TcaConverterUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class FieldsController
{
    protected $storageRepository;
    protected $fieldHelper;
    protected $iconFactory;
    protected $configurationLoader;

    public function __construct(
        StorageRepository $storageRepository,
        FieldHelper $fieldHelper,
        IconFactory $iconFactory,
        ConfigurationLoaderInterface $configurationLoader
    ) {
        $this->storageRepository = $storageRepository;
        $this->fieldHelper = $fieldHelper;
        $this->iconFactory = $iconFactory;
        $this->configurationLoader = $configurationLoader;
    }

    public function loadElement(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $table = $params['type'];
        $elementKey = $params['key'];

        $storage = $this->storageRepository->loadElement($table, $elementKey);
        $json['fields'] = $this->addFields($storage['tca'] ?? [], $table, $elementKey);

        return new JsonResponse($json);
    }

    public function loadField(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $table = $params['type'];
        $key = $params['key'];
        $field = $this->storageRepository->loadField($table, $key);
        $json['field'] = $this->addFields([$key => $field], $table)[0];
        $json['field']['label'] = $this->storageRepository->findFirstNonEmptyLabel($table, $key);

        return new JsonResponse($json);
    }

    /**
     * @param array $fields
     * @param string $table
     * @param string $elementKey
     * @param null $parent
     * @return array
     */
    protected function addFields(array $fields, string $table, string $elementKey = '', $parent = null)
    {
        $storage = $this->storageRepository->load();
        $defaults = $this->configurationLoader->loadDefaults();
        $nestedFields = [];
        foreach ($fields as $key => $field) {
            $newField = [
                'fields' => [],
                'parent' => $parent ?? [],
                'newField' => false,
            ];

            if ($parent) {
                $newField['key'] = isset($field['coreField']) ? $field['key'] : $field['maskKey'];
            } else {
                $newField['key'] = $key;
            }

            if ($elementKey !== '') {
                $newField['label'] = $this->fieldHelper->getLabel($elementKey, $newField['key'], $table);
                $newField['label'] = $this->translateLabel($newField['label']);
            }

            $fieldType = FieldType::cast($this->storageRepository->getFormType($newField['key'], $elementKey, $table));

            // Convert old date format Y-m-d to d-m-Y
            $dbType = $field['config']['dbType'] ?? false;
            if ($dbType && in_array($dbType, ['date', 'datetime'], true)) {
                $lower = $field['config']['range']['lower'] ?? false;
                $upper = $field['config']['range']['upper'] ?? false;
                if ($lower && DateUtility::isOldDateFormat($lower)) {
                    $field['config']['range']['lower'] = DateUtility::convertOldToNewFormat($dbType, $lower);
                }
                if ($upper && DateUtility::isOldDateFormat($upper)) {
                    $field['config']['range']['upper'] = DateUtility::convertOldToNewFormat($dbType, $upper);
                }
            }

            $newField['isMaskField'] = AffixUtility::hasMaskPrefix($newField['key']);
            $newField['name'] = (string)$fieldType;
            $newField['icon'] = $this->iconFactory->getIcon('mask-fieldtype-' . $newField['name'])->getMarkup();
            $newField['description'] = $field['description'] ?? '';
            $newField['tca'] = [];

            if (!$newField['isMaskField']) {
                $nestedFields[] = $newField;
                continue;
            }

            if (!$fieldType->isGroupingField()) {
                $newField['sql'] = $storage[$table]['sql'][$newField['key']][$table][$newField['key']];
                $newField['tca'] = TcaConverterUtility::convertTcaArrayToFlat($field['config'] ?? []);
                $newField['tca']['l10n_mode'] = $field['l10n_mode'] ?? '';
            }

            if ($fieldType->equals(FieldType::TIMESTAMP)) {
                $default = $newField['tca']['config.default'] ?? false;
                if ($default) {
                    $newField['tca']['config.default'] = DateUtility::convertTimestampToDate($newField['tca']['config.eval'], $default);
                }
                $lower = $newField['tca']['config.range.lower'] ?? false;
                if ($lower) {
                    $newField['tca']['config.range.lower'] = DateUtility::convertTimestampToDate($newField['tca']['config.eval'], $lower);
                }
                $upper = $newField['tca']['config.range.upper'] ?? false;
                if ($upper) {
                    $newField['tca']['config.range.upper'] = DateUtility::convertTimestampToDate($newField['tca']['config.eval'], $upper);
                }
            }

            if ($fieldType->equals(FieldType::FILE)) {
                $newField['tca']['imageoverlayPalette'] = $field['imageoverlayPalette'] ?? 1;
                // Since mask v7.0.0 the path for allowedFileExtensions has changed to root level.
                $allowedFileExtensionsPath = 'config.filter.0.parameters.allowedFileExtensions';
                $newField['tca']['allowedFileExtensions'] = $field['allowedFileExtensions'] ?? $newField['tca'][$allowedFileExtensionsPath] ?? '';
                // Remove old path.
                if (isset($newField['tca'][$allowedFileExtensionsPath])) {
                    unset($newField['tca'][$allowedFileExtensionsPath]);
                }
            }

            if ($fieldType->equals(FieldType::CONTENT)) {
                $newField['tca']['cTypes'] = $field['cTypes'] ?? [];
            }

            // Set defaults for mask fields
            foreach ($defaults[(string)$fieldType]['tca_in'] ?? [] as $tcaKey => $defaultValue) {
                $newField['tca'][$tcaKey] = $newField['tca'][$tcaKey] ?? $defaultValue;
            }

            if ($fieldType->equals(FieldType::INLINE)) {
                $newField['tca']['ctrl.iconfile'] = $field['ctrl']['iconfile'] ?? $field['inlineIcon'] ?? '';
                $newField['tca']['ctrl.label'] = $field['ctrl']['label'] ?? $field['inlineLabel'] ?? '';
            }

            $newField['tca'] = $this->cleanUpConfig($newField['tca'], $fieldType);

            if ($fieldType->isParentField()) {
                $inlineTable = $fieldType->equals(FieldType::INLINE) ? $newField['key'] : $table;
                $newField['fields'] = $this->addFields(
                    $this->storageRepository->loadInlineFields($newField['key'], $elementKey),
                    $inlineTable,
                    $elementKey,
                    $newField
                );
            }

            $nestedFields[] = $newField;
        }
        return $nestedFields;
    }

    /**
     * This method removes all tca options defined which aren't available in mask.
     *
     * @param array $config
     * @param FieldType $fieldType
     * @return array
     */
    protected function cleanUpConfig(array $config, FieldType $fieldType): array
    {
        $tabConfig = $this->configurationLoader->loadTab($fieldType);
        $tcaOptions = [];
        foreach ($tabConfig as $options) {
            foreach ($options as $row) {
                $tcaOptions = array_merge($tcaOptions, array_keys($row));
            }
        }
        return array_filter($config, function ($key) use ($tcaOptions) {
            return in_array($key, $tcaOptions);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function translateLabel(string $key): string
    {
        if (empty($key) || strpos($key, 'LLL') !== 0) {
            return $key;
        }

        $result = LocalizationUtility::translate($key);
        return empty($result) ? $key : $result;
    }
}