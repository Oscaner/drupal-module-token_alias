<?php

namespace Drupal\token_alias;

use Drupal\Core\Entity\EntityInterface;
use Drupal\pathauto\PathautoState;

/**
 * Interface TokenAliasGeneratorInterface.
 *
 * @package Drupal\token_alias
 */
interface TokenAliasGeneratorInterface {

  /**
   * Get pathauto states from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\pathauto\PathautoState[]
   *   The array of PathautoState.
   */
  public function getPathautoStatesFromEntity(EntityInterface $entity);

  /**
   * Get pathauto state value from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $operation
   *   The operation, OR or AND,
   *   OR:  CREATE || SKIP => 1 || 0 => 1,
   *   AND: CREATE && SKIP => 1 && 0 => 0.
   * @param int $fallback_state_value
   *   The fallback state value.
   *
   * @return int
   *   The pathauto state value.
   */
  public function getPathautoStateValueFromEntity(EntityInterface $entity, $operation = 'OR', $fallback_state_value = PathautoState::CREATE);

}
