<?php

namespace Drupal\Tests\token_alias\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\pathauto\PathautoState;
use Drupal\system\Entity\Menu;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Class TokenAliasNodeWebTest.
 *
 * @package Drupal\Tests\token_alias\Functional
 */
class TokenAliasNodeWebTest extends TokenAliasTestBase {

  use PathautoTestHelperTrait;

  protected $nodeType;

  /**
   * The pattern.
   *
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $pattern;

  /**
   * @var \Drupal\system\MenuInterface
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    self::$modules = array_merge(parent::$modules, ['menu_ui']);

    parent::setUp();

    // Create node type.
    $this->nodeType = $this->createNodeType('page', 'Basic page');

    $this->grantUserPermissionToCreateContentOfType($this->adminUser, $this->nodeType->id());

    $this->drupalLogin($this->adminUser);

    // Create alias pattern.
    $this->pattern = $this->createPattern('node', '/content/[node:title]');

    // Create menu type.
    $this->menu = Menu::create([
      'id' => 'main-menu',
      'label' => 'Main menu',
      'description' => 'The <em>Main</em> menu is used on many sites to show the major sections of the site, often in a top navigation bar.',
    ]);
    $this->menu->save();

    // Setup node type menu options.
    $this->nodeType->setThirdPartySetting('menu_ui', 'available_menus', ['main', $this->menu->id()]);
    $this->nodeType->setThirdPartySetting('menu_ui', 'parent', $this->menu->id() . ':');
    $this->nodeType->save();
  }

  /**
   * Tests adding nodes with different settings.
   *
   * @scenario
   * - On node add form
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - fill the token alias in alias field
   *   - save the node
   *   - if the token is valid, the token should be replaced
   */
  public function testNodeAdding() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet("node/add/{$this->nodeType->id()}");
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');

    // Create a node by saving the node form.
    $title = ' Testing: node title [';
    $token_alias = '/[node:menu-link:parent:url:path]/[node:title].html';
    $automatic_alias = '/testing-node-title.html';
    $this->drupalPostForm(NULL, [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // Check whether the automatic alias actually works.
    $this->drupalGet(ltrim($automatic_alias, '/'));
    // Node accessible through automatic alias.
    $this->assertSession()->pageTextContains($title);

    // Check whether the automatic alias should be exists.
    $aliases = $this->aliasStorage->loadByProperties([
      'alias' => $automatic_alias,
    ]);
    $this->assertTrue(1 === count($aliases), 'Automatic alias exists');

    // Check whether the token alias should be no-exists.
    $aliases = $this->aliasStorage->loadByProperties([
      'alias' => $token_alias,
    ]);
    $this->assertTrue(0 === count($aliases), 'Token alias not exists.');
  }

  /**
   * Tests that if the alias token was empty, should not be token replace.
   *
   * @scenario
   * - On node add form
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - fill the token alias with token
   *   - save the node
   *   - if the token is invalid, the token should not be replaced, and should not be removed, and show warning message
   * @scenario
   * - On node edit form of an existing node with Token Alias that the token not replaced automatically.
   *   - fill node alias is /[node:menu-link:parent:url:path]
   *   - fill the menu settings with parent menu that menu link is internal link with 404
   *   - save the node
   *   - the token should not be replaced, and should not be removed, and show warning message
   * @scenario
   * - On node edit form of an existing node with Token Alias that the token not replaced automatically.
   *   - fill node alias is /[node:menu-link:parent:url:path]
   *   - fill the menu settings with parent menu that menu link is internal link with 200
   *   - save the node
   *   - the token should be replaced
   * @scenario
   * - On node edit form of an existing node with Token Alias that the token not replaced automatically.
   *   - fill node alias is /[node:menu-link:parent:url:path]
   *   - fill the menu settings with parent menu that menu link is external
   *   - save the node
   *   - the token should not be replaced, and should not be removed, and show warning message
   * @scenario
   * - On node edit form of an existing node with Token Alias that the token not replaced automatically.
   *   - fill node alias with token
   *   - fill some node field values, to make the toke valid
   *   - save the node
   *   - the token should be replaced
   */
  public function testAliasTokenEmpty() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet("node/add/{$this->nodeType->id()}");
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');

    // @scenario 1:
    $token_alias = '/[node:menu-link:parent:url:path]';
    $automatic_alias = $token_alias;
    // Create a node by saving the node form.
    $title = $this->randomMachineName();
    $this->drupalPostForm(NULL, [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Warning message visible in the page after post.
    $this->assertSession()->pageTextContains('The tokens\' value all empty in current alias, do not replace. ( Tokens: [node:menu-link:parent:url:path] )');

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // Check whether the automatic alias actually works.
    $this->drupalGet(ltrim($automatic_alias, '/'));
    // Node accessible through automatic alias.
    $this->assertSession()->pageTextContains($title);

    // @scenario: 2:
    $automatic_alias = $token_alias;
    // Create menu link with 404 internal link.
    $parent_menu_link = MenuLinkContent::create([
      'link' => ['uri' => 'internal:/page-not-found'],
      'title' => $this->randomMachineName(),
      'menu_name' => $this->menu->id(),
    ]);
    $parent_menu_link->save();

    // Edit node to file menu settings.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->drupalPostForm(NULL, [
      'path[0][alias]' => $token_alias,
      'menu[enabled]' => 1,
      'menu[title]' => $this->randomMachineName(),
      'menu[menu_parent]' => $this->menu->id() . ':' . $parent_menu_link->getPluginId(),
    ], $this->t('Save'));

    // Warning message visible in the page after post.
    $this->assertSession()->pageTextContains('The tokens\' value all empty in current alias, do not replace. ( Tokens: [node:menu-link:parent:url:path] )');

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // @scenario 3:
    $automatic_alias = "/admin/structure/menu-0";
    // Create menu link with 200 internal link.
    $parent_menu_link->link->first()->uri = 'internal:/admin/structure/menu';
    $parent_menu_link->save();

    // Edit node to file menu settings.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->drupalPostForm(NULL, [
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Warning message visible in the page after post.
    $this->assertSession()->pageTextNotContains('The tokens\' value all empty in current alias, do not replace. ( Tokens: [node:menu-link:parent:url:path] )');

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // @scenario 4:
    $automatic_alias = $token_alias;
    // Create menu link with external link.
    $parent_menu_link->link->first()->uri = 'https://www.baidu.com';
    $parent_menu_link->save();

    // Edit node to file menu settings.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->drupalPostForm(NULL, [
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Warning message visible in the page after post.
    $this->assertSession()->pageTextContains('The tokens\' value all empty in current alias, do not replace. ( Tokens: [node:menu-link:parent:url:path] )');

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias);

    // @scenario 5:
    // Create random node by node type.
    $parent = $this->drupalCreateNode(['type' => $this->nodeType->id()]);
    $parent_automatic_alias = "/content/{$parent->getTitle()}";
    // Check whether the automatic alias actually works.
    $this->drupalGet(ltrim($parent_automatic_alias, '/'));
    // Node accessible through automatic alias.
    $this->assertSession()->pageTextContains($parent->getTitle());

    // Create menu link.
    $parent_menu_link->link->first()->uri = 'entity:node/' . $parent->id();
    $parent_menu_link->save();

    // Edit node to fill menu settings.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->drupalPostForm(NULL, [
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Token should be replace.
    $automatic_alias = "{$parent_automatic_alias}-0";

    // Warning message invisible in the page after post.
    $this->assertSession()->pageTextNotContains('The tokens\' value all empty in current alias, do not replace. ( Tokens: [node:menu-link:parent:url:path] )');

    // Check whether the automatic alias actually works.
    $this->drupalGet(ltrim($automatic_alias, '/'));
    // Node accessible through automatic alias.
    $this->assertSession()->pageTextContains($title);

    $this->aliasStorage->resetCache();

    // Check just two alias.
    $aliases = $this->aliasStorage->loadMultiple();
    $this->assertTrue(2 === count($aliases), 'Two aliases');

    // Check whether the automatic alias should be exists.
    $aliases = $this->aliasStorage->loadByProperties([
      'alias' => $automatic_alias,
    ]);
    $this->assertTrue(1 === count($aliases), 'Automatic alias exists');

    // Check whether the token alias should be no-exists.
    $aliases = $this->aliasStorage->loadByProperties([
      'alias' => $token_alias,
    ]);
    $this->assertTrue(0 === count($aliases), 'Token alias not exists.');
  }

  /**
   * Tests the token alias should not generate duplicate alias.
   *
   * @scenario
   * - On node add form
   *   - fill node title and token alias
   *   - save the node
   *   - create second node with same above values
   *   - the aliases that two nodes should be difference
   * @scenario
   * - On node edit form of the title and alias different with the scenario 1 nodes
   *   - fill the title and alias same with the scenario 1 nodes above
   *   - save the node
   *   - the alias should be different with the scenario 1 nodes
   */
  public function testDuplicateAliases() {
    $token_alias = '/[node:title]';

    $title = 'node1';
    $automatic_alias = ['/node1', '/node1-0'];

    // @scenario 1:
    // Create first node.
    $this->drupalPostForm("node/add/{$this->nodeType->id()}", [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Create second node.
    $this->drupalPostForm("node/add/{$this->nodeType->id()}", [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Load nodes.
    $nodes = array_values($this->nodeStorage->loadMultiple());
    $this->assertTrue(2 === count($nodes), 'Nodes creation correctly.');

    foreach ($nodes as $index => $node) {
      // Look for alias generated in the form.
      $this->drupalGet("node/{$node->id()}/edit");
      $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
      // Generated alias visible in the path alias field.
      $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias[$index]);
    }

    // Load aliases.
    $aliases = array_values($this->aliasStorage->loadMultiple());
    $this->assertTrue(2 === count($aliases), 'Aliases creation correctly');

    foreach ($aliases as $index => $alias) {
      $this->assertTrue($alias->getAlias() === $automatic_alias[$index], $this->t('Alias %alias correct', ['%alias' => $alias->getAlias()]));
    }

    // @scenario 2:
    // Create third node.
    $title2 = 'node2';
    $token_alias_2 = '/[node:title].html';
    $automatic_alias_2 = '/node2.html';
    array_push($automatic_alias, '/node1-1');

    $this->drupalPostForm("node/add/{$this->nodeType->id()}", [
      'title[0][value]' => $title2,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias_2,
    ], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title2);

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    // Generated alias visible in the path alias field.
    $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias_2);

    $this->drupalPostForm("node/{$node->id()}/edit", [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));

    // Load nodes.
    $nodes = array_values($this->nodeStorage->loadMultiple());
    $this->assertTrue(3 === count($nodes), 'Nodes creation correctly.');

    foreach ($nodes as $index => $node) {
      // Look for alias generated in the form.
      $this->drupalGet("node/{$node->id()}/edit");
      $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
      // Generated alias visible in the path alias field.
      $this->assertSession()->fieldValueEquals('path[0][alias]', $automatic_alias[$index]);
    }

    // Load aliases.
    $aliases = array_values($this->aliasStorage->loadMultiple());
    $this->assertTrue(3 === count($aliases), 'Aliases creation correctly');

    foreach ($aliases as $index => $alias) {
      $this->assertTrue($alias->getAlias() === $automatic_alias[$index], $this->t('Alias %alias correct', ['%alias' => $alias->getAlias()]));
    }
  }

}
