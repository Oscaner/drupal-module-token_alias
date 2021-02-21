<?php

namespace Drupal\Tests\token_alias\Functional;

use Drupal\Tests\pathauto\Functional\PathautoNodeWebTest;

/**
 * Class DefaultPathautoNodeWebTest.
 *
 * @package Drupal\Tests\token_alias\Functional
 */
class DefaultPathautoNodeWebTest extends PathautoNodeWebTest {

  /**
   * {@inheritdoc}
   */
  function setUp() {
    self::$modules = array_merge(parent::$modules, ['token_alias']);
    parent::setUp();
  }

}
