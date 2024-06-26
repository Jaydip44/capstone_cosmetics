<?php

declare(strict_types=1);

namespace Drupal\Tests\Composer\Generator;

use Drupal\Composer\Generator\Builder\DrupalCoreRecommendedBuilder;
use Drupal\Composer\Generator\Builder\DrupalDevDependenciesBuilder;
use Drupal\Composer\Generator\Builder\DrupalPinnedDevDependenciesBuilder;
use PHPUnit\Framework\TestCase;
use Drupal\Composer\Composer;

/**
 * Test DrupalCoreRecommendedBuilder.
 *
 * @group Metapackage
 */
class BuilderTest extends TestCase {

  /**
   * Provides test data for testBuilder.
   */
  public static function builderTestData() {
    return [
      [
        DrupalCoreRecommendedBuilder::class,
        [
          'name' => 'drupal/core-recommended',
          'type' => 'metapackage',
          'description' => 'Core and its dependencies with known-compatible minor versions. Require this project INSTEAD OF drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'drupal/core' => Composer::drupalVersionBranch(),
            'symfony/polyfill-ctype' => '~v1.12.0',
            'symfony/yaml' => '~v3.4.32',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-strict' => '*',
          ],
        ],
      ],

      [
        DrupalDevDependenciesBuilder::class,
        [
          'name' => 'drupal/core-dev',
          'type' => 'metapackage',
          'description' => 'require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'behat/mink' => '^1.8',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-require-dev' => '*',
          ],
        ],
      ],

      [
        DrupalPinnedDevDependenciesBuilder::class,
        [
          'name' => 'drupal/core-dev-pinned',
          'type' => 'metapackage',
          'description' => 'Pinned require-dev dependencies from drupal/drupal; use in addition to drupal/core-recommended to run tests from drupal/core.',
          'license' => 'GPL-2.0-or-later',
          'require' =>
          [
            'drupal/core' => Composer::drupalVersionBranch(),
            'behat/mink' => 'v1.8.0',
            'symfony/css-selector' => 'v4.3.5',
          ],
          'conflict' =>
          [
            'webflo/drupal-core-require-dev' => '*',
          ],
        ],
      ],

    ];
  }

  /**
   * Tests all of the various kinds of builders.
   *
   * @dataProvider builderTestData
   */
  public function testBuilder($builderClass, $expected): void {
    $fixtures = new Fixtures();
    $drupalCoreInfo = $fixtures->drupalCoreComposerFixture();

    $builder = new $builderClass($drupalCoreInfo);
    $generatedJson = $builder->getPackage();

    $this->assertEquals($expected, $generatedJson);
  }

}
