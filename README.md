# Instagram Client

A simple PHP client for InstagramAPI.

## Setup with Docker

1. Build and run the container (dependencies are installed automatically):
```bash
docker-compose up -d --build
```

2. Run the examples:
```bash
# Console examples
docker-compose exec php php index.php

# Or visit web examples at: http://localhost:8080/web-examples.php
```

## Manual Setup

1. Install dependencies:
```bash
composer install
```

2. Run the example:
```bash
php example.php
```

## Usage

```php
use InstagramClient\InstagramClient;

$client = new InstagramClient('YOUR_ACCESS_TOKEN');

// Get user profile
$profile = $client->getUserProfile();

// Get user media
$media = $client->getUserMedia();
```

## Methods

- `getUserProfile()` - Get user's profile information
- `getUserMedia($limit = 25)` - Get user's media posts
- `getMediaDetails($mediaId)` - Get details of a specific media item
- `refreshAccessToken($refreshToken)` - Refresh the access token
