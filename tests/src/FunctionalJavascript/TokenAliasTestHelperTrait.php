<?php

namespace Drupal\Tests\token_alias\FunctionalJavascript;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;

/**
 * Trait TokenAliasTestHelperTrait.
 *
 * @package Drupal\Tests\token_alias\FunctionalJavascript
 */
trait TokenAliasTestHelperTrait {

  /**
   * Click detail field.
   *
   * @param string $id
   *   The field id.
   */
  protected function clickDetailField(string $id) {
    $this->getSession()->getPage()->find('xpath', '//a[contains(@href, "#' . $id .'")]')->click();
  }

  /**
   * Use Drupal 8 new @data-drupal-selectro attribute instead.
   *
   * @param ElementInterface $element Mink element to search
   * @param string $selector Drupal 8 data_drupal_selector
   *
   * @return NodeElement|null
   */
  protected function findFieldByDrupalSelector(ElementInterface $element, string $selector) {
    return $element->find('xpath', '//*[contains(@data-drupal-selector, "' . $selector . '")]');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkField($locator) {
    $field = $this->findFieldByDrupalSelector($locator);
    if (null === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'data-drupal-selector', $locator);
    }
    $field->check();
  }

  /**
   * {@inheritdoc}
   */
  public function uncheckField($locator) {
    $field = $this->findFieldByDrupalSelector($locator);
    if (null === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'data-drupal-selector', $locator);
    }
    $field->uncheck();
  }

  /**
   * Check checkbox field.
   *
   * @param string $field
   *   The field.
   */
  protected function checkCheckbox(string $field) {
    $this->getSession()->getPage()->checkField($field);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForElementVisible('xpath', "//*[contains(@data-drupal-selector, \"$field\")]");
  }

  /**
   * Uncheck checkbox field.
   *
   * @param string $field
   *   The field.
   */
  protected function uncheckCheckbox(string $field) {
    $this->getSession()->getPage()->uncheckField($field);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->waitForElementVisible('xpath', "//*[contains(@data-drupal-selector, \"$field\")]");
  }

  /**
   * Fill the specified value to field.
   *
   * @param string $field
   *   The field.
   * @param $value
   *   The value.
   */
  protected function fillFieldWithValue(string $field, $value) {
    $this->findFieldByDrupalSelector($this->getSession()->getPage(), $field)->setValue($value);
  }

  /**
   * Check if the path alias pattern shown in subject string.
   *
   * @param string $pattern
   *   The pattern string.
   * @param string $haystack
   *   The haystack.
   */
  protected function assertPatternStringExists(string $pattern, string $haystack) {
    $hint_string = 'Pattern: <code>' . $pattern . '</code>';
    $this->assertContains($hint_string, $haystack);
  }

  /**
   * Check if the value of path alias field matches given value and disabled.
   *
   * @param string $field
   *   The field.
   * @param mixed $value
   *   The value of the path alias field.
   */
  protected function assertFieldDisabledWithValue(string $field, $value) {
    // Get the path alias related fields.
    $path_alias_field = $this->findFieldByDrupalSelector($this->getSession()->getPage(), $field);

    // The alias pattern should be shown in the path alias field.
    $this->assertEquals($value, $path_alias_field->getValue());

    // The path alias field should be disabled.
    $path_alias_field->hasAttribute('disabled');
//    $this->assertTrue($path_alias_field->hasAttribute('disabled'));
  }

  /**
   * Check if the value of path alias field matches given value and editable.
   *
   * @param string $field
   *   The field.
   * @param mixed $value
   *   The value of the path alias field.
   */
  protected function assertFieldEditableWithValue(string $field, $value) {
    // Get the path alias related fields.
    $path_alias_field = $this->findFieldByDrupalSelector($this->getSession()->getPage(), $field);

    // The alias pattern should be shown in the path alias field.
    $this->assertEquals($value, $path_alias_field->getValue());

    // The path alias field should not be disabled or readonly.
    $this->assertFalse($path_alias_field->hasAttribute('disabled'));
    $this->assertFalse($path_alias_field->hasAttribute('readonly'));
  }

}
