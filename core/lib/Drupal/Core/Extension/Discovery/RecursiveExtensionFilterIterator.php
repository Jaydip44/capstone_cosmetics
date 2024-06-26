<?php

namespace Drupal\Core\Extension\Discovery;

/**
 * Filters a RecursiveDirectoryIterator to discover extensions.
 *
 * To ensure the best possible performance for extension discovery, this
 * filter implementation hard-codes a range of assumptions about directories
 * in which Drupal extensions may appear and in which not. Every unnecessary
 * subdirectory tree recursion is avoided.
 *
 * The list of globally ignored directory names is defined in the
 * RecursiveExtensionFilterIterator::$skippedFolders property.
 *
 * In addition, all 'config' directories are skipped, unless the directory path
 * ends with 'modules/config', so as to still find the config module provided by
 * Drupal core and still allow that module to be overridden with a custom config
 * module.
 *
 * Lastly, ExtensionDiscovery instructs this filter to additionally skip all
 * 'tests' directories at regular runtime, since just with Drupal core only, the
 * discovery process yields 4x more extensions when tests are not ignored.
 *
 * @see ExtensionDiscovery::scan()
 * @see ExtensionDiscovery::scanDirectory()
 *
 * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterCallback instead.
 *
 * @see https://www.drupal.org/node/3343023
 */
class RecursiveExtensionFilterIterator extends \RecursiveFilterIterator {

  /**
   * List of base extension type directory names to scan.
   *
   * Only these directory names are considered when starting a filesystem
   * recursion in a search path.
   *
   * @var array
   */
  protected $allowedExtensionTypes = [
    'profiles',
    'modules',
    'themes',
  ];

  /**
   * List of directory names to skip when recursing.
   *
   * These directories are globally ignored in the recursive filesystem scan;
   * i.e., extensions (of all types) are not able to use any of these names,
   * because their directory names will be skipped.
   *
   * @var array
   */
  protected $skippedFolders = [
    // Object-oriented code subdirectories.
    'src',
    'lib',
    'vendor',
    // Front-end.
    'assets',
    'css',
    'files',
    'images',
    'js',
    'misc',
    'templates',
    // Legacy subdirectories.
    'includes',
    // Test subdirectories.
    'fixtures',
    // @todo ./tests/Drupal should be ./tests/src/Drupal
    'Drupal',
  ];

  /**
   * Whether to include test directories when recursing.
   *
   * @var bool
   */
  protected $acceptTests = FALSE;

  /**
   * Construct a RecursiveExtensionFilterIterator.
   *
   * @param \RecursiveIterator $iterator
   *   The iterator to filter.
   * @param string[] $skipped_folders
   *   (optional) Add to the list of directories that should be filtered out
   *   during the iteration.
   */
  public function __construct(\RecursiveIterator $iterator, array $skipped_folders = []) {
    @trigger_error('The ' . __NAMESPACE__ . '\RecursiveExtensionFilterIterator is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Extension\Discovery\RecursiveExtensionFilterCallback instead. See https://www.drupal.org/node/3343023', E_USER_DEPRECATED);
    parent::__construct($iterator);
    $this->skippedFolders = array_merge($this->skippedFolders, $skipped_folders);
  }

  /**
   * Controls whether test directories will be scanned.
   *
   * @param bool $flag
   *   Pass FALSE to skip all test directories in the discovery. If TRUE,
   *   extensions in test directories will be discovered and only the global
   *   directory skip list in RecursiveExtensionFilterIterator::$skippedFolders
   *   is applied.
   */
  public function acceptTests($flag = FALSE) {
    $this->acceptTests = $flag;
    if (!$this->acceptTests) {
      $this->skippedFolders[] = 'tests';
    }
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getChildren() {
    $filter = parent::getChildren();
    // Pass on the skipped folders list.
    $filter->skippedFolders = $this->skippedFolders;
    // Pass the $acceptTests flag forward to child iterators.
    $filter->acceptTests($this->acceptTests);
    return $filter;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function accept() {
    $name = $this->current()->getFilename();
    // FilesystemIterator::SKIP_DOTS only skips '.' and '..', but not hidden
    // directories (like '.git').
    if ($name[0] == '.') {
      return FALSE;
    }
    if ($this->current()->isDir()) {
      // If this is a subdirectory of a base search path, only recurse into the
      // fixed list of expected extension type directory names. Required for
      // scanning the top-level/root directory; without this condition, we would
      // recurse into the whole filesystem tree that possibly contains other
      // files aside from Drupal.
      if ($this->current()->getSubPath() == '') {
        return in_array($name, $this->allowedExtensionTypes, TRUE);
      }
      // 'config' directories are special-cased here, because every extension
      // contains one. However, those default configuration directories cannot
      // contain extensions. The directory name cannot be globally skipped,
      // because core happens to have a directory of an actual module that is
      // named 'config'. By explicitly testing for that case, we can skip all
      // other config directories, and at the same time, still allow the core
      // config module to be overridden/replaced in a profile/site directory
      // (whereas it must be located directly in a modules directory).
      if ($name == 'config') {
        return str_ends_with($this->current()->getPathname(), 'modules/config');
      }
      // Accept the directory unless the folder is skipped.
      return !in_array($name, $this->skippedFolders, TRUE);
    }
    else {
      // Only accept extension info files.
      return str_ends_with($name, '.info.yml');
    }
  }

}
