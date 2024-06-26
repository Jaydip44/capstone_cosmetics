<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod
 * @group PageCache
 */
class CommandLineOrUnsafeMethodTest extends UnitTestCase {

  /**
   * The request policy under test.
   *
   * @var \Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $policy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Note that it is necessary to partially mock the class under test in
    // order to disable the isCli-check.
    $this->policy = $this->getMockBuilder('Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod')
      ->onlyMethods(['isCli'])
      ->getMock();
  }

  /**
   * Asserts that check() returns DENY for unsafe HTTP methods.
   *
   * @dataProvider providerTestHttpMethod
   * @covers ::check
   */
  public function testHttpMethod($expected_result, $method): void {
    $this->policy->expects($this->once())
      ->method('isCli')
      ->willReturn(FALSE);

    $request = Request::create('/', $method);
    $actual_result = $this->policy->check($request);
    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Provides test data and expected results for the HTTP method test.
   *
   * @return array
   *   Test data and expected results.
   */
  public static function providerTestHttpMethod() {
    return [
      [NULL, 'GET'],
      [NULL, 'HEAD'],
      [RequestPolicyInterface::DENY, 'POST'],
      [RequestPolicyInterface::DENY, 'PUT'],
      [RequestPolicyInterface::DENY, 'DELETE'],
      [RequestPolicyInterface::DENY, 'OPTIONS'],
      [RequestPolicyInterface::DENY, 'TRACE'],
      [RequestPolicyInterface::DENY, 'CONNECT'],
    ];
  }

  /**
   * Asserts that check() returns DENY if running from the command line.
   *
   * @covers ::check
   */
  public function testIsCli(): void {
    $this->policy->expects($this->once())
      ->method('isCli')
      ->willReturn(TRUE);

    $request = Request::create('/', 'GET');
    $actual_result = $this->policy->check($request);
    $this->assertSame(RequestPolicyInterface::DENY, $actual_result);
  }

}
