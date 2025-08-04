<?php

/**
 * Web Examples for Instagram Client
 * 
 * This file demonstrates various ways to use the Instagram Client in web applications
 * 
 * Run with PHP built-in server:
 * php -S localhost:8080 web-examples.php
 * 
 * Or with Docker:
 * docker-compose up -d
 * Then visit: http://localhost:8080/web-examples.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use InstagramClient\InstagramClient;
use InstagramClient\Exception\InstagramClientException;

// Configuration
$accessToken = 'XXXXXXXXXXXXXXXXXXXXXX';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $instagram = new InstagramClient($accessToken);
        
        switch ($_GET['action']) {
            case 'profile':
                echo json_encode(['success' => true, 'data' => $instagram->getUserProfile()]);
                break;
                
            case 'media':
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
                echo json_encode(['success' => true, 'data' => $instagram->getUserMedia($limit)]);
                break;
                
            case 'media_details':
                if (!isset($_GET['media_id'])) {
                    throw new Exception('Media ID required');
                }
                $details = $instagram->getMediaDetails($_GET['media_id']);
                echo json_encode(['success' => true, 'data' => $details]);
                break;
                
            case 'token_info':
                echo json_encode(['success' => true, 'data' => $instagram->getTokenInfo()]);
                break;
                
            case 'analytics':
                $media = $instagram->getUserMedia(50);
                $analytics = analyzeMedia($media['data'] ?? []);
                echo json_encode(['success' => true, 'data' => $analytics]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        
    } catch (InstagramClientException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'type' => 'instagram_error']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'type' => 'general_error']);
    }
    
    exit;
}

// Analytics function
function analyzeMedia($posts) {
    if (empty($posts)) return [];
    
    $mediaTypes = [];
    $postsByMonth = [];
    $captionStats = ['lengths' => [], 'with_caption' => 0, 'without_caption' => 0];
    $recentPosts = 0;
    $weekAgo = strtotime('-1 week');
    
    foreach ($posts as $post) {
        // Media types
        $type = $post['media_type'];
        $mediaTypes[$type] = ($mediaTypes[$type] ?? 0) + 1;
        
        // Posts by month
        $month = date('M Y', strtotime($post['timestamp']));
        $postsByMonth[$month] = ($postsByMonth[$month] ?? 0) + 1;
        
        // Caption analysis
        if (!empty($post['caption'])) {
            $captionStats['lengths'][] = strlen($post['caption']);
            $captionStats['with_caption']++;
        } else {
            $captionStats['without_caption']++;
        }
        
        // Recent posts
        if (strtotime($post['timestamp']) > $weekAgo) {
            $recentPosts++;
        }
    }
    
    // Calculate caption statistics
    $avgCaptionLength = !empty($captionStats['lengths']) 
        ? round(array_sum($captionStats['lengths']) / count($captionStats['lengths']))
        : 0;
    
    return [
        'total_posts' => count($posts),
        'media_types' => $mediaTypes,
        'posts_by_month' => array_slice($postsByMonth, -6, 6, true), // Last 6 months
        'caption_stats' => [
            'average_length' => $avgCaptionLength,
            'with_caption' => $captionStats['with_caption'],
            'without_caption' => $captionStats['without_caption']
        ],
        'recent_activity' => [
            'posts_last_week' => $recentPosts,
            'avg_per_week' => round($recentPosts)
        ]
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Client - Web Examples</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-size: 2.5em;
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .tab-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .tab {
            flex: 1;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab.active {
            color: #e1306c;
            background: white;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(45deg, #f09433, #e1306c);
        }

        .tab-content {
            display: none;
            padding: 30px;
            min-height: 400px;
        }

        .tab-content.active {
            display: block;
        }

        .example-section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 5px solid #e1306c;
        }

        .example-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.5;
            overflow-x: auto;
            margin: 15px 0;
            position: relative;
        }

        .code-block::before {
            content: 'PHP';
            position: absolute;
            top: 10px;
            right: 15px;
            background: #e1306c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .btn {
            background: linear-gradient(45deg, #f09433, #e1306c);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 10px 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(225, 48, 108, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .result-box {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e1306c;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            margin: 15px 0;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            margin: 15px 0;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .media-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .media-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .media-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .media-item-info {
            padding: 10px;
            font-size: 12px;
            color: #666;
        }

        .badge {
            display: inline-block;
            background: #e1306c;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin: 2px;
        }

        .analytics-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #e1306c;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .chart-bar {
            background: #e9ecef;
            height: 20px;
            border-radius: 10px;
            margin: 8px 0;
            position: relative;
            overflow: hidden;
        }

        .chart-fill {
            background: linear-gradient(45deg, #f09433, #e1306c);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .json-viewer {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 13px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📸 Instagram Client</h1>
            <p>Web Examples & Interactive Demo</p>
            <p style="margin-top: 10px; color: #666; font-size: 0.9em;">
                Comprehensive examples showing how to integrate Instagram Basic Display API in web applications
            </p>
        </div>

        <div class="tab-container">
            <div class="tabs">
                <button class="tab active" onclick="showTab('basic')">🏠 Basic Usage</button>
                <button class="tab" onclick="showTab('media')">📱 Media Gallery</button>
                <button class="tab" onclick="showTab('analytics')">📊 Analytics</button>
                <button class="tab" onclick="showTab('advanced')">⚙️ Advanced</button>
                <button class="tab" onclick="showTab('code')">💻 Code Examples</button>
            </div>

            <!-- Basic Usage Tab -->
            <div id="basic" class="tab-content active">
                <div class="example-section">
                    <div class="example-title">🔗 Get User Profile</div>
                    <p>Retrieve basic profile information including username, account type, and media count.</p>
                    <button class="btn" onclick="loadProfile()">Load Profile</button>
                    <div id="profile-result" class="result-box"></div>
                </div>

                <div class="example-section">
                    <div class="example-title">🔑 Access Token Information</div>
                    <p>Check your access token status, expiration date, and validity.</p>
                    <button class="btn" onclick="loadTokenInfo()">Check Token</button>
                    <div id="token-result" class="result-box"></div>
                </div>

                <div class="example-section">
                    <div class="example-title">📋 Quick Media Overview</div>
                    <p>Get a quick overview of your recent posts (last 5 posts).</p>
                    <button class="btn" onclick="loadQuickMedia()">Load Recent Posts</button>
                    <div id="quick-media-result" class="result-box"></div>
                </div>
            </div>

            <!-- Media Gallery Tab -->
            <div id="media" class="tab-content">
                <div class="example-section">
                    <div class="example-title">🖼️ Interactive Media Gallery</div>
                    <p>Browse through your Instagram posts in a beautiful grid layout.</p>
                    
                    <div style="margin: 20px 0;">
                        <label>Number of posts to load: </label>
                        <select id="media-limit" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                            <option value="6">6 posts</option>
                            <option value="12" selected>12 posts</option>
                            <option value="24">24 posts</option>
                            <option value="50">50 posts</option>
                        </select>
                        <button class="btn" onclick="loadMediaGallery()">Load Gallery</button>
                    </div>
                    
                    <div id="media-gallery"></div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" class="tab-content">
                <div class="example-section">
                    <div class="example-title">📈 Instagram Analytics Dashboard</div>
                    <p>Analyze your Instagram posting patterns, media types, and engagement metrics.</p>
                    <button class="btn" onclick="loadAnalytics()">Generate Analytics</button>
                    <div id="analytics-result"></div>
                </div>
            </div>

            <!-- Advanced Tab -->
            <div id="advanced" class="tab-content">
                <div class="example-section">
                    <div class="example-title">🔍 Media Detail Inspector</div>
                    <p>Get detailed information about a specific media post, including carousel items.</p>
                    
                    <div style="margin: 20px 0;">
                        <input type="text" id="media-id-input" placeholder="Enter Media ID (e.g., from gallery)" 
                               style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 300px;">
                        <button class="btn" onclick="loadMediaDetails()">Inspect Media</button>
                    </div>
                    
                    <div id="media-details-result" class="result-box"></div>
                </div>

                <div class="example-section">
                    <div class="example-title">💾 Export Data</div>
                    <p>Export your Instagram data as JSON for backup or further analysis.</p>
                    <button class="btn" onclick="exportData()">Export to JSON</button>
                    <div id="export-result" class="result-box"></div>
                </div>

                <div class="example-section">
                    <div class="example-title">🔄 Token Management</div>
                    <p>Refresh your access token to extend its validity.</p>
                    <button class="btn" onclick="refreshToken()">Refresh Token</button>
                    <div id="refresh-result" class="result-box"></div>
                </div>
            </div>

            <!-- Code Examples Tab -->
            <div id="code" class="tab-content">
                <div class="example-section">
                    <div class="example-title">🚀 Basic Implementation</div>
                    <p>Here's how to implement the Instagram client in your PHP application:</p>
                    
                    <div class="code-block"><?php echo htmlspecialchars('<?php
require_once "vendor/autoload.php";

use InstagramClient\InstagramClient;
use InstagramClient\Exception\InstagramClientException;

// Initialize client
$accessToken = "YOUR_ACCESS_TOKEN";
$instagram = new InstagramClient($accessToken);

try {
    // Get user profile
    $profile = $instagram->getUserProfile();
    echo "Username: " . $profile["username"] . "\n";
    echo "Media Count: " . $profile["media_count"] . "\n";
    
    // Get recent media
    $media = $instagram->getUserMedia(10);
    foreach ($media["data"] as $post) {
        echo "Post: " . $post["id"] . " - " . $post["media_type"] . "\n";
    }
    
} catch (InstagramClientException $e) {
    echo "Error: " . $e->getMessage();
}'); ?></div>
                </div>

                <div class="example-section">
                    <div class="example-title">🌐 AJAX Integration</div>
                    <p>Example of how to use the client with AJAX requests:</p>
                    
                    <div class="code-block"><?php echo htmlspecialchars('// JavaScript (Frontend)
async function loadInstagramData() {
    try {
        const response = await fetch("api.php?action=media&limit=12");
        const data = await response.json();
        
        if (data.success) {
            displayMedia(data.data);
        } else {
            console.error("Error:", data.error);
        }
    } catch (error) {
        console.error("Network error:", error);
    }
}

// PHP (Backend - api.php)
if ($_GET["action"] === "media") {
    $instagram = new InstagramClient($accessToken);
    $limit = $_GET["limit"] ?? 12;
    
    try {
        $media = $instagram->getUserMedia($limit);
        echo json_encode(["success" => true, "data" => $media]);
    } catch (InstagramClientException $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}'); ?></div>
                </div>

                <div class="example-section">
                    <div class="example-title">⚡ Error Handling Best Practices</div>
                    <p>Comprehensive error handling for production applications:</p>
                    
                    <div class="code-block"><?php echo htmlspecialchars('try {
    $instagram = new InstagramClient($accessToken);
    $profile = $instagram->getUserProfile();
    
} catch (InstagramClientException $e) {
    // Handle Instagram-specific errors
    if (strpos($e->getMessage(), "access token") !== false) {
        // Token expired - redirect to re-authentication
        header("Location: /auth/instagram");
        exit;
    } else {
        // Other API errors - log and show user-friendly message
        error_log("Instagram API Error: " . $e->getMessage());
        $error = "Unable to load Instagram data. Please try again later.";
    }
    
} catch (Exception $e) {
    // Handle unexpected errors
    error_log("Unexpected error: " . $e->getMessage());
    $error = "An unexpected error occurred.";
}'); ?></div>
                </div>

                <div class="example-section">
                    <div class="example-title">🔧 Advanced Configuration</div>
                    <p>Custom configuration and dependency injection:</p>
                    
                    <div class="code-block"><?php echo htmlspecialchars('use GuzzleHttp\Client;

// Custom HTTP client with specific configuration
$httpClient = new Client([
    "timeout" => 60,
    "headers" => [
        "User-Agent" => "MyApp/1.0"
    ],
    "proxy" => "http://proxy.example.com:8080" // If needed
]);

// Initialize with custom client
$instagram = new InstagramClient($accessToken, $httpClient);

// Or configure different endpoints for testing
class TestInstagramClient extends InstagramClient {
    protected const BASE_URL = "https://api-test.instagram.com";
}'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // API functions
        async function apiRequest(action, params = {}) {
            const url = new URL(window.location.href);
            url.searchParams.set('action', action);
            
            Object.keys(params).forEach(key => {
                url.searchParams.set(key, params[key]);
            });
            
            try {
                const response = await fetch(url.toString());
                return await response.json();
            } catch (error) {
                return { success: false, error: 'Network error: ' + error.message };
            }
        }

        function showLoading(elementId) {
            document.getElementById(elementId).innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>
            `;
        }

        function showError(elementId, message) {
            document.getElementById(elementId).innerHTML = `
                <div class="error">
                    <strong>❌ Error:</strong> ${message}
                </div>
            `;
        }

        function showSuccess(elementId, content) {
            document.getElementById(elementId).innerHTML = content;
        }

        // Profile loading
        async function loadProfile() {
            showLoading('profile-result');
            
            const result = await apiRequest('profile');
            
            if (result.success) {
                const profile = result.data;
                showSuccess('profile-result', `
                    <div class="success">✅ Profile loaded successfully!</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div class="analytics-card">
                            <div class="stat-number">${profile.media_count}</div>
                            <div class="stat-label">Total Posts</div>
                        </div>
                        <div class="analytics-card">
                            <div style="font-size: 1.5em; font-weight: bold; color: #e1306c;">@${profile.username}</div>
                            <div class="stat-label">Username</div>
                        </div>
                        <div class="analytics-card">
                            <div style="font-size: 1.2em; font-weight: bold; color: #6c757d;">${profile.account_type}</div>
                            <div class="stat-label">Account Type</div>
                        </div>
                    </div>
                    <div class="json-viewer" style="margin-top: 15px;">
                        <strong>Raw JSON Response:</strong><br>
                        ${JSON.stringify(profile, null, 2)}
                    </div>
                `);
            } else {
                showError('profile-result', result.error);
            }
        }

        // Token info loading
        async function loadTokenInfo() {
            showLoading('token-result');
            
            const result = await apiRequest('token_info');
            
            if (result.success) {
                const tokenInfo = result.data;
                const expiresIn = tokenInfo.expires_in;
                const expiresInDays = Math.round(expiresIn / 86400);
                const expirationDate = new Date(Date.now() + expiresIn * 1000).toLocaleString();
                
                let statusClass = 'success';
                let statusIcon = '✅';
                let statusText = 'Token is valid';
                
                if (expiresInDays < 7) {
                    statusClass = 'error';
                    statusIcon = '⚠️';
                    statusText = 'Token expires soon!';
                }
                
                showSuccess('token-result', `
                    <div class="${statusClass}">${statusIcon} ${statusText}</div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div class="analytics-card">
                            <div class="stat-number">${expiresInDays}</div>
                            <div class="stat-label">Days Until Expiration</div>
                        </div>
                        <div class="analytics-card">
                            <div style="font-size: 1em; font-weight: bold; color: #6c757d;">${expirationDate}</div>
                            <div class="stat-label">Expires At</div>
                        </div>
                    </div>
                    <div class="json-viewer" style="margin-top: 15px;">
                        <strong>Raw JSON Response:</strong><br>
                        ${JSON.stringify(tokenInfo, null, 2)}
                    </div>
                `);
            } else {
                showError('token-result', result.error);
            }
        }

        // Quick media loading
        async function loadQuickMedia() {
            showLoading('quick-media-result');
            
            const result = await apiRequest('media', { limit: 5 });
            
            if (result.success && result.data.data) {
                const posts = result.data.data;
                let html = '<div class="success">✅ Recent posts loaded!</div>';
                
                posts.forEach((post, index) => {
                    const date = new Date(post.timestamp).toLocaleDateString();
                    const caption = post.caption ? (post.caption.length > 100 ? 
                        post.caption.substring(0, 100) + '...' : post.caption) : 'No caption';
                    
                    html += `
                        <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong>Post ${index + 1}</strong>
                                <span class="badge">${post.media_type}</span>
                            </div>
                            <div style="color: #666; font-size: 0.9em; margin-bottom: 8px;">
                                📅 ${date} | 🆔 ${post.id}
                            </div>
                            <div style="margin-bottom: 10px;">${caption}</div>
                            <a href="${post.permalink}" target="_blank" style="color: #e1306c; text-decoration: none;">
                                View on Instagram →
                            </a>
                        </div>
                    `;
                });
                
                showSuccess('quick-media-result', html);
            } else {
                showError('quick-media-result', result.error || 'No posts found');
            }
        }

        // Media gallery loading
        async function loadMediaGallery() {
            const limit = document.getElementById('media-limit').value;
            const galleryElement = document.getElementById('media-gallery');
            
            galleryElement.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading ${limit} posts...</p>
                </div>
            `;
            
            const result = await apiRequest('media', { limit: limit });
            
            if (result.success && result.data.data) {
                const posts = result.data.data;
                let html = `<div class="success">✅ Loaded ${posts.length} posts</div><div class="media-grid">`;
                
                posts.forEach(post => {
                    const mediaUrl = post.media_type === 'VIDEO' ? 
                        (post.thumbnail_url || post.media_url) : post.media_url;
                    
                    html += `
                        <div class="media-item" onclick="showMediaDetails('${post.id}')">
                            <img src="${mediaUrl}" alt="Instagram post" loading="lazy">
                            <div class="media-item-info">
                                <div class="badge">${post.media_type}</div>
                                <div style="margin-top: 5px; font-size: 11px;">
                                    ${new Date(post.timestamp).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                galleryElement.innerHTML = html;
            } else {
                showError('media-gallery', result.error || 'No posts found');
            }
        }

        // Show media details
        function showMediaDetails(mediaId) {
            document.getElementById('media-id-input').value = mediaId;
            showTab('advanced');
            setTimeout(() => loadMediaDetails(), 100);
        }

        // Load media details
        async function loadMediaDetails() {
            const mediaId = document.getElementById('media-id-input').value.trim();
            
            if (!mediaId) {
                showError('media-details-result', 'Please enter a media ID');
                return;
            }
            
            showLoading('media-details-result');
            
            const result = await apiRequest('media_details', { media_id: mediaId });
            
            if (result.success) {
                const details = result.data;
                let html = '<div class="success">✅ Media details loaded!</div>';
                
                // Basic info
                html += `
                    <div class="analytics-grid" style="margin: 20px 0;">
                        <div class="analytics-card">
                            <div style="font-size: 1.2em; font-weight: bold; color: #e1306c;">${details.media_type}</div>
                            <div class="stat-label">Media Type</div>
                        </div>
                        <div class="analytics-card">
                            <div style="font-size: 1.2em; font-weight: bold; color: #6c757d;">@${details.username}</div>
                            <div class="stat-label">Username</div>
                        </div>
                        <div class="analytics-card">
                            <div style="font-size: 1em; font-weight: bold; color: #6c757d;">
                                ${new Date(details.timestamp).toLocaleString()}
                            </div>
                            <div class="stat-label">Posted</div>
                        </div>
                    </div>
                `;
                
                // Caption
                if (details.caption) {
                    html += `
                        <div class="analytics-card">
                            <strong>Caption:</strong>
                            <div style="margin-top: 10px; line-height: 1.4;">${details.caption}</div>
                        </div>
                    `;
                }
                
                // Carousel items
                if (details.children && details.children.data) {
                    html += `
                        <div class="analytics-card">
                            <strong>Carousel Items (${details.children.data.length}):</strong>
                            <div class="media-grid" style="margin-top: 15px;">
                    `;
                    
                    details.children.data.forEach((child, index) => {
                        const childMediaUrl = child.media_type === 'VIDEO' ? 
                            (child.thumbnail_url || child.media_url) : child.media_url;
                        
                        html += `
                            <div class="media-item">
                                <img src="${childMediaUrl}" alt="Carousel item ${index + 1}">
                                <div class="media-item-info">
                                    <div class="badge">${child.media_type}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                }
                
                // Raw JSON
                html += `
                    <div class="json-viewer" style="margin-top: 15px;">
                        <strong>Raw JSON Response:</strong><br>
                        ${JSON.stringify(details, null, 2)}
                    </div>
                `;
                
                showSuccess('media-details-result', html);
            } else {
                showError('media-details-result', result.error);
            }
        }

        // Load analytics
        async function loadAnalytics() {
            showLoading('analytics-result');
            
            const result = await apiRequest('analytics');
            
            if (result.success) {
                const analytics = result.data;
                let html = '<div class="success">✅ Analytics generated successfully!</div>';
                
                // Overview cards
                html += `
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <div class="stat-number">${analytics.total_posts}</div>
                            <div class="stat-label">Total Posts Analyzed</div>
                        </div>
                        <div class="analytics-card">
                            <div class="stat-number">${analytics.recent_activity.posts_last_week}</div>
                            <div class="stat-label">Posts This Week</div>
                        </div>
                        <div class="analytics-card">
                            <div class="stat-number">${analytics.caption_stats.average_length}</div>
                            <div class="stat-label">Avg Caption Length</div>
                        </div>
                    </div>
                `;
                
                // Media types
                if (analytics.media_types) {
                    html += '<div class="analytics-card"><strong>📊 Media Types Distribution:</strong><div style="margin-top: 15px;">';
                    
                    const totalPosts = analytics.total_posts;
                    Object.entries(analytics.media_types).forEach(([type, count]) => {
                        const percentage = Math.round((count / totalPosts) * 100);
                        html += `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                                <span>${type}</span>
                                <span>${count} posts (${percentage}%)</span>
                            </div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: ${percentage}%"></div>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                }
                
                // Posts by month
                if (analytics.posts_by_month) {
                    html += '<div class="analytics-card"><strong>📅 Posts by Month:</strong><div style="margin-top: 15px;">';
                    
                    const maxPosts = Math.max(...Object.values(analytics.posts_by_month));
                    Object.entries(analytics.posts_by_month).forEach(([month, count]) => {
                        const percentage = Math.round((count / maxPosts) * 100);
                        html += `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin: 10px 0;">
                                <span>${month}</span>
                                <span>${count} posts</span>
                            </div>
                            <div class="chart-bar">
                                <div class="chart-fill" style="width: ${percentage}%"></div>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                }
                
                // Caption stats
                html += `
                    <div class="analytics-card">
                        <strong>✍️ Caption Statistics:</strong>
                        <div class="analytics-grid" style="margin-top: 15px;">
                            <div style="text-align: center;">
                                <div class="stat-number" style="font-size: 1.5em;">${analytics.caption_stats.with_caption}</div>
                                <div class="stat-label">Posts with Captions</div>
                            </div>
                            <div style="text-align: center;">
                                <div class="stat-number" style="font-size: 1.5em;">${analytics.caption_stats.without_caption}</div>
                                <div class="stat-label">Posts without Captions</div>
                            </div>
                        </div>
                    </div>
                `;
                
                showSuccess('analytics-result', html);
            } else {
                showError('analytics-result', result.error);
            }
        }

        // Export data
        async function exportData() {
            showLoading('export-result');
            
            // Get both profile and media data
            const [profileResult, mediaResult] = await Promise.all([
                apiRequest('profile'),
                apiRequest('media', { limit: 50 })
            ]);
            
            if (profileResult.success && mediaResult.success) {
                const exportData = {
                    export_date: new Date().toISOString(),
                    profile: profileResult.data,
                    media_count: mediaResult.data.data ? mediaResult.data.data.length : 0,
                    media: mediaResult.data.data || []
                };
                
                // Create download link
                const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const filename = `instagram_export_${new Date().toISOString().split('T')[0]}.json`;
                
                showSuccess('export-result', `
                    <div class="success">✅ Data exported successfully!</div>
                    <div class="analytics-card">
                        <strong>Export Summary:</strong>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li>Profile information: ✅ Included</li>
                            <li>Media posts: ${exportData.media_count} posts</li>
                            <li>Export date: ${new Date().toLocaleString()}</li>
                            <li>File size: ${Math.round(blob.size / 1024)} KB</li>
                        </ul>
                        <a href="${url}" download="${filename}" class="btn" style="text-decoration: none;">
                            💾 Download JSON File
                        </a>
                    </div>
                    <div class="json-viewer" style="margin-top: 15px;">
                        <strong>Preview (first 1000 characters):</strong><br>
                        ${JSON.stringify(exportData, null, 2).substring(0, 1000)}...
                    </div>
                `);
            } else {
                showError('export-result', 'Failed to export data: ' + (profileResult.error || mediaResult.error));
            }
        }

        // Refresh token
        async function refreshToken() {
            showLoading('refresh-result');
            
            // Note: This is a demonstration - actual token refresh would require backend implementation
            showSuccess('refresh-result', `
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7;">
                    <strong>ℹ️ Token Refresh Demo</strong><br>
                    In a real application, token refresh would be handled on the backend.
                    This demo shows how you would implement the UI for this feature.
                </div>
                <div class="analytics-card" style="margin-top: 15px;">
                    <strong>Token Refresh Process:</strong>
                    <ol style="margin: 15px 0; padding-left: 20px;">
                        <li>Check current token expiration</li>
                        <li>Call Instagram API refresh endpoint</li>
                        <li>Update stored token in database/session</li>
                        <li>Update client instance with new token</li>
                        <li>Notify user of successful refresh</li>
                    </ol>
                </div>
            `);
        }

        // Auto-load profile on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProfile();
        });
    </script>
</body>
</html>
