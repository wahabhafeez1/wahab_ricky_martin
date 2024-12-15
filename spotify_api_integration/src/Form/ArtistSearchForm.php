<?php

namespace Drupal\spotify_api_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_api_integration\Service\SpotifyService;
use Drupal\Core\Database\Database;

class ArtistSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spotify_api_integration_artist_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Artist search input.
    $form['artist_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist Name'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the name of the artist you want to search for.'),
    ];

    // Actions wrapper.
    $form['actions']['#type'] = 'actions';

    // Submit button for searching.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search Artists'),
    ];

    // If search results exist (from a previous submit), show checkboxes to save new artists.
    if ($artists = $form_state->get('artists')) {
      $form['artists'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select Artists'),
        '#options' => array_column($artists, 'name', 'id'),
        '#description' => $this->t('Check the artists you want to save, then click "Save Selected Artists".'),
      ];

      // Another button to save the selected artists.
      $form['actions']['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Selected Artists'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\spotify_api_integration\Service\SpotifyService $spotify_service */
    $spotify_service = \Drupal::service('spotify_api_integration.spotify_service');

    // Get the submitted artist name.
    $artist_name = $form_state->getValue('artist_name');

    // Search for artists from Spotify.
    $artists = $spotify_service->searchArtists($artist_name);

    // Store the artists array in form state so that we can display them as checkboxes.
    $form_state->set('artists', $artists);

    // Determine which button was clicked.
    $trigger = $form_state->getTriggeringElement()['#value'];

		if ($trigger == 'Save Selected Artists') {

      // Get the selected artist IDs (check the selected checkboxes)
      $selected = array_keys(array_filter($form_state->getValue('artists')));

      // Log the selected artist IDs for debugging
      \Drupal::logger('spotify_api_integration')->debug('Selected artist IDs: ' . print_r($selected, TRUE));

      if (!empty($selected)) {
        $connection = Database::getConnection();

        foreach ($selected as $artist_id) {
          // Insert only the artist_id into the database.
          $connection->insert('spotify_artists')
            ->fields([
              'artist_id' => $artist_id,
            ])
            ->execute();

          // Log the artist_id being inserted for debugging
          \Drupal::logger('spotify_api_integration')->debug('Inserting artist_id: ' . $artist_id);
        }

        $this->messenger()->addStatus($this->t('Selected artist IDs have been saved to the database.'));
      }
      else {
        $this->messenger()->addWarning($this->t('No artists were selected.'));
      }
    }

    // Rebuild the form for subsequent actions.
    $form_state->setRebuild(TRUE);
  }
}
