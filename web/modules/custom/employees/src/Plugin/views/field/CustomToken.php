<?php

namespace Drupal\employees\Plugin\views\field;

use Drupal\views\Plugin\views\field\Custom;

/**
 * A handler to provide a field extending custom by global token replacement.
 * See https://drupal.stackexchange.com/a/259541
 * 
 * @ingroup views_field_handlers
 *
 * @ViewsField("custom")
 */
class CustomToken extends Custom {

  /**
   * {@inheritdoc}
   */
  protected function renderAltered($alter, $tokens) {
    return $this->viewsTokenReplace($this->globalTokenReplace($alter['text']), $tokens);
  }
}