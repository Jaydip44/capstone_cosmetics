<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\ConfigDirectoryNotDefinedException;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\FileStorageFactory;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Config\FileStorageFactory
 * @group config
 */
class FileStorageFactoryTest extends KernelTestBase {

  /**
   * @covers ::getSync
   */
  public function testGetSync(): void {

    // Write some random data to the sync storage.
    $name = $this->randomMachineName();
    $data = (array) $this->getRandomGenerator()->object();
    $storage = new FileStorage(Settings::get('config_sync_directory'));
    $storage->write($name, $data);

    // Get the sync storage and read from it.
    $sync = FileStorageFactory::getSync();
    $this->assertEquals($data, $sync->read($name));

    // Unset the sync directory setting.
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    unset($settings['config_sync_directory']);
    new Settings($settings);

    // On an empty settings there is an exception thrown.
    $this->expectException(ConfigDirectoryNotDefinedException::class);
    $this->expectExceptionMessage('The config sync directory is not defined in $settings["config_sync_directory"]');
    FileStorageFactory::getSync();
  }

}
