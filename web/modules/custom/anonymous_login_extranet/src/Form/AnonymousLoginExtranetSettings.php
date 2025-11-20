<?php

namespace Drupal\anonymous_login_extranet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Anonymous Login Extranet Settings Form
 */
class AnonymousLoginExtranetSettings extends ConfigFormBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'anonymous_login_extranet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_login_extranet_settings';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pathValidator = $container->get('path.validator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('anonymous_login_extranet.settings');
    $paths = $config->get('paths') ?? [];
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page paths'),
      '#default_value' => $paths ? implode(PHP_EOL, $paths) : '',
      '#description' => $this->t('Enter a list of page paths that will force anonymous users to login before viewing. After logging in, they will be redirected back to the requested page. Enter each path on a different line. Wildcards (*) can be used. Prefix a path with ~ (tilde) to exclude it from being redirected.'),
    ];
    // *** Begin Anonymous Login Extranet change
	$form['ip_exceptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Internal IP Addresses'),
      '#default_value' => $config->get('ip_exceptions'),
      '#description' => $this->t('Enter a list of internal IP addresses. Anonymous users from these IP addresses will not be forced to login. Enter each IP address on a different line. IP address ranges can also be entered using the format \'192.168.0.0 - 192.168.255.255\'.'),
    ];
	// *** End Anonymous Login Extranet change
    $form['login_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login page path'),
      '#default_value' => ($config->get('login_path')) ? $config->get('login_path') : '/user/login',
      '#required' => TRUE,
      '#description' => $this->t('Enter the user login page path of your site.'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login message'),
      '#default_value' => $config->get('message'),
      '#description' => $this->t('Optionally provide a message that will be shown to users when they are redirected to login.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

	// *** Begin Anonymous Login Extranet change
    // IP address range validation.
    $ip_exceptions = $form_state->getValue('ip_exceptions');
    if (strlen($ip_exceptions)) {
      $ip_exceptions = trim($ip_exceptions);
      $form_state->setValue('ip_exceptions', $ip_exceptions);
      
      // Split by each newline.
      $setting = explode(PHP_EOL, $ip_exceptions);
  
      // Iterate each line and check for valid IP address or range.
      foreach ($setting as $ip_range) {
        if (preg_match('/^((2[0-4]\d|25[0-5]|1?\d?\d)(?:\.(?2)){3})\s*-\s*((?1))\r?$/', $ip_range)) {
          // Valid IP address range
          continue;
        }
        elseif (preg_match('/^(2[0-4]\d|25[0-5]|1?\d?\d)(?:\.(?1)){3}\r?$/', $ip_range)) {
          // Valid single IP address
          continue;
        }
        else {
          // Invalid address/range
          $form_state->setErrorByName('ip_exceptions', $this->t('Invalid IP address or range: ') . $ip_range);
        }
      }
    }
	// *** End Anonymous Login Extranet change
    
    $paths = array_filter(array_map('trim', explode(PHP_EOL, $form_state->getValue('paths'))));
    $form_state->setValue('paths', $paths);

    // Login page path validation.
    $path = $this->pathValidator
      ->getUrlIfValidWithoutAccessCheck($form_state->getValue('login_path'));
    if (!$path) {
      $form_state->setErrorByName('login_path', $this->t('Login page path is invalid. Check it please.'));
    }
    else {
      // Set path without language prefix.
      $form_state->setValue('login_path', '/' . $path->getInternalPath());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('anonymous_login_extranet.settings')
      ->set('paths', $form_state->getValue('paths'))
	  // *** Begin Anonymous Login Extranet change
      ->set('ip_exceptions', $form_state->getValue('ip_exceptions'))
	  // *** End Anonymous Login Extranet change
      ->set('login_path', $form_state->getValue('login_path'))
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
