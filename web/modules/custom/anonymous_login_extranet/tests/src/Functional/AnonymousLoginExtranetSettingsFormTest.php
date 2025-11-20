<?php

namespace Drupal\Tests\anonymous_login_extranet\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Anonymous Login for Extranet for Extranet settings form browser tests.
 *
 * @group anonymous_login_extranet
 */
class AnonymousLoginExtranetSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'anonymous_login_extranet',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $moduleConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleConfig = $this->config('anonymous_login_extranet.settings');
  }

  /**
   * Tests module settings form.
   */
  public function testForm() {
    $this->drupalGet('admin/config/user-interface/anonymous-login');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->drupalCreateUser(['administer Anonymous Login for Extranet settings']));

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
    $this->submitForm($edit, 'Save configuration');
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
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $this->drupalGet('admin/config/user-interface/anonymous-login');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->fieldValueEquals('paths', '*' . PHP_EOL . '~/user/register');
    $this->assertSession()->fieldValueEquals('login_path', '/node/1');
    $this->assertSession()->fieldValueEquals('message', 'Test login message.');
  }

}
