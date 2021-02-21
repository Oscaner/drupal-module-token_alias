<?php

namespace Drupal\token_alias;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\EntityBrowserFormInterface;
use Drupal\media_library\Form\FileUploadForm;

/**
 * Class TokenAliasFormHelper.
 *
 * @package Drupal\token_alias
 */
class TokenAliasFormHelper implements TokenAliasFormHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromFormState(FormStateInterface $form_state) {
    // Form object.
    $form_object = $form_state->getFormObject();

    // Prepare entity.
    $entity = NULL;

    // Handle entity form.
    if ($form_object instanceof EntityFormInterface) {
      $entity = $form_object->getEntity();
    }
    // Handle entity browser form.
    elseif ($form_object instanceof EntityBrowserFormInterface) {
      $entity_form = $form_state->getCompleteForm()['widget']['entity'] ?? [];
      if (isset($entity_form['#entity'])) {
        $entity = $entity_form['#entity'];
      }
      elseif (isset($entity_form['#default_value'])) {
        $entity = $entity_form['#default_value'];
      }
    }
    // Handle file upload form.
    elseif ($form_state instanceof FileUploadForm) {
      $entities = $form_state->get('media') ?: [];
      $entity = array_shift($entities) ?: NULL;
    }

    return $entity;
  }

}
