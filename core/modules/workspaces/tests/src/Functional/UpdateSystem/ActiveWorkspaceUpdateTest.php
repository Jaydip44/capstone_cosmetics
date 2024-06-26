<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional\UpdateSystem;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests that there is no active workspace during database updates.
 *
 * @group workspaces
 * @group Update
 */
class ActiveWorkspaceUpdateTest extends BrowserTestBase {

  use UpdatePathTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser([], ['view any workspace']);
    $this->container->get('module_installer')->install(['workspace_update_test']);
    $this->rebuildContainer();

    // Ensure the workspace_update_test_post_update_check_active_workspace()
    // update runs.
    $existing_updates = \Drupal::keyValue('post_update')->get('existing_updates', []);
    $index = array_search('workspace_update_test_post_update_check_active_workspace', $existing_updates);
    unset($existing_updates[$index]);
    \Drupal::keyValue('post_update')->set('existing_updates', $existing_updates);

    // Create a valid workspace that can be used for testing.
    Workspace::create(['id' => 'test', 'label' => 'Test'])->save();
  }

  /**
   * Tests that there is no active workspace during database updates.
   */
  public function testActiveWorkspaceDuringUpdate(): void {
    /** @var \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspaces.manager');

    // Check that we have an active workspace before running the updates.
    $this->assertTrue($workspace_manager->hasActiveWorkspace());
    $this->assertEquals('test', $workspace_manager->getActiveWorkspace()->id());

    $this->runUpdates();

    // Check that we didn't have an active workspace while running the updates.
    // @see workspace_update_test_post_update_check_active_workspace()
    $this->assertFalse(\Drupal::state()->get('workspace_update_test.has_active_workspace'));

    // Check that we have an active workspace after running the updates.
    $workspace_manager = \Drupal::service('workspaces.manager');
    $this->assertTrue($workspace_manager->hasActiveWorkspace());
    $this->assertEquals('test', $workspace_manager->getActiveWorkspace()->id());
  }

}
