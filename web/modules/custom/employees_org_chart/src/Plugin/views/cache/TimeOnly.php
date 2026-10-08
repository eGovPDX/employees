<?php

namespace Drupal\employees_org_chart\Plugin\views\cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsCache;
use Drupal\views\Plugin\views\cache\Time;

/**
 * Time-based caching that ignores changes to the listed entities.
 *
 * Core Views adds a cache tag for every user row. For an org chart of 
 * thousands of people that makes the cache-tag response headers too large, 
 * and the entity list tag (user_list) is invalidated by every user save. 
 * This plugin keeps only the view's own config tag, so the
 * output refreshes when the lifespan expires or the view is edited.
 *
 * It also adds the tag that portland_openid_connect invalidates after each
 * user sync from Entra ID, so the output refreshes once the synced data is
 * complete. The lifespan is then only a fallback for changes made outside the
 * sync, such as manual profile edits.
 */
#[ViewsCache(
  id: 'employees_org_chart_time_only',
  title: new TranslatableMarkup('Time-based, ignoring entity changes'),
  help: new TranslatableMarkup('Time-based caching that does not add a cache tag per result. Results can be stale for up to the lifespan.'),
)]
class TimeOnly extends Time {

  /**
   * Cache tag invalidated after each user sync.
   *
   * Kept as a string so this module doesn't depend on portland_openid_connect.
   *
   * @see \Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil::USER_SYNC_CACHE_TAG
   */
  const USER_SYNC_CACHE_TAG = 'portland_openid_connect:user_sync';

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags($this->view->storage->getCacheTags(), [static::USER_SYNC_CACHE_TAG]);
  }

}
