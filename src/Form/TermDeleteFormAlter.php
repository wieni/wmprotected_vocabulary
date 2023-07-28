<?php

namespace Drupal\wmprotected_vocabulary\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Form\TermDeleteForm;
use Drupal\taxonomy\VocabularyInterface;

class TermDeleteFormAlter
{
    use StringTranslationTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var MessengerInterface */
    protected $messenger;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        MessengerInterface $messenger
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->messenger = $messenger;
    }

    public function alterForm(
        array &$form,
        FormStateInterface $formState,
        string $formId
    ): void {
        if (!$formState->getFormObject() instanceof TermDeleteForm) {
            return;
        }

        /* @var TermDeleteForm $formObject */
        $formObject = $formState->getFormObject();
        /* @var Term $term */
        $term = $formObject->getEntity();
        $vocabularyId = $term->bundle();

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
        $this->messenger->addStatus($this->t('This term can not be deleted since it has content.'));
    }

    protected function checkContent(Term $term, array $fields): bool
    {
        foreach (array_filter($fields) as $field) {
            [$entityType, $bundle, $fieldName] = explode(':', $field);

            if (!$entityType || !$fieldName) {
                continue;
            }

            $storage = $this->entityTypeManager->getStorage($entityType);
            $query = $storage->getQuery();
            $query->condition($fieldName, $term->id());
            $query->accessCheck(false);
            $count = $query->execute();

            if ($count) {
                return true;
            }
        }

        return false;
    }
}
