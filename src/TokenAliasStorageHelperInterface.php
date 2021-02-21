<?php

namespace Drupal\token_alias;

use Drupal\Core\Language\LanguageInterface;
use Drupal\pathauto\AliasStorageHelperInterface;

/**
 * Interface TokenAliasStorageHelperInterface.
 *
 * @package Drupal\token_alias
 */
interface TokenAliasStorageHelperInterface extends AliasStorageHelperInterface {

  /**
   * Fetches an existing URL alias given a path and optional language.
   *
   * @param string $alias
   *   An alias.
   * @param string $language
   *   An optional language code to look up the path in.
   *
   * @return null|bool|array
   *   FALSE if no alias was found or an associative array containing the
   *   following keys:
   *   - source (string): The internal system path with a starting slash.
   *   - alias (string): The URL alias with a starting slash.
   *   - pid (int): Unique path alias identifier.
   *   - langcode (string): The language code of the alias.
   */
  public function loadByAlias($alias, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED);

}
