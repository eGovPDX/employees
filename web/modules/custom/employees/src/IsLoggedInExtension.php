<?php

namespace Drupal\employees;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsLoggedInExtension extends AbstractExtension {
  public function getName() {
    return 'employees.is_logged_in_extension';
  }


  public function getFunctions() {
    return [
      new TwigFunction('is_logged_in', [$this, 'isLoggedIn'], ['is_safe' => ['html']]),
    ];
  }

  public function isLoggedIn() {
    return \Drupal::currentUser()->isAuthenticated();
  }
}
