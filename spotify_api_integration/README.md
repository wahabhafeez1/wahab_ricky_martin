# Spotify API Integration

This module integrates the Spotify API with a Drupal 10 website. It allows you to search for artists on Spotify, save selected artist IDs and names to the Drupal database, and display the artist's information (name, image, genres) on a custom page.

## Features
- Search for Spotify artists by name.
- Save selected artist IDs and names to the Drupal database.
- Display the list of saved artist IDs and names in a custom block.
- Show detailed information about each artist on their individual page (name, image, genres).
- Restrict access to the artist pages for logged-in users only.

## Installation

### Requirements
- Drupal 10 or later.
- Composer installed for managing dependencies.
- A valid Spotify API client ID and client secret.

### Step 1: Enable the module

1. Install the module via Drush or the Drupal admin interface.

   - **Via Drush**:
     ```bash
     drush en spotify_api_integration
     ```

   - **Via Admin Interface**:
     - Go to **Extend** in the Drupal admin interface.
     - Search for **Spotify API Integration** and enable it.

### Step 2: Configure the Spotify API credentials

1. After enabling the module, navigate to **Configuration** > **Spotify API Integration**.
2. Enter your **Client ID** and **Client Secret** obtained from the Spotify Developer Dashboard.

### Step 3: Use the artist search form

1. Navigate to the **Artist Search** form page (provided by the module) to search for artists by name.
2. Select the artists you want to save and click **Save Selected Artists**. This will store the artist ID and name in the Drupal database.

### Step 4: Display the selected artists

1. Add the **Selected Artists Block** to your site. This block will display the list of saved artists.
2. You can customize the display settings of the block through the block layout interface.

### Step 5: View individual artist details

1. Each artist has their own page at `/artist/{artist_id}`.
2. The artist page will show the artist's name, image, and a list of genres associated with them.
3. Only logged-in users can access these artist pages.

## Configuration
- **Spotify API credentials**: The client ID and client secret are configurable in the module settings.
- **Artist Search**: You can search for artists by name and save selected artists to the database.
- **Block**: The block that displays selected artists can be added to your site via **Structure** > **Block layout**.
