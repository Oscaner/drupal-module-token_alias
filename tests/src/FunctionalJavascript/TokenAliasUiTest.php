<?php

namespace Drupal\Tests\token_alias\FunctionalJavascript;

use Drupal\pathauto\PathautoState;
use Drupal\Tests\pathauto\Functional\PathautoTestHelperTrait;

/**
 * Class TokenAliasUiTest.
 *
 * @package Drupal\Tests\token_alias\FunctionalJavascript
 */
class TokenAliasUiTest extends TokenAliasTestBase {

  use PathautoTestHelperTrait;

  /**
   * The pattern.
   *
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $pattern;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createNodeType('page', 'Basic page');

    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'page');

    $this->drupalLogin($this->adminUser);

    $this->pattern = $this->createPattern('node', '/content/[node:title]');
  }

  /**
   * Tests adding nodes with different settings.
   *
   * @scenario
   * - On node add form
   *   - 'Generate automatic URL alias' checkbox is checked
   *   - the alias pattern presents in alias field, disabled
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the alias pattern presents in alias field, editable
   * @scenario
   * - On node add form
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - fill alias in alias field
   *   - check 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   */
  public function testNodeAdding() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet('node/add/page');
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');

    // The alias pattern should present in the field description.
    $this->assertPatternStringExists(
      $this->pattern->getPattern(),
      $this->getSession()->getPage()->findById('edit-path-0-pathauto--description')->getHtml()
    );

    // Unfold url alias detail field.
    $this->clickDetailField('edit-path-0');

    // @scenario 1:
    // Assert Url Alias widget by alias pattern.
    $this->assertUrlAliasWidget($this->pattern->getPattern());

    // Fill the specified value to path alias field.
    $alias = '/test/automatic-title.html';
    $this->fillFieldWithValue('edit-path-0-alias', $alias);
    $this->checkCheckbox('edit-path-0-pathauto');

    // @scenario 2:
    // Assert Url Alias widget by alias.
    $this->assertUrlAliasWidget($alias);
  }

  /**
   * Tests editing nodes with different settings.
   *
   * @scenario
   * - On node edit form of an existing node with URL alias already generated automatically.
   *   - 'Generate automatic URL alias' checkbox is checked
   *   - the alias presents in alias field, disabled
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, editable
   * @scenario
   * - On node edit form of an existing node with URL alias already generated automatically.
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - Remove alias in alias field
   *   - check 'Generate automatic URL alias' checkbox
   *   - the empty string presents in alias field, no changed
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the empty string presents in alias field, no changed
   * @scenario
   * - On node edit form of an existing node with URL alias already generated automatically.
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - fill alias in alias field
   *   - check 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   * @scenario
   * - On node edit form of an existing node with URL alias already generated manually.
   *   - 'Generate automatic URL alias' checkbox is unchecked
   *   - the alias presents in alias field, editable
   *   - check 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, disabled
   * @scenario
   * - On node edit form of an existing node with URL alias already generated manually.
   *   - Remove alias in alias field
   *   - check 'Generate automatic URL alias' checkbox
   *   - the empty string presents in alias field, no changed
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the empty string presents in alias field, no changed
   * @scenario
   * - On node edit form of an existing node with URL alias already generated manually.
   *   - fill alias in alias field
   *   - check 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the alias presents in alias field, no changed
   */
  public function testNodeEditing() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet('node/add/page');
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');

    // Create a node by saving the node form.
    $title = ' Testing: node title [';
    $automatic_alias = '/content/testing-node-title';
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");

    // Unfold url alias detail field.
    $this->clickDetailField('edit-path-0');

    // @scenario 1:
    // Asset Url Alias widget by automatic alias.
    $this->assertUrlAliasWidget($automatic_alias);

    // Fill the empty value to path alias field.
    $input_alias = '';
    $this->uncheckCheckbox('edit-path-0-pathauto');
    $this->fillFieldWithValue('edit-path-0-alias', $input_alias);

    // @scenario 2:
    // Asset Url Alias widget by input alias.
    $this->assertUrlAliasWidget($input_alias, FALSE);

    // Fill the empty value to path alias field.
    $input_alias = '/abc';
    $this->uncheckCheckbox('edit-path-0-pathauto');
    $this->fillFieldWithValue('edit-path-0-alias', $input_alias);

    // @scenario 3:
    // Asset Url Alias widget by input alias.
    $this->assertUrlAliasWidget($input_alias, FALSE);

    // Create a node by saving the node form.
    $title = ' Testing: node title [ 2';
    $token_alias = '/[node:title].html';
    $automatic_alias = '/testing-node-title-2.html';
    $this->drupalPostForm(NULL, [
      'title[0][value]' => $title,
      'path[0][pathauto]' => PathautoState::SKIP,
      'path[0][alias]' => $token_alias,
    ], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");

    // Unfold url alias detail field.
    $this->clickDetailField('edit-path-0');

    // @scenario 4:
    // Asset Url Alias widget by automatic alias.
    $this->assertUrlAliasWidget($automatic_alias, FALSE);

    // Fill the empty value to path alias field.
    $input_alias = '';
    $this->uncheckCheckbox('edit-path-0-pathauto');
    $this->fillFieldWithValue('edit-path-0-alias', $input_alias);

    // @scenario 5:
    // Asset Url Alias widget by input alias.
    $this->assertUrlAliasWidget($input_alias, FALSE);

    // Fill the empty value to path alias field.
    $input_alias = '/abc';
    $this->uncheckCheckbox('edit-path-0-pathauto');
    $this->fillFieldWithValue('edit-path-0-alias', $input_alias);

    // @scenario 6:
    // Asset Url Alias widget by input alias.
    $this->assertUrlAliasWidget($input_alias, FALSE);
  }

  /**
   * Tests editing nodes without alias.
   *
   * @scenario
   * - On node edit form of an existing node without URL alias.
   *   - 'Generate automatic URL alias' checkbox is unchecked
   *   - the empty string presents in alias field, editable
   *   - check 'Generate automatic URL alias' checkbox
   *   - the token alias pattern presents in alias field, disabled
   *   - uncheck 'Generate automatic URL alias' checkbox
   *   - the token alias pattern presents in alias field, editable
   */
  public function testNodeEditingWithoutAlias() {
    // Ensure that the Pathauto checkbox is checked by default on the node add
    // form.
    $this->drupalGet('node/add/page');
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');

    // Unfold url alias detail field.
    $this->clickDetailField('edit-path-0');

    // Create a node by saving the node form.
    $title = $this->randomMachineName();
    $this->uncheckCheckbox('edit-path-0-pathauto');
    $this->fillFieldWithValue('edit-path-0-alias', '');
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], $this->t('Save'));
    $node = $this->drupalGetNodeByTitle($title);

    // Assert aliases count.
    $aliases = $this->container->get('entity_type.manager')->getStorage('path_alias')->loadMultiple();
    $this->assertEquals(0, count($aliases), 'Alias no created.');

    // Look for alias generated in the form.
    $this->drupalGet("node/{$node->id()}/edit");

    // Unfold url alias detail field.
    $this->clickDetailField('edit-path-0');

    // The 'Generate automatic URL alias' should be unchecked.
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    $this->assertFieldEditableWithValue('edit-path-0-alias', '');

    // Check 'Generate automatic URL alias' checkbox.
    $this->checkCheckbox('edit-path-0-pathauto');

    // The 'Generate automatic URL alias' should be checked.
    $this->assertSession()->checkboxChecked('edit-path-0-pathauto');
    $this->assertFieldDisabledWithValue('edit-path-0-alias', '');

    // Uncheck 'Generate automatic URL alias' checkbox.
    $this->uncheckCheckbox('edit-path-0-pathauto');

    // The 'Generate automatic URL alias' should be unchecked.
    $this->assertSession()->checkboxNotChecked('edit-path-0-pathauto');
    $this->assertFieldEditableWithValue('edit-path-0-alias', '');
  }

}
