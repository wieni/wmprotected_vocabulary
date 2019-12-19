<?php

namespace Drupal\wmprotected_vocabulary\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\taxonomy\VocabularyForm;
use Drupal\taxonomy\VocabularyInterface;

class VocabularyFormAlter
{
    use StringTranslationTrait;

    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;

    public function __construct(
        EntityFieldManagerInterface $entityFieldManager
    ) {
        $this->entityFieldManager = $entityFieldManager;
    }

    public function alterForm(
        array &$form,
        FormStateInterface $formState,
        string $formId
    ): void {
        if (!$this->isConfigForm($formState)) {
            return;
        }

        /* @var VocabularyForm $formObject */
        $formObject = $formState->getFormObject();
        /* @var VocabularyInterface $vocabulary */
        $vocabulary = $formObject->getEntity();

        $fields = $this->getFields($vocabulary);

        if (empty($fields)) {
            return;
        }

        $form['wmprotected_vocabulary'] = [
            '#open' => true,
            '#title' => $this->t('Protect taxonomy'),
            '#type' => 'details',
        ];

        $form['wmprotected_vocabulary']['protected'] = [
            '#default_value' => $vocabulary->getThirdPartySetting('wmprotected_vocabulary', 'protected'),
            '#description' => $this->t('Restricts deleting terms with content.'),
            '#title' => $this->t('Protect'),
            '#type' => 'checkbox',
        ];

        $form['wmprotected_vocabulary']['fields'] = [
            '#default_value' => $vocabulary->getThirdPartySetting('wmprotected_vocabulary', 'fields') ?? [],
            '#options' => $fields,
            '#states' => [
                'visible' => [
                    ':input[name="protected"]' => [
                        'checked' => true,
                    ],
                ],
            ],
            '#title' => $this->t('Protect fields'),
            '#type' => 'checkboxes',
        ];

        $form['#entity_builders'][] = [static::class, 'protectedVocabularyEntityBuilder'];
    }

    public static function protectedVocabularyEntityBuilder(
        string $entityType,
        VocabularyInterface $type,
        array $form,
        FormStateInterface $formState
    ): void {
        $protected = (bool) $formState->getValue('protected');

        if (!$protected) {
            $type->unsetThirdPartySetting('wmprotected_vocabulary', 'protected');
            $type->unsetThirdPartySetting('wmprotected_vocabulary', 'fields');
            return;
        }

        $type->setThirdPartySetting('wmprotected_vocabulary', 'protected', $protected);
        $fields = $formState->getValue('fields') ?? [];
        $type->setThirdPartySetting('wmprotected_vocabulary', 'fields', $fields);
    }

    protected function isConfigForm(FormStateInterface $formState): bool
    {
        $formObject = $formState->getFormObject();

        return $formObject instanceof VocabularyForm;
    }

    protected function getFields(VocabularyInterface $vocabulary): array
    {
        $referenceFields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');

        $fields = [];

        foreach ($referenceFields as $entityType => $fieldNames) {
            $this->processEntityTypes($vocabulary, $fields, $entityType, $fieldNames);
        }

        return $fields;
    }

    protected function processEntityTypes(
        VocabularyInterface $vocabulary,
        array &$fields,
        string $entityType,
        array $fieldNames
    ): void {
        foreach ($fieldNames as $fieldName => $info) {
            if (!isset($info['bundles'])) {
                continue;
            }

            $this->processBundles($vocabulary, $fields, $entityType, $info['bundles'], $fieldName);
        }
    }

    protected function processBundles(
        VocabularyInterface $vocabulary,
        array &$fields,
        string $entityType,
        array $bundles,
        string $fieldName
    ): void {
        foreach ($bundles as $bundle) {
            $this->processFieldDefinition($vocabulary, $fields, $entityType, $bundle, $fieldName);
        }
    }

    protected function processFieldDefinition(
        VocabularyInterface $vocabulary,
        array &$fields,
        string $entityType,
        string $bundle,
        string $fieldName
    ): void {
        $definitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

        if (!isset($definitions[$fieldName])) {
            return;
        }

        $definition = $definitions[$fieldName];

        if (!$definition instanceof FieldConfigInterface) {
            return;
        }

        $settings = $definition->getSettings();

        if (
            !isset($settings['handler'], $settings['handler_settings']['target_bundles'])
            || $settings['handler'] !== 'default:taxonomy_term'
            || !in_array($vocabulary->id(), $settings['handler_settings']['target_bundles'], true)
        ) {
            return;
        }

        $fields[$entityType . ':' . $bundle . ':' . $fieldName] = $definition->getLabel() . ' (' . $fieldName . ')';
    }
}
