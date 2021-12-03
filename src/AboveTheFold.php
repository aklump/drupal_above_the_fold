<?php

namespace Drupal\above_the_fold;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Symfony\Component\HttpFoundation\Request;

final class AboveTheFold {

  /**
   * @var array
   */
  static private $results = [];

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var string
   */
  private $hash;

  /**
   * @var array
   */
  private $cacheTags = [];

  /**
   * @param string $uri
   *   The relative URI of an image.  Should not be absolute!
   * @param array $context
   *   An arbitrary array of contextual info which is used to track $uri.
   */
  public function __construct(string $uri, array $context) {
    $this->cache = \Drupal::cache('above_the_fold');
    $this->hash = md5(json_encode(func_get_args()));
  }

  /**
   * @param string $hash
   *
   * @return
   *   Self for chaining.
   */
  public function setHash(string $hash): self {
    $this->hash = $hash;

    return $this;
  }

  /**
   * @param array $cacheTags
   *
   * @return
   *   Self for chaining.
   */
  public function setCacheTags(array $cacheTags): self {
    $this->cacheTags = $cacheTags;

    return $this;
  }

  /**
   * Create instance using a request as context.
   *
   * @param string $uri
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return static
   *
   * @throws \RuntimeException
   *   If $request is an AJAX request.
   */
  public static function fromRequest(string $uri, Request $request): self {
    if ($request->isXmlHttpRequest()) {
      throw new \RuntimeException('An AJAX request cannot be used with this method.');
    }
    $image_context = ['page' => $request->getUri()];
    list(, $image_context['page']) = explode(parse_url($image_context['page'], PHP_URL_HOST), $image_context['page']);
    $obj = new AboveTheFold($uri, $image_context);

    /** @var \Drupal\node\NodeInterface $node */
    $node = $request->attributes->get('node');
    if ($node instanceof CacheableDependencyInterface) {
      $obj->setCacheTags($node->getCacheTags());
    }

    return $obj;
  }

  /**
   * Create instance from a hash.
   *
   * @param string $hash
   *
   * @return static
   */
  public static function fromHash(string $hash): self {
    $obj = new AboveTheFold('', []);
    $obj->setHash($hash);

    return $obj;
  }

  /**
   * Determine if the image is indicated to be above the fold.
   *
   * @return bool
   *   True if the image is indicated as above the fold.
   */
  public function get(): bool {
    if (!isset(static::$results[$this->hash])) {
      static::$results[$this->hash] = boolval($this->cache->get($this->hash));
    }

    return static::$results[$this->hash];
  }

  /**
   * Indicate if this image is ATF or not.
   *
   * @param bool $is_above_the_fold
   *   True if the image is above the fold in the given context.
   *
   * @return $this
   *   Self for chaining.
   */
  public function set(bool $is_above_the_fold = TRUE): self {
    if (!$is_above_the_fold) {
      $this->cache->delete($this->hash);
    }
    else {
      $this->cache->set($this->hash, TRUE, Cache::PERMANENT, $this->cacheTags);
    }

    return $this;
  }

  public function __toString() {
    return json_encode([
      'hash' => $this->hash,
      'tags' => $this->cacheTags,
    ]);
  }

}
