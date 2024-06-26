<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Regression test for https://www.drupal.org/node/2807263.
 *
 * When a Vocabulary entity is unserialized before the modules have been loaded
 * (which happens in the KernelPreHandle Stack middleware), then the constants
 * that the Vocabulary entity uses are not yet available because they are set in
 * taxonomy.module. This means that for example the PageCache middleware cannot
 * load any cached Vocabulary entity, because unserialization will fail.
 *
 * @group taxonomy
 */
class VocabularySerializationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'vocabulary_serialization_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    Vocabulary::create(['vid' => 'test', 'name' => 'Test'])->save();
  }

  public function testSerialization(): void {
    $this->drupalGet('/vocabulary_serialization_test/test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('this is the output', $this->getSession()->getPage()->getContent());
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');

    $this->drupalGet('/vocabulary_serialization_test/test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSame('this is the output', $this->getSession()->getPage()->getContent());
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
  }

}
