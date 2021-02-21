<?php

namespace Drupal\token_alias;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\pathauto\PathautoGenerator;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\pathauto\PathautoItem;
use Drupal\pathauto\PathautoState;

/**
 * Class TokenAliasGenerator, handle token alias.
 *
 * @package Drupal\token_alias
 */
class TokenAliasGenerator extends PathautoGenerator implements TokenAliasGeneratorInterface, PathautoGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function getPathautoStatesFromEntity(EntityInterface $entity) {
    // Checks, if the entity no content entity,
    // or the entity not have path field.
    if (!($entity instanceof ContentEntityInterface) || !$entity->hasField('path')) {
      return [];
    }

    $pathauto_states = [];

    $item_count = $entity->get('path')->count() - 1;
    while ($item_count >= 0) {
      try {
        $pathauto_item = $entity->get('path')->get($item_count);
        if ($pathauto_item instanceof PathautoItem && ($pathauto_state = $pathauto_item->get('pathauto')) && ($pathauto_state instanceof PathautoState)) {
          $pathauto_states[$item_count] = $pathauto_state;
        }
      }
      catch (MissingDataException $exception) {
        // Do nothing.
      }
      $item_count--;
    }

    return $pathauto_states;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathautoStateValueFromEntity(EntityInterface $entity, $operation = 'OR', $fallback_state_value = PathautoState::CREATE) {
    // Get all pathauto states of entity.
    $pathauto_states = array_map(function (PathautoState $state) {
      return (int) $state->getValue();
    }, $this->getPathautoStatesFromEntity($entity));

    // If pathauto states is empty, to fallback.
    if (empty($pathauto_states)) {
      return $fallback_state_value;
    }

    // Have 1 to 1 (E.g. 1||0).
    if ($operation === 'OR') {
      return in_array(PathautoState::CREATE, $pathauto_states, TRUE) ? PathautoState::CREATE : PathautoState::SKIP;
    }
    // Have 0 to 0 (E.g. 1&&0).
    elseif ($operation === 'AND') {
      return in_array(PathautoState::SKIP, $pathauto_states, TRUE) ? PathautoState::SKIP : PathautoState::CREATE;
    }

    return $fallback_state_value;
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityAlias(EntityInterface $entity, $op, array $options = []) {
    // Skip if the entity does not have the path field.
    if (!($entity instanceof ContentEntityInterface) || !$entity->hasField('path')) {
      return parent::updateEntityAlias($entity, $op, $options);
    }

    // Handle default action if pathauto processing no disabled,
    // or the force option no empty.
    // @see \Drupal\pathauto\PathautoGenerator::updateEntityAlias(), 352.
    if ($entity->path->pathauto != PathautoState::SKIP || !empty($options['force'])) {
      return parent::updateEntityAlias($entity, $op, $options);
    }

    // Only act if this is the default revision.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return parent::updateEntityAlias($entity, $op, $options);
    }

//    $options += ['language' => $entity->language()->getId()];
//    $type = $entity->getEntityTypeId();

    // Skip processing if the entity has no pattern.
//    if (!$this->getPatternByEntity($entity)) {
//      return parent::updateEntityAlias($entity, $op, $options);
//    }

    $alias = $entity->path->alias ?? '';
    $alias_tokens = $this->token->scan($alias);

    // Skip processing of the entity alias has no token.
    if (empty($alias_tokens)) {
      return NULL;
    }

    // Deal with taxonomy specific logic.
    // @todo Update and test forum related code.
//    if ($type == 'taxonomy_term') {
//      $config_forum = $this->configFactory->get('forum.settings');
//      if ($entity->bundle() == $config_forum->get('vocabulary')) {
//        $type = 'forum';
//      }
//    }

    // Handle alias.
    try {
      $result = $this->createEntityAlias($entity, $op);
    }
    catch (\InvalidArgumentException $e) {
      $this->messenger()->addError($e->getMessage());
      return NULL;
    }

    // @todo Move this to a method on the pattern plugin.
//    if ($type == 'taxonomy_term') {
//      foreach ($this->loadTermChildren($entity->id()) as $subterm) {
//        $this->updateEntityAlias($subterm, $op, $options);
//      }
//    }

    return $result;
  }

  /**
   *  Replace token alias to create an  alias.
   *
   * {@inheritdoc}
   */
  public function createEntityAlias(EntityInterface $entity, $op) {
    // Retrieve and apply the token alias for this content type.
    $token_alias = $entity->path->alias ?? NULL;
    if (empty($token_alias)) {
      // No alias? Do nothing (otherwise we may blow away existing aliases...)
      return NULL;
    }

    // Retrieve the tokens from the token alias.
    $alias_tokens = $this->token->scan($token_alias);
    if (empty($alias_tokens)) {
      // No tokens? Do nothing (Because we may not necessary, Drupal Core will handle...)
      return NULL;
    }

    try {
      $internal_path = $entity->toUrl()->getInternalPath();
    }
      // @todo convert to multi-exception handling in PHP 7.1.
    catch (EntityMalformedException $exception) {
      return NULL;
    }
    catch (UndefinedLinkTemplateException $exception) {
      return NULL;
    }
    catch (\UnexpectedValueException $exception) {
      return NULL;
    }

    $source = '/' . $internal_path;
    $langcode = $entity->language()->getId();

    // Core does not handle aliases with language Not Applicable.
    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Build token data.
    $data = [
      $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];

    // Allow other modules to alter the token alias.
    $context = [
      'module' => $entity->getEntityType()->getProvider(),
      'op' => $op,
      'source' => $source,
      'data' => $data,
      'bundle' => $entity->bundle(),
      'language' => &$langcode,
    ];
    $this->moduleHandler->alter('token_alias_token_alias', $token_alias, $context);

    // Special handling when updating an item which is already aliased.
    $existing_alias = NULL;
    if (!empty($alias_tokens) || $op == 'update' || $op == 'bulkupdate') {
      $existing_alias = $this->aliasStorageHelper->loadBySource($source, $langcode);
    }
    // Focus on existing alias with token string.
    if (!empty($alias_tokens) && $existing_alias['alias'] !== $token_alias && $this->aliasStorageHelper instanceof TokenAliasStorageHelperInterface) {
      $existing_alias = ($this->aliasStorageHelper->loadByAlias($token_alias, $langcode)) ?: $existing_alias;
    }

    // Replace any tokens in the token alias.
    // Uses callback option to clean replacements. No sanitization.
    // Pass empty BubbleableMetadata object ot explicitly ignore cacheablity,
    // as the result is never rendered.
    $alias = $this->token->replace($token_alias, $data, [
      'clear' => TRUE,
      'callback' => [$this->aliasCleaner, 'cleanTokenValues'],
      'langcode' => $langcode,
      'pathauto' => TRUE,
    ], new BubbleableMetadata());

    // Check if the token replacement has not actually replaced any values. If
    // that is the case, then stop because we should not generate an alias,
    // @see token_scan().
    $token_alias_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', $token_alias);
    if (trim($alias, '/') === trim($token_alias_tokens_removed, '/')) {
      preg_match_all('/\[[^\s\]:]*:[^\s\]]*\]/', $token_alias, $tokens_string);
      $tokens_string = array_unique($tokens_string[0] ?? []);
      $this->messenger()->addWarning($this->t('The tokens\' value all empty in current alias, do not replace. ( Tokens: %tokens )', [
        '%tokens' => implode(', ', $tokens_string),
      ]));
      return NULL;
    }

    $alias = $this->aliasCleaner->cleanAlias($alias);

    // Allow other modules to alter the alias.
    $context['source'] = &$source;
    $context['token_alias'] = $token_alias;
    $this->moduleHandler->alter('token_alias_alias', $alias, $context);

    // If we have arrived at an empty string, discontinue.
    if (!mb_strlen($alias)) {
      return NULL;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $original_alias = $alias;
    $this->aliasUniquifier->uniquify($alias, $source, $langcode);
    if ($original_alias != $alias) {
      // Alter the user why this happened.
      $this->pathautoMessenger->addMessage($this->t('The alias contain tokens will replace to %original_alias, but it conflicted with an existing alias. Alias changed to %alias.', [
        '%original_alias' => $original_alias,
        '%alias' => $alias,
      ]), $op);
    }

    // Return the generated alias if requested.
    if ($op == 'return') {
      return $alias;
    }

    // Update action.
    $update_action = $entity->path->pathauto == PathautoState::SKIP ? PathautoGeneratorInterface::UPDATE_ACTION_DELETE : NULL;

    // Build the new path alias array and send it off to be created.
    return $this->aliasStorageHelper->save([
      'source' => $source,
      'alias' => $alias,
      'language' => $langcode,
    ], $existing_alias, $op, $update_action);
  }

}
