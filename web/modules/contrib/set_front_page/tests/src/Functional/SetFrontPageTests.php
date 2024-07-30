<?php

namespace Drupal\Tests\set_front_page\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests for set front page module.
 *
 * @group set_front_page
 */
class SetFrontPageTests extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views', 'node', 'set_front_page'];

  /**
   * The node object that is created for testing.
   *
   * @var string
   */
  protected $node;

  /**
   * The path to a node that is created for testing.
   *
   * @var string
   */
  protected $nodePath;

  /**
   * The path to a term that is created for testing.
   *
   * @var string
   */
  protected $termPath;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user, log in admin user, and create one node.
    $this->drupalLogin ($this->drupalCreateUser([
      'access content',
      'administer site configuration',
      'set front page'
    ]));
    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->node = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);
    $this->nodePath = 'node/' . $this->node->id();

    // Configure 'node' as front page.
    $this->config('system.site')->set('page.front', '/node')->save();
  }

  /**
   * Test override front page functionality.
   */
  public function testSetFrontPageConfig() {
    // Test default homepage.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals('Home | Drupal');

    // Change the front page to an invalid path.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage' => '/kittens'];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains((string) new FormattableMarkup("The path '@path' is either invalid or you do not have access to it.", ['@path' => $edit['site_frontpage']]));

    // Change the front page to a valid path without a starting slash.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage' => $this->nodePath];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains((string) new FormattableMarkup("The path '@path' has to start with a slash.", ['@path' => $edit['site_frontpage']]));

    // Change the front page to a valid path.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit['site_frontpage'] = '/' . $this->nodePath;
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // The homepage is $this->node and its title changed.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals($this->node->getTitle() . ' | Drupal');

    // Configure a default frontpage path with an invalid path.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage_default' => '/kittens'];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains((string) new FormattableMarkup("The path '@path' is either invalid or you do not have access to it.", ['@path' => $edit['site_frontpage_default']]));

    // Configure a default frontpage path with a valid path without starting
    // slash.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage_default' => $this->nodePath];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains((string) new FormattableMarkup("The path '@path' has to start with a slash.", ['@path' => $edit['site_frontpage_default']]));

    // Change the default front page to a valid path.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit['site_frontpage_default'] = '/node';
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // The set frontpage tab is not accessible by this user, because the
    // content type is not enabled in the set front page configuration.
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertSession()->statusCodeEquals(403);

    // Enabled valid content type to be an homepage.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit['set_front_page_node_type__page'] = TRUE;
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // The set frontpage tab is accessible by this user
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertSession()->statusCodeEquals(200);
    // The corrent node is the frontpage so the save button is disabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-save" and @type="submit" and @disabled="disabled"]')), 'The "Use this page as the front page" button is present and disabled.');

    // The default buttons is enabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-change-to-default" and @type="submit" and not(@disabled)]')), 'The "Revert to the default page" button is present and enabled.');

    // In the content type blog the set frontpage is not enabled.
    $blog_node = $this->drupalCreateNode(['type' => 'blog', 'promote' => 1]);

    // The set frontpage tab is not accessible by this user
    $this->drupalGet('node/' . $blog_node->id() . '/set_front_page');
    $this->assertSession()->statusCodeEquals(403);

    // Create a new node.
    $node = $this->drupalCreateNode(['type' => 'page', 'promote' => 1]);

    // The set frontpage tab is accessible by this user
    $this->drupalGet('node/' . $node->id() . '/set_front_page');
    $this->assertSession()->statusCodeEquals(200);

    // The frontapge is not the current node, so is enabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-save" and @type="submit" and not(@disabled)]')), 'The "Use this page as the front page" button is present and enabled.');

    // The default buttons is enabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-change-to-default" and @type="submit" and not(@disabled)]')), 'The "Revert to the default page" button is present and enabled.');

    // Change the front page to $node.
    $this->drupalGet('node/' . $node->id() . '/set_front_page');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], t('Use this page as the front page'));
    $this->assertSession()->statusCodeEquals(200);

    // The frontapge is the current node, so is disabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-save" and @type="submit" and @disabled="disabled"]')), 'The "Use this page as the front page" button is present and disabled.');

    // The default buttons is enabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-change-to-default" and @type="submit" and not(@disabled)]')), 'The "Revert to the default page" button is present and enabled.');

    // Confirm that the front page is set to the new node.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->titleEquals($node->getTitle() . ' | Drupal');

    // Change the default frontpage path.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage_default' => '/' . $this->nodePath];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertSession()->statusCodeEquals(200);

    // The current node is the frontpage so disable the save button.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-save" and @type="submit" and not(@disabled)]')), 'The "Use this page as the front page" button is present and enabled.');

    // The current node is the default frontpage so the button is disabled.
    $this->assertTrue(!empty($this->xpath('//input[@id="edit-change-to-default" and @type="submit" and @disabled = "disabled"]')), 'The "Revert to the default page" button is present and disabled.');

    // If the default frontpage path is not defined, the related button should
    // disappear.
    $this->drupalGet('admin/config/set_front_page/settings');
    $this->assertSession()->statusCodeEquals(200);
    $edit = ['site_frontpage_default' => ''];
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->drupalGet($this->nodePath . '/set_front_page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue(empty($this->xpath('//input[@id="edit-change-to-default" and @type="submit"]')), 'The "Revert to the default page" button is not present.');
  }
}

