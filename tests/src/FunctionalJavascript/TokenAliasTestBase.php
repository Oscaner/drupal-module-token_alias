<?php

namespace Drupal\Tests\token_alias\FunctionalJavascript;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\Entity\Role;

/**
 * Class TokenAliasTestBase.
 *
 * @package Drupal\Tests\token_alias\FunctionalJavascript
 */
abstract class TokenAliasTestBase extends WebDriverTestBase {

  use StringTranslationTrait;
  use TokenAliasTestHelperTrait;

  /**
   * The content user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The node storage object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * The alias storage object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $aliasStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'token_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');

    $this->aliasStorage = $this->container->get('entity_type.manager')->getStorage('path_alias');

    $this->adminUser = $this->drupalCreateUser([
      'administer pathauto',
      'administer url aliases',
      'create url aliases',
      'bypass node access',
      'access content overview',
    ]);
  }


  /**
   * Create node type to many tests.
   *
   * @param string $type
   *   The type.
   * @param string $type_name
   *   The type name.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The node type.
   */
  protected function createNodeType(string $type, string $type_name) {
    return $this->drupalCreateContentType([
      'name' => $type_name,
      'type' => $type,
    ]);
  }

  /**
   * Grants given user permission to create content of given type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to grant permission to.
   * @param string $content_type_id
   *   Content type ID.
   */
  protected function grantUserPermissionToCreateContentOfType(AccountInterface $account, string $content_type_id) {
    $role_ids = $account->getRoles(TRUE);
    /* @var \Drupal\user\RoleInterface $role */
    $role_id = reset($role_ids);
    $role = Role::load($role_id);
    $role->grantPermission(sprintf('create %s content', $content_type_id));
    $role->grantPermission(sprintf('edit any %s content', $content_type_id));
    $role->grantPermission(sprintf('delete any %s content', $content_type_id));
    $role->grantPermission(sprintf('view %s revisions', $content_type_id));
    $role->save();
  }

  /**
   * Assert url alias widget.
   *
   * @param string $value
   *   The value of path alias field.
   * @param bool $c_to_uc
   *   True is checked to unchecked, otherwise false.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertUrlAliasWidget(string $value, bool $c_to_uc = TRUE) {
    if ($c_to_uc) {
      // Check 'Generate automatic URL alias' checkbox.
      $this->checkCheckbox('edit-path-0-pathauto');
      // The 'Generate automatic URL alias' should be checked.
      $this->assertSession()->checkboxChecked('edit-path-0-pathauto');
      // The value of path alias field should be matches $value and disabled, if the pathauto checked.
      $this->assertFieldDisabledWithValue('edit-path-0-alias', $value);
      // Uncheck 'Generate automatic URL alias' checkbox.
      $this->uncheckCheckbox('edit-path-0-pathauto');
      // The 'Generate automatic URL alias' should be unchecked.
      $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
      // The value of path alias field should be matches $value and editable, if the pathauto unchecked.
      $this->assertFieldEditableWithValue('edit-path-0-alias', $value);
    }
    else {
      // Uncheck 'Generate automatic URL alias' checkbox.
      $this->uncheckCheckbox('edit-path-0-pathauto');
      // The 'Generate automatic URL alias' should be unchecked.
      $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
      // The value of path alias field should be matches $value and editable, if the pathauto unchecked.
      $this->assertFieldEditableWithValue('edit-path-0-alias', $value);
      // Check 'Generate automatic URL alias' checkbox.
      $this->checkCheckbox('edit-path-0-pathauto');
      // The 'Generate automatic URL alias' should be checked.
      $this->assertSession()->checkboxChecked('edit-path-0-pathauto');
      // The value of path alias field should be matches $value and disabled, if the pathauto checked.
      $this->assertFieldDisabledWithValue('edit-path-0-alias', $value);
    }
  }

}
