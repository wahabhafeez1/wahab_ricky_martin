<?php

namespace Drupal\spotify_api_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\spotify_api_integration\Service\SpotifyService;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
 * Provides a block to display selected Spotify artists as links.
 *
 * @Block(
 *   id = "selected_artists_block",
 *   admin_label = @Translation("Selected Artists Block")
 * )
 */
class SelectedArtistsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Retrieve artist IDs from the database.
    $connection = Database::getConnection();
    $query = $connection->select('spotify_artists', 's')
      ->fields('s', ['artist_id'])  // Select only artist_id
      ->execute();
    $artist_data = $query->fetchAll();

    // Check if there are any artists.
    if (empty($artist_data)) {
      return [
        '#markup' => $this->t('No artists have been selected yet.'),
      ];
    }

    // Initialize SpotifyService to get artist details.
    $spotify_service = \Drupal::service('spotify_api_integration.spotify_service');
    $items = [];

    // Check if the user is logged in.
    $is_logged_in = \Drupal::currentUser()->isAuthenticated();

    foreach ($artist_data as $artist) {
      // Fetch artist details using SpotifyService.
      try {
        $artist_info = $spotify_service->getArtistById($artist->artist_id);

        // Check if artist information was fetched successfully.
        if ($artist_info && isset($artist_info['name'])) {
          $artist_name = $artist_info['name'];

          // If the user is logged in, create a link to the artist's page.
          if ($is_logged_in) {
            $artist_url = Url::fromRoute('spotify_api_integration.artist_page', ['artist_id' => $artist->artist_id]);
            $artist_link = Link::fromTextAndUrl($artist_name, $artist_url)->toString();
            $items[] = [
              '#markup' => $artist_link,
            ];
          } else {
            // If the user is not logged in, just display the artist name as plain text.
            $items[] = [
              '#markup' => $artist_name,
            ];
          }
        }
        else {
          // Handle case where artist data is incomplete.
          \Drupal::logger('spotify_api_integration')->warning('Artist data incomplete for artist ID: @id', ['@id' => $artist->artist_id]);
        }
      } catch (\Exception $e) {
        // Handle any exceptions that occur during API call.
        \Drupal::logger('spotify_api_integration')->error('Error fetching data for artist ID: @id, Error: @message', ['@id' => $artist->artist_id, '@message' => $e->getMessage()]);
      }
    }

    // Return the rendered items list with artist links or plain text.
    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Selected Artists'),
      '#attributes' => [
        'class' => ['selected-artists-list'],
      ],
    ];
  }
}
