<?php

namespace Drupal\spotify_api_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\spotify_api_integration\Service\SpotifyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;  // Updated import for Html class.

/**
 * Provides the artist page controller.
 */
class ArtistPageController extends ControllerBase {

  /**
   * The Spotify service.
   *
   * @var \Drupal\spotify_api_integration\Service\SpotifyService
   */
  protected $spotifyService;

  /**
   * Constructor to inject dependencies.
   *
   * @param \Drupal\spotify_api_integration\Service\SpotifyService $spotify_service
   *   The Spotify service.
   */
  public function __construct(SpotifyService $spotify_service) {
    $this->spotifyService = $spotify_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spotify_api_integration.spotify_service')
    );
  }

  /**
   * Displays artist information (name, image, genres).
   *
   * @param string $artist_id
   *   The artist ID.
   *
   * @return array
   *   A render array.
   */
  public function viewArtist($artist_id) {
    // Check if the user is logged in.
    if (\Drupal::currentUser()->isAnonymous()) {
      // Redirect anonymous users to the login page.
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }

    // Proceed to fetch artist information and render the page.
    $artist_info = $this->spotifyService->getArtistById($artist_id);

    if (!$artist_info || !isset($artist_info['name'])) {
      \Drupal::logger('spotify_api_integration')->error('Error fetching artist data for artist ID: @id', ['@id' => $artist_id]);
      return [
        '#markup' => $this->t('Artist not found or an error occurred.'),
      ];
    }

    $artist_name = $artist_info['name'];
    $artist_image = !empty($artist_info['images'][0]['url']) ? $artist_info['images'][0]['url'] : '';
    $artist_genres = !empty($artist_info['genres']) ? implode(', ', $artist_info['genres']) : $this->t('No genres available');
    
    // Use Html::escape to properly sanitize the image URL and artist name.
    $image_html = $artist_image ? '<img src="' . Html::escape($artist_image) . '" alt="' . Html::escape($artist_name) . '" />' : '';
    $genre_html = '<strong>' . Html::escape($artist_name) . '</strong><br>' . $this->t('Genres: @genres', ['@genres' => $artist_genres]);

    // Use #markup to ensure Drupal processes the HTML content correctly.
    $items = [];
    $items[] = [
      '#markup' => $image_html . $genre_html,
    ];

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $artist_name,
    ];
  }

  /**
   * Callback to get the title of the artist page.
   *
   * @param string $artist_id
   *   The artist ID.
   *
   * @return string
   *   The artist name or 'Artist' if the name is not available.
   */
  public function getTitle($artist_id) {
    $artist_info = $this->spotifyService->getArtistById($artist_id);
    return $artist_info ? $artist_info['name'] : $this->t('Artist');
  }
}
