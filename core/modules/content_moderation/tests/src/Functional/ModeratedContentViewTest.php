<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * Tests moderated content administration page functionality.
 *
 * @group content_moderation
 */
class ModeratedContentViewTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'node',
    'views',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page'])->save();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article'])->save();
    $this->drupalCreateContentType(['type' => 'unmoderated_type', 'name' => 'Unmoderated type'])->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view any unpublished content',
      'administer nodes',
      'bypass node access',
    ]);
  }

  /**
   * Tests the moderated content page.
   */
  public function testModeratedContentPage(): void {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->adminUser);

    // Use an explicit changed time to ensure the expected order in the content
    // admin listing. We want these to appear in the table in the same order as
    // they appear in the following code, and the 'moderated_content' view has a
    // table style configuration with a default sort on the 'changed' field
    // descending.
    $time = \Drupal::time()->getRequestTime();
    $excluded_nodes['published_page'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--, 'moderation_state' => 'published']);
    $excluded_nodes['published_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--, 'moderation_state' => 'published']);

    $excluded_nodes['unmoderated_type'] = $this->drupalCreateNode(['type' => 'unmoderated_type', 'changed' => $time--]);
    $excluded_nodes['unmoderated_type']->setNewRevision(TRUE);
    $excluded_nodes['unmoderated_type']->isDefaultRevision(FALSE);
    $excluded_nodes['unmoderated_type']->changed->value = $time--;
    $excluded_nodes['unmoderated_type']->save();

    $nodes['published_then_draft_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--, 'moderation_state' => 'published', 'title' => 'first article - published']);
    $nodes['published_then_draft_article']->setNewRevision(TRUE);
    $nodes['published_then_draft_article']->setTitle('first article - draft');
    $nodes['published_then_draft_article']->moderation_state->value = 'draft';
    $nodes['published_then_draft_article']->changed->value = $time--;
    $nodes['published_then_draft_article']->save();

    $nodes['published_then_archived_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--, 'moderation_state' => 'published']);
    $nodes['published_then_archived_article']->setNewRevision(TRUE);
    $nodes['published_then_archived_article']->moderation_state->value = 'archived';
    $nodes['published_then_archived_article']->changed->value = $time--;
    $nodes['published_then_archived_article']->save();

    $nodes['draft_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--, 'moderation_state' => 'draft']);
    $nodes['draft_page_1'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--, 'moderation_state' => 'draft']);
    $nodes['draft_page_2'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time, 'moderation_state' => 'draft']);

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content/moderated');
    $assert_session->statusCodeEquals(200);

    // Check that nodes with pending revisions appear in the view.
    $node_type_labels = $this->xpath('//td[contains(@class, "views-field-type")]');
    $delta = 0;
    foreach ($nodes as $node) {
      $assert_session->linkByHrefExists('node/' . $node->id());
      $assert_session->linkByHrefExists('node/' . $node->id() . '/edit');
      $assert_session->linkByHrefExists('node/' . $node->id() . '/delete');
      // Verify that we can see the content type label.
      $this->assertEquals($node->type->entity->label(), trim($node_type_labels[$delta]->getText()));
      $delta++;
    }

    // Check that nodes that are not moderated or do not have a pending revision
    // do not appear in the view.
    foreach ($excluded_nodes as $node) {
      $assert_session->linkByHrefNotExists('node/' . $node->id());
    }

    // Check that the latest revision is displayed.
    $assert_session->pageTextContains('first article - draft');
    $assert_session->pageTextNotContains('first article - published');

    // Verify filtering by moderation state.
    $this->drupalGet('admin/content/moderated', ['query' => ['moderation_state' => 'editorial-draft']]);

    $assert_session->linkByHrefExists('node/' . $nodes['published_then_draft_article']->id() . '/edit');
    $assert_session->linkByHrefExists('node/' . $nodes['draft_article']->id() . '/edit');
    $assert_session->linkByHrefExists('node/' . $nodes['draft_page_1']->id() . '/edit');
    $assert_session->linkByHrefExists('node/' . $nodes['draft_page_1']->id() . '/edit');
    $assert_session->linkByHrefNotExists('node/' . $nodes['published_then_archived_article']->id() . '/edit');

    // Verify filtering by moderation state and content type.
    $this->drupalGet('admin/content/moderated', ['query' => ['moderation_state' => 'editorial-draft', 'type' => 'page']]);

    $assert_session->linkByHrefExists('node/' . $nodes['draft_page_1']->id() . '/edit');
    $assert_session->linkByHrefExists('node/' . $nodes['draft_page_2']->id() . '/edit');
    $assert_session->linkByHrefNotExists('node/' . $nodes['published_then_draft_article']->id() . '/edit');
    $assert_session->linkByHrefNotExists('node/' . $nodes['published_then_archived_article']->id() . '/edit');
    $assert_session->linkByHrefNotExists('node/' . $nodes['draft_article']->id() . '/edit');
  }

  /**
   * Tests the moderated content page with multilingual content.
   */
  public function testModeratedContentPageMultilingual(): void {
    ConfigurableLanguage::createFromLangcode('fr')->save();

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'moderation_state' => 'published',
      'title' => 'en article published',
    ]);

    $node->title = 'en draft revision';
    $node->moderation_state = 'draft';
    $node->save();

    $translation = Node::load($node->id())->addTranslation('fr');
    $translation->title = 'fr draft revision';
    $translation->moderation_state = 'draft';
    $translation->save();

    $this->drupalLogin($this->adminUser);

    // The moderated content view should show both the pending en draft revision
    // and the pending fr draft revision.
    $this->drupalGet('admin/content/moderated');
    $this->assertSession()->linkExists('fr draft revision');
    $this->assertSession()->linkExists('en draft revision');
  }

}
