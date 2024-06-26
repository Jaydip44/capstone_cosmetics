<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test of backend chain implementation specifics.
 *
 * @group Cache
 * @coversDefaultClass \Drupal\Core\Cache\BackendChain
 */
class BackendChainImplementationUnitTest extends UnitTestCase {

  /**
   * Chain that will be heavily tested.
   *
   * @var \Drupal\Core\Cache\BackendChain
   */
  protected $chain;

  /**
   * First backend in the chain.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $firstBackend;

  /**
   * Second backend in the chain.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $secondBackend;

  /**
   * Third backend in the chain.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $thirdBackend;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up three memory backends to be used in the chain.
    $time = new Time();
    $this->firstBackend = new MemoryBackend($time);
    $this->secondBackend = new MemoryBackend($time);
    $this->thirdBackend = new MemoryBackend($time);

    // Set an initial fixed dataset for all testing. The next three data
    // collections will test two edge cases (last backend has the data, and
    // first backend has the data) and will test a normal use case (middle
    // backend has the data). We should have a complete unit test with those.
    // Note that in all cases, when the same key is set on more than one
    // backend, the values are voluntarily different, this ensures in which
    // backend we actually fetched the key when doing get calls.

    // Set a key present on all backends (for delete).
    $this->firstBackend->set('t123', 1231);
    $this->secondBackend->set('t123', 1232);
    $this->thirdBackend->set('t123', 1233);

    // Set a key present on the second and the third (for get), those two will
    // be different, this will ensure from where we get the key.
    $this->secondBackend->set('t23', 232);
    $this->thirdBackend->set('t23', 233);

    // Set a key on only the third, we will ensure propagation using this one.
    $this->thirdBackend->set('t3', 33);

    // Create the chain.
    $this->chain = new BackendChain();
    $this->chain
      ->appendBackend($this->firstBackend)
      ->appendBackend($this->secondBackend)
      ->appendBackend($this->thirdBackend);
  }

  /**
   * Tests the get feature.
   */
  public function testGet(): void {
    $cached = $this->chain->get('t123');
    $this->assertNotFalse($cached, 'Got key that is on all backends');
    $this->assertSame(1231, $cached->data, 'Got the key from the backend 1');

    $cached = $this->chain->get('t23');
    $this->assertNotFalse($cached, 'Got key that is on backends 2 and 3');
    $this->assertSame(232, $cached->data, 'Got the key from the backend 2');

    $cached = $this->chain->get('t3');
    $this->assertNotFalse($cached, 'Got key that is on the backend 3');
    $this->assertSame(33, $cached->data, 'Got the key from the backend 3');
  }

  /**
   * Tests the get multiple feature.
   */
  public function testGetMultiple(): void {
    $cids = ['t123', 't23', 't3', 't4'];

    $ret = $this->chain->getMultiple($cids);
    $this->assertSame($ret['t123']->data, 1231, 'Got key 123 and value is from the first backend');
    $this->assertSame($ret['t23']->data, 232, 'Got key 23 and value is from the second backend');
    $this->assertSame($ret['t3']->data, 33, 'Got key 3 and value is from the third backend');
    $this->assertArrayNotHasKey('t4', $ret);

    $this->assertNotContains('t123', $cids, "Existing key 123 has been removed from &\$cids");
    $this->assertNotContains('t23', $cids, "Existing key 23 has been removed from &\$cids");
    $this->assertNotContains('t3', $cids, "Existing key 3 has been removed from &\$cids");
    $this->assertContains('t4', $cids, "Non existing key 4 is still in &\$cids");
  }

  /**
   * Tests that set will propagate.
   */
  public function testSet(): void {
    $this->chain->set('test', 123);

    $cached = $this->firstBackend->get('test');
    $this->assertNotFalse($cached, 'Test key is in the first backend');
    $this->assertSame(123, $cached->data, 'Test key has the right value');

    $cached = $this->secondBackend->get('test');
    $this->assertNotFalse($cached, 'Test key is in the second backend');
    $this->assertSame(123, $cached->data, 'Test key has the right value');

    $cached = $this->thirdBackend->get('test');
    $this->assertNotFalse($cached, 'Test key is in the third backend');
    $this->assertSame(123, $cached->data, 'Test key has the right value');
  }

  /**
   * Tests that delete will propagate.
   */
  public function testDelete(): void {
    $this->chain->set('test', 5);

    $cached = $this->firstBackend->get('test');
    $this->assertNotFalse($cached, 'Test key has been added to the first backend');
    $cached = $this->secondBackend->get('test');
    $this->assertNotFalse($cached, 'Test key has been added to the first backend');
    $cached = $this->thirdBackend->get('test');
    $this->assertNotFalse($cached, 'Test key has been added to the first backend');

    $this->chain->delete('test');

    $cached = $this->firstBackend->get('test');
    $this->assertFalse($cached, 'Test key is removed from the first backend');
    $cached = $this->secondBackend->get('test');
    $this->assertFalse($cached, 'Test key is removed from the second backend');
    $cached = $this->thirdBackend->get('test');
    $this->assertFalse($cached, 'Test key is removed from the third backend');
  }

  /**
   * Ensure get values propagation to previous backends.
   */
  public function testGetHasPropagated(): void {
    $this->chain->get('t23');
    $cached = $this->firstBackend->get('t23');
    $this->assertNotFalse($cached, 'Test 2 has been propagated to the first backend');

    $this->chain->get('t3');
    $cached = $this->firstBackend->get('t3');
    $this->assertNotFalse($cached, 'Test 3 has been propagated to the first backend');
    $cached = $this->secondBackend->get('t3');
    $this->assertNotFalse($cached, 'Test 3 has been propagated to the second backend');
  }

  /**
   * Ensure get multiple values propagation to previous backends.
   */
  public function testGetMultipleHasPropagated(): void {
    $cids = ['t3', 't23'];
    $this->chain->getMultiple($cids);

    $cached = $this->firstBackend->get('t3');
    $this->assertNotFalse($cached, 'Test 3 has been propagated to the first backend');
    $this->assertSame(33, $cached->data, 'And value has been kept');
    $cached = $this->secondBackend->get('t3');
    $this->assertNotFalse($cached, 'Test 3 has been propagated to the second backend');
    $this->assertSame(33, $cached->data, 'And value has been kept');

    $cached = $this->firstBackend->get('t23');
    $this->assertNotFalse($cached, 'Test 2 has been propagated to the first backend');
    $this->assertSame(232, $cached->data, 'And value has been kept');
  }

  /**
   * Tests that the delete all operation is propagated to all chained backends.
   */
  public function testDeleteAllPropagation(): void {
    // Set both expiring and permanent keys.
    $this->chain->set('test1', 1, Cache::PERMANENT);
    $this->chain->set('test2', 3, time() + 1000);
    $this->chain->deleteAll();

    $this->assertFalse($this->firstBackend->get('test1'), 'First key has been deleted in first backend.');
    $this->assertFalse($this->firstBackend->get('test2'), 'Second key has been deleted in first backend.');
    $this->assertFalse($this->secondBackend->get('test1'), 'First key has been deleted in second backend.');
    $this->assertFalse($this->secondBackend->get('test2'), 'Second key has been deleted in second backend.');
    $this->assertFalse($this->thirdBackend->get('test1'), 'First key has been deleted in third backend.');
    $this->assertFalse($this->thirdBackend->get('test2'), 'Second key has been deleted in third backend.');
  }

  /**
   * Tests that the delete tags operation is propagated to all chained backends.
   */
  public function testDeleteTagsPropagation(): void {
    // Create two cache entries with the same tag and tag value.
    $this->chain->set('test_cid_clear1', 'foo', Cache::PERMANENT, ['test_tag:2']);
    $this->chain->set('test_cid_clear2', 'foo', Cache::PERMANENT, ['test_tag:2']);
    $this->assertNotFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2'),
      'Two cache items were created in all backends.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    $this->chain->invalidateTags(['test_tag:2']);
    $this->assertFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2'),
      'Two caches removed from all backends after clearing a cache tag.');

    // Create two cache entries with the same tag and an array tag value.
    $this->chain->set('test_cid_clear1', 'foo', Cache::PERMANENT, ['test_tag:1']);
    $this->chain->set('test_cid_clear2', 'foo', Cache::PERMANENT, ['test_tag:1']);
    $this->assertNotFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2'),
      'Two cache items were created in all backends.');

    // Invalidate test_tag of value 1. This should invalidate both entries.
    $this->chain->invalidateTags(['test_tag:1']);
    $this->assertFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2'),
      'Two caches removed from all backends after clearing a cache tag.');

    // Create three cache entries with a mix of tags and tag values.
    $this->chain->set('test_cid_clear1', 'foo', Cache::PERMANENT, ['test_tag:1']);
    $this->chain->set('test_cid_clear2', 'foo', Cache::PERMANENT, ['test_tag:2']);
    $this->chain->set('test_cid_clear3', 'foo', Cache::PERMANENT, ['test_tag_foo:3']);
    $this->assertNotFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->firstBackend->get('test_cid_clear3')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear3')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear3'),
      'Three cached items were created in all backends.');

    $this->chain->invalidateTags(['test_tag_foo:3']);
    $this->assertNotFalse($this->firstBackend->get('test_cid_clear1')
      && $this->firstBackend->get('test_cid_clear2')
      && $this->secondBackend->get('test_cid_clear1')
      && $this->secondBackend->get('test_cid_clear2')
      && $this->thirdBackend->get('test_cid_clear1')
      && $this->thirdBackend->get('test_cid_clear2'),
      'Cached items not matching the tag were not cleared from any of the backends.');

    $this->assertFalse($this->firstBackend->get('test_cid_clear3')
      && $this->secondBackend->get('test_cid_clear3')
      && $this->thirdBackend->get('test_cid_clear3'),
      'Cached item matching the tag was removed from all backends.');
  }

  /**
   * Tests that removing bin propagates to all backends.
   */
  public function testRemoveBin(): void {
    $chain = new BackendChain();
    for ($i = 0; $i < 3; $i++) {
      $backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
      $backend->expects($this->once())->method('removeBin');
      $chain->appendBackend($backend);
    }

    $chain->removeBin();
  }

}
