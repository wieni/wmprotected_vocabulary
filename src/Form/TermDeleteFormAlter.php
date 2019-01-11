<?php

namespace Drupal\wmprotected_vocabulary\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Form\TermDeleteForm;
use Drupal\taxonomy\VocabularyInterface;

class TermDeleteFormAlter
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public function alterForm(
        array &$form,
        FormStateInterface $formState,
        string $formId
    ) {
        if (!$this->isTermDeleteForm($formState)) {
            return;
        }

        /* @var TermDeleteForm $formObject */
        $formObject = $formState->getFormObject();
        /* @var Term $term */
        $term = $formObject->getEntity();
        $vocabularyId = $term->getVocabularyId();

        /* @var VocabularyInterface $vocabulary */
        $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabularyId);

        $protected = $vocabulary->getThirdPartySetting('wmprotected_vocabulary', 'protected');

        if (!$protected) {
            return;
        }

        $fields = $vocabulary->getThirdPartySetting('wmprotected_vocabulary', 'fields');

        $hasContent = $this->checkContent($term, $fields);

        if (!$hasContent) {
            return;
        }

        $form['actions']['submit']['#disabled'] = true;
        drupal_set_message($this->t('This term can not be deleted since it has content.'));
    }

    protected function isTermDeleteForm(FormStateInterface $formState)
    {
        $formObject = $formState->getFormObject();

        return $formObject instanceof TermDeleteForm;
    }

    protected function checkContent(Term $term, array $fields)
    {
        foreach ($fields as $field) {
            list($entityType, $bundle, $fieldName) = explode(':', $field);

            if (!$entityType || !$fieldName) {
                continue;
            }

            $storage = $this->entityTypeManager->getStorage($entityType);
            $query = $storage->getQuery();
            $query->condition($fieldName, $term->id());
            $count = $query->execute();

            if ($count) {
                return true;
            }
        }

        return false;
    }
}
