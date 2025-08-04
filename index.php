<?php

require_once __DIR__ . '/vendor/autoload.php';

use InstagramClient\InstagramClient;
use InstagramClient\Exception\InstagramClientException;

$accessToken = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXX';

$instagram = new InstagramClient($accessToken);

// =================================================================
// EXAMPLE 1: Get User Profile
// =================================================================
$profile = $instagram->getUserProfile();

// =================================================================
// EXAMPLE 2: Get Token Information
// =================================================================
$tokenInfo = $instagram->getTokenInfo();

// =================================================================
// EXAMPLE 3: Get User Media (Basic)
// =================================================================
$media = $instagram->getUserMedia(5); // Get last 5 posts

// =================================================================
// EXAMPLE 4: Get Detailed Media Information
// =================================================================
$media = $instagram->getUserMedia(1);
        
// =================================================================
// EXAMPLE 5: Media Statistics and Analysis
// =================================================================
$media = $instagram->getUserMedia(25); // Get more posts for analysis
// =================================================================
// EXAMPLE 6: Token Refresh
// =================================================================
$refreshResult = $instagram->refreshAccessToken();