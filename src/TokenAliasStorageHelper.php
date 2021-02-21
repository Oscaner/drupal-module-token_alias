<?php

namespace Drupal\token_alias;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\pathauto\AliasStorageHelper;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\MessengerInterface;
use Drupal\path_alias\AliasRepositoryInterface;

/**
 * Class TokenAliasStorageHelper.
 *
 * @package Drupal\token_alias
 */
class TokenAliasStorageHelper extends AliasStorageHelper implements TokenAliasStorageHelperInterface {

  /**
   * @var \Drupal\pathauto\AliasStorageHelperInterface
   */
  protected $subject;

  /**
   * The config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\AliasRepositoryInterface $alias_repository
   *   The alias repository.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manger.
   */
  public function __construct(AliasStorageHelperInterface $subject, ConfigFactoryInterface $config_factory, AliasRepositoryInterface $alias_repository, Connection $database, MessengerInterface $messenger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->subject = $subject;
    $this->configFactory = $config_factory;
    $this->aliasRepository = $alias_repository;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasSchemaMaxLength() {
    return $this->subject->getAliasSchemaMaxLength();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $path, $existing_alias = NULL, $op = NULL, $update_action = NULL) {
    return $this->subject->save($path, $existing_alias, $op, $update_action);
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySource($source, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    return $this->subject->loadBySource($source, $language);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByAlias($alias, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $alias = $this->aliasRepository->lookupByAlias($alias, $language);
    if ($alias) {
      return [
        'pid' => $alias['id'],
        'alias' => $alias['alias'],
        'source' => $alias['path'],
        'langcode' => $alias['langcode'],
      ];
    }
    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteBySourcePrefix($source) {
    return $this->subject->deleteBySourcePrefix($source);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    return $this->subject->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityPathAll(EntityInterface $entity, $default_uri = NULL) {
    return $this->subject->deleteEntityPathAll($entity, $default_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySourcePrefix($source) {
    return $this->subject->loadBySourcePrefix($source);
  }

  /**
   * {@inheritdoc}
   */
  public function countBySourcePrefix($source) {
    return $this->subject->countBySourcePrefix($source);
  }

  /**
   * {@inheritdoc}
   */
  public function countAll() {
    return $this->subject->countAll();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple($pids) {
    return $this->subject->deleteMultiple($pids);
  }

}
