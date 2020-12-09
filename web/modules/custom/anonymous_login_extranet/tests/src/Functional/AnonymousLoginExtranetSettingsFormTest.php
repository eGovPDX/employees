<?php

namespace Drupal\Tests\anonymous_login\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Anonymous login settings form browser tests.
 *
 * @group anonymous_login
 */
class AnonymousLoginSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'anonymous_login',
    'node',
  ];

  /**
   * Module configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $moduleConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->moduleConfig = $this->config('anonymous_login.settings');
  }

  /**
   * Tests module settings form.
   */
  public function testForm() {
    $this->drupalGet('admin/config/user-interface/anonymous-login');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->drupalCreateUser(['administer anonymous login settings']));

    $this->drupalGet('admin/config/user-interface/anonymous-login_wrong');
    $this->assertSession()->statusCodeEquals(404);

    $this->drupalGet('admin/config/user-interface/anonymous-login');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'paths' => '*' . PHP_EOL . '~/user/register',
      'login_path' => '/some-wrong-login-path',
      'message' => 'Test login message.',
    ];
    // Test login path validation.
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('Login page path is invalid. Check it please.');

    // Prepare node to use it in login path setting.
    $this->createContentType(['type' => 'node_test']);
    \Drupal::entityTypeManager()
      ->getStorage('node')
      ->create([
        'type' => 'node_test',
        'title' => $this->randomString(),
      ])->save();
    $edit['login_path'] = '/node/1';

    // Test form saving.
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('admin/config/user-interface/anonymous-login');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldValueEquals('paths', '*' . PHP_EOL . '~/user/register');
    $this->assertSession()->fieldValueEquals('login_path', '/node/1');
    $this->assertSession()->fieldValueEquals('message', 'Test login message.');
  }

}
