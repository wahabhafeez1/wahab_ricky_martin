<?php

namespace Drupal\spotify_api_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\spotify_api_integration\Service\SpotifyService;
use Drupal\Core\Database\Database;

/**
 * Class SelectedArtistsController.
 */
class SelectedArtistsController extends ControllerBase {

  /**
   * The Spotify service.
   *
   * @var \Drupal\spotify_api_integration\Service\SpotifyService
   */
  protected $spotifyService;

  /**
   * Constructor to inject the Spotify service.
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
   * Displays the list of artist IDs and names from the database, along with names from Spotify API.
   *
   * @return array
   *   A render array containing a table with artist IDs and names.
   */
  public function content() {
    // Retrieve artist IDs from the database.
    $connection = Database::getConnection();
    $query = $connection->select('spotify_artists', 's')
      ->fields('s', ['artist_id'])  // Only select artist_id field
      ->execute();
    $artist_data = $query->fetchAll();

    // Get SpotifyService to fetch artist details.
    $spotify_service = $this->spotifyService;

    // Define table headers.
    $header = [
      $this->t('Artist ID'),
      $this->t('Artist Name'),
    ];

    $rows = [];

    foreach ($artist_data as $artist) {
      // Fetch the artist name from Spotify API using the artist ID.
      $artist_info = $spotify_service->getArtistById($artist->artist_id);

      // Check if the artist's name exists.
      if ($artist_info && isset($artist_info['name'])) {
        $artist_name = $artist_info['name'];
        
        // Save artist ID and name in the database (if not already saved).
        $this->saveArtistName($artist->artist_id, $artist_name);
      } else {
        // Log and use 'Unknown' if name is not found.
        \Drupal::logger('spotify_api_integration')->warning('Artist data incomplete for artist ID: @id', ['@id' => $artist->artist_id]);
        $artist_name = $this->t('Unknown');
      }

      // Add the artist's row to the table.
      $rows[] = [
        'data' => [
          ['data' => $artist->artist_id],  // Display the artist ID
          ['data' => $artist_name],         // Display the artist name
        ],
      ];
    }

    // If no artists are selected, show a message.
    if (empty($rows)) {
      $build['message'] = [
        '#markup' => $this->t('No artists have been selected yet.'),
      ];
    } else {
      // Build render array for the table.
      $build['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No artists found.'),
      ];
    }

    // Return the rendered table.
    return $build;
  }

  /**
   * Saves the artist ID and name in the database.
   *
   * @param string $artist_id
   *   The artist ID to save.
   * @param string $artist_name
   *   The artist name to save.
   */
  private function saveArtistName($artist_id, $artist_name) {
    $connection = Database::getConnection();
    
    // Check if the artist ID already exists in the database.
    $exists = $connection->select('spotify_artists', 's')
      ->fields('s', ['artist_id'])
      ->condition('artist_id', $artist_id)
      ->execute()
      ->fetchField();

    // If the artist ID doesn't exist, insert it along with the artist name.
    if (!$exists) {
      $connection->insert('spotify_artists')
        ->fields([
          'artist_id' => $artist_id,
          'name' => $artist_name,  // Save the artist name alongside the ID
        ])
        ->execute();

      \Drupal::logger('spotify_api_integration')->debug('Inserted artist_id: @id with name: @name', ['@id' => $artist_id, '@name' => $artist_name]);
    }
  }
}
