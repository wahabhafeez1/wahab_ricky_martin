<?php

namespace Drupal\spotify_api_integration\Service;

use GuzzleHttp\Client;

class SpotifyService {
  protected $clientId;
  protected $clientSecret;
  protected $httpClient;

  public function __construct() {
    $config = \Drupal::config('spotify_api_integration.settings');
    $this->clientId = $config->get('client_id');
    $this->clientSecret = $config->get('client_secret');
    $this->httpClient = new Client();
  }

  public function getAccessToken() {
    $response = $this->httpClient->post('https://accounts.spotify.com/api/token', [
      'headers' => ['Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}")],
      'form_params' => ['grant_type' => 'client_credentials'],
    ]);
    $data = json_decode($response->getBody(), TRUE);
    return $data['access_token'] ?? NULL;
  }

  /**
   * Search for artists by name.
   *
   * @param string $query
   *   The name of the artist to search for.
   *
   * @return array
   *   An array of artist details.
   */
  public function searchArtists($query) {
    $token = $this->getAccessToken();
    $response = $this->httpClient->get('https://api.spotify.com/v1/search', [
      'headers' => ['Authorization' => "Bearer $token"],
      'query' => [
        'q' => $query,
        'type' => 'artist',
        'limit' => 10, // Limit to 10 results
      ],
    ]);
    $data = json_decode($response->getBody(), TRUE);
    return $data['artists']['items'] ?? [];
  }

  /**
   * Fetch artist details by ID from Spotify.
   */
  public function getArtistById($artist_id) {
    $token = $this->getAccessToken();
    $response = $this->httpClient->get('https://api.spotify.com/v1/artists/' . $artist_id, [
      'headers' => ['Authorization' => "Bearer $token"]
    ]);
    return json_decode($response->getBody(), TRUE);
  }
}
