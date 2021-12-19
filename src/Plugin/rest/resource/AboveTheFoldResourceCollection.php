<?php

namespace Drupal\above_the_fold\Plugin\rest\resource;

use AKlump\Taskcamp\API\Entity;
use Drupal\above_the_fold\AboveTheFold;
use Drupal\above_the_fold\Event\ReportReceivedEvent;
use Drupal\above_the_fold\Event\TaskcampReporterEvents;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Represents an image.
 *
 * @see \Drupal\rest\Plugin\Deriver\EntityDeriver
 *
 * @RestResource(
 *   id = "above_the_fold",
 *   label = @Translation("Above the fold image resource collection"),
 *   uri_paths = {
 *     "create" = "/api/above-the-fold"
 *   }
 * )
 */
class AboveTheFoldResourceCollection extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    return [
      'above_the_fold' => [
        'title' => 'Report above the fold images',
        'description' => "Observes a user's browser to report images are above the fold.",
      ],
    ];
  }

  /**
   * Create a Image resource.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\rest\ResourceResponse|\Symfony\Component\HttpKernel\Exception\BadRequestHttpException|JsonResponse
   */
  public function post(Request $request) {
    try {
      $json = $request->getContent();
      if (!$json) {
        throw new \RuntimeException();
      }
      $data = json_decode($json, TRUE);
      if (!$data || empty($data['hash'])) {
        throw new \RuntimeException();
      }
      $above = AboveTheFold::fromHash($data['hash']);
      if (!empty($data['tags'])) {
        $above->setCacheTags($data['tags']);
      }
      $above->set();

      return new JsonResponse([
        'message' => 'Image marked with above the fold status.',
        'items' => [
          json_decode(strval($above)),
        ],
      ], 201);
    }
    catch (\Exception $exception) {
      return new ResourceResponse([], 500);
    }
  }

}
