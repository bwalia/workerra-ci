<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Chat Controller
 *
 * Handles chat interface and JWT token generation for Lua chat API
 * Follows the same session/authentication pattern as other controllers in the system
 *
 * @package App\Controllers
 * @author  Workstation CRM Team
 */
class Chat extends BaseController
{
    /**
     * @var \CodeIgniter\Session\Session
     */
    protected $session;

    /**
     * @var string Business UUID for multi-tenancy
     */
    protected $businessUuid;

    /**
     * Constructor
     *
     * Initializes session and checks authentication following the same pattern
     * as CommonController and Dashboard
     */
    public function __construct()
    {
        parent::__construct();
        $this->session = Services::session();

        // Check if user is logged in (same pattern as CommonController and Dashboard)
        if (! $this->session->get('uuid')) {
            header('Location:/');
            die();
        }

        // Get business UUID for multi-tenancy (same pattern as all controllers)
        $this->businessUuid = session('uuid_business');
    }

    /**
     * Display chat interface
     *
     * Main entry point for the chat UI. Generates JWT token from session data
     * and passes it to the view for API authentication.
     *
     * @return string View output
     */
    public function index()
    {
        // Get user data from session (following the exact pattern from Home controller login)
        $userData = [
            'id'               => $this->session->get('uuid'),              // User ID
            'uuid'             => $this->session->get('userUuid'),          // User UUID
            'name'             => $this->session->get('uname'),             // User name
            'email'            => $this->session->get('uemail'),            // User email
            'role'             => $this->session->get('role'),              // User role (1=user, 2=admin)
            'profile_img'      => $this->session->get('profile_img'),       // Profile image
            'uuid_business_id' => $this->session->get('uuid_business_id'),  // Business UUID
            'uuid_business'    => $this->businessUuid,                      // Alternative business UUID
            'permissions'      => $this->session->get('permissions') ?? [], // User permissions array
        ];

        // Generate JWT token for this user session (for Lua API authentication)
        $jwtToken = $this->generateJWTToken($userData);

        // Prepare data for view
        $data = [
            'title'        => 'Chat',
            'user'         => $userData,
            'jwt_token'    => $jwtToken,
            'api_base_url' => base_url('api/chat'),
            'ws_url'       => $this->getWebSocketUrl(),
        ];

        return view('chat/index', $data);
    }

    /**
     * Generate JWT token for user
     *
     * Uses the same JWT secret as the main application (Services::getSecretKey())
     * Token format matches what Lua middleware expects for authentication
     *
     * @param array $user User data array
     * @return string JWT token
     */
    private function generateJWTToken(array $user): string
    {
        // Get JWT secret from Services (same as used in getSignedJWTForUser helper)
        $secret = Services::getSecretKey();

        // JWT Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        // JWT Payload - includes all user data needed by Lua middleware
        // NOTE: We don't include the full permissions array to keep the token size small
        $payload = json_encode([
                                                  // Standard JWT claims
            'sub'              => $user['uuid'],  // Subject (user UUID)
            'iat'              => time(),         // Issued at
            'exp'              => time() + 86400, // Expires in 24 hours

                                                             // Custom claims for chat system (matches Lua auth middleware expectations)
            'uuid'             => $user['uuid'],             // User UUID
            'email'            => $user['email'],            // User email
            'name'             => $user['name'],             // User name
            'role'             => (int) $user['role'],       // User role (convert to int)
            'uuid_business_id' => $user['uuid_business_id'], // Business UUID for multi-tenancy

            // Don't include full permissions array - it's too large for headers
            // The Lua middleware will fetch user from database which has permissions
        ]);

        // Encode Header to Base64URL
        $base64UrlHeader = $this->base64UrlEncode($header);

        // Encode Payload to Base64URL
        $base64UrlPayload = $this->base64UrlEncode($payload);

        // Create Signature using HMAC-SHA256
        $signature          = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // Create JWT (header.payload.signature)
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }

    /**
     * Base64 URL encode
     *
     * Standard base64url encoding as per JWT specification (RFC 7519)
     * Replaces + with -, / with _, and removes padding =
     *
     * @param string $data Data to encode
     * @return string Base64URL encoded string
     */
    private function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get WebSocket URL
     *
     * Converts HTTP/HTTPS base URL to WS/WSS for WebSocket connection
     *
     * @return string WebSocket URL
     */
    private function getWebSocketUrl(): string
    {
        $baseUrl = base_url();
        // Convert http:// to ws:// and https:// to wss://
        $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $baseUrl);
        return rtrim($wsUrl, '/') . '/ws/chat';
    }

    /**
     * API: Get current user's JWT token
     *
     * Allows frontend to fetch a fresh token if needed
     * Useful for token refresh scenarios
     *
     * @return ResponseInterface JSON response with token
     */
    public function getToken()
    {
        // Check authentication
        if (! $this->session->get('uuid')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // Get user data from session
        $userData = [
            'uuid'             => $this->session->get('userUuid'),
            'name'             => $this->session->get('uname'),
            'email'            => $this->session->get('uemail'),
            'role'             => $this->session->get('role'),
            'permissions'      => $this->session->get('permissions') ?? [],
            'uuid_business_id' => $this->session->get('uuid_business_id'),
        ];

        // Generate fresh token
        $token = $this->generateJWTToken($userData);

        // Return token with user info
        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'token'      => $token,
                'expires_in' => 86400,
                'user'       => [
                    'uuid'  => $userData['uuid'],
                    'name'  => $userData['name'],
                    'email' => $userData['email'],
                    'role'  => $userData['role'],
                ],
            ],
        ]);
    }

    /**
     * API: Refresh JWT token
     *
     * Extends token expiration for active users
     * Should be called before token expires to maintain continuous access
     *
     * @return ResponseInterface JSON response with new token
     */
    public function refreshToken()
    {
        // Check authentication
        if (! $this->session->get('uuid')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ])->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        // Get user data from session
        $userData = [
            'uuid'             => $this->session->get('userUuid'),
            'name'             => $this->session->get('uname'),
            'email'            => $this->session->get('uemail'),
            'role'             => $this->session->get('role'),
            'permissions'      => $this->session->get('permissions') ?? [],
            'uuid_business_id' => $this->session->get('uuid_business_id'),
        ];

        // Generate fresh token with extended expiration
        $token = $this->generateJWTToken($userData);

        // Return new token
        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'token'      => $token,
                'expires_in' => 86400,
            ],
        ]);
    }

    /**
     * Debug endpoint to view session data
     *
     * Useful for development and debugging
     * Should be removed or protected in production
     *
     * @return ResponseInterface JSON response with session data
     */
    public function debug()
    {
        // Check authentication
        if (! $this->session->get('uuid')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated',
            ]);
        }

        // Collect session data (safely, without exposing sensitive info)
        $sessionData = [
            'uuid'             => $this->session->get('uuid'),
            'userUuid'         => $this->session->get('userUuid'),
            'uname'            => $this->session->get('uname'),
            'uemail'           => $this->session->get('uemail'),
            'role'             => $this->session->get('role'),
            'uuid_business_id' => $this->session->get('uuid_business_id'),
            'uuid_business'    => $this->session->get('uuid_business'),
            'has_permissions'  => ! empty($this->session->get('permissions')),
            'permission_count' => is_array($this->session->get('permissions')) ? count($this->session->get('permissions')) : 0,
            'jwt_token_exists' => ! empty($this->session->get('jwt_token')),
        ];

        // Generate sample JWT for testing
        $testJwt = $this->generateJWTToken([
            'uuid'             => $sessionData['userUuid'],
            'email'            => $sessionData['uemail'],
            'name'             => $sessionData['uname'],
            'role'             => $sessionData['role'],
            'permissions'      => $this->session->get('permissions') ?? [],
            'uuid_business_id' => $sessionData['uuid_business_id'],
        ]);

        return $this->response->setJSON([
            'success'               => true,
            'session'               => $sessionData,
            'generated_jwt'         => $testJwt,
            'jwt_secret_configured' => ! empty(Services::getSecretKey()),
        ]);
    }
}
