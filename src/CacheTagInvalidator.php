<?php

namespace Drupal\above_the_fold;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;

class CacheTagInvalidator implements CacheTagsInvalidatorInterface {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var string
   */
  protected $bin;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   */
  public function __construct(Connection $connection, CacheBackendInterface $cache, $bin) {
    $this->connection = $connection;
    $this->cache = $cache;
    $this->bin = $bin;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {

    // This will happen during install/uninstall.
    if (!$this->connection->schema()->tableExists('cache_' . $this->bin)) {
      return;
    }

    // Since our cache backend is no tagged with `cache.bin`, we have to locate
    // the cache IDs manually via this service, when tags are invalidated.  This
    // is because this service is tagged with `cache_tags_invalidator`.
    $cids = [];
    foreach ($tags as $tag) {
      $query = $this->connection
        ->select('cache_' . $this->bin, 'c')
        ->fields('c', ['cid']);
      $tag_matching_conditions = $query->orConditionGroup()
        ->condition('tags', $tag)
        ->condition('tags', $tag . ' %', 'LIKE')
        ->condition('tags', '% ' . $tag, 'LIKE');
      $query->condition($tag_matching_conditions);
      $cids = array_merge($cids, $query->execute()->fetchCol());
    }
    if (count($cids)) {
      // Choosing delete over invalidate for small db size.  There is no value
      // with having invalid results around.
      \Drupal::service('cache.' . $this->bin)->deleteMultiple($cids);
    }
  }

}
