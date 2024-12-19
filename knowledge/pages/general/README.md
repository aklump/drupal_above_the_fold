<!--
id: readme
tags: ''
-->

# Above The Fold Drupal Module

## Summary

1. Provides a means to track which images should load immediately (above the fold) and which images should wait for lazy-loading.
2. Provides a REST endpoint that allows JS to tell that an image has loaded above the fold.

{{ composer.install|raw }}

## Configuration

1. Grant user permissions as explained here.
2. Unless a user has the `above_the_fold` permission, the endpoint `/api/above-the-fold` will not allow them access, and therefor their browsing activity will have no effect, as far as this module is concerned.
3. This module does not provide a mechanism for `POST`ing to the endpoint, however example code is shown below.

## Integration in Theory

1. For a given image, create an instance of `\Drupal\above_the_fold\AboveTheFold`
2. Call `$above_the_fold->get()` on that instance.
3. If the result is `TRUE`, then that image should load immediately; otherwise it should lazy load. In either case make the appropriate adjustments to the render process.
4. When `FALSE`, you must pass the string value of the instance (this will be a JSON string) to javascript in connection with the image. This could be done using a `data` attribute on the image.
5. Using JS logic, determine if the image loads above the fold. If it does, then `POST` the JSON value of the instance to the endpoint _/api/above-the-fold_. This will cause `$above_the_fold->get()` to return `TRUE` the next time it is called.

## Integration Code Example

### PHP

```php
$image = [
  '#theme' => 'image',
  '#src' => 'public://images/foo.jpg',
  '#attributes' => [],
];

try {
  $above = \Drupal\above_the_fold\AboveTheFold::fromRequest($image['#src'], \Drupal::request());

  // Check if the image is found in our registry.
  if (!$above->get()) {

    // Nope.  It should lazy load, or it has not yet been registered.
    $image['#attributes']['class'][] = 'lazy-load';

    // If the current user has permission to report status then add the data attribute that will be used by JS to POST.
    if (\Drupal::currentUser()->hasPermission('above_the_fold')) {
      $image['#attributes']['data-above-the-fold'] = strval($above);
    }
  }
}
catch(\Exception $e) {
  // This happens if request is AJAX
}



```

### Javascript

```js
function onImageLoaded(imageEl) {

  const heightOfTheFoldInPixels = 800

  if (0 === window.scrollY && window.innerHeight <= heightOfTheFoldInPixels) {
    const json = imageEl.getAttribute('data-above-the-fold')
    if (json) {
      $.ajax('/api/above-the-fold', {
        data: json,
        contentType: 'application/json',
        type: 'POST',
        beforeSend: function(xhr) {
          xhr.setRequestHeader('X-CSRF-Token', drupalSettings.ajaxPlus.csrfToken)
        },
      })
    }
  }
}
```

{{ funding|raw }}
