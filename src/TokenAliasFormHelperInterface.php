<?php

namespace Drupal\token_alias;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface TokenAliasFormHelperInterface.
 *
 * @package Drupal\token_alias
 */
interface TokenAliasFormHelperInterface {

  /**
   * Get entity from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getEntityFromFormState(FormStateInterface $form_state);

}
