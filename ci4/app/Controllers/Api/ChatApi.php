<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Chat API Controller - Lua Proxy
 *
 * This controller acts as a proxy to the Lua/OpenResty chat API.
 * All chat operations are handled by Lua for maximum performance.
 * CodeIgniter is only used for UI rendering.
 *
 * Routes handled:
 * - GET /api/chat/channels - List channels (proxied to Lua)
 * - POST /api/chat/channels - Create channel (proxied to Lua)
 * - GET /api/chat/messages - Get messages (proxied to Lua)
 * - POST /api/chat/messages - Send message (proxied to Lua)
 */
class ChatApi extends BaseController
{
    protected $luaApiBaseUrl;
    protected $currentUser;
    protected $authToken;

    public function __construct()
    {
        parent::__construct();
        // Lua API runs on the same nginx server
        $this->luaApiBaseUrl = 'http://workerra-ci-nginx';
    }

    /**
     * Initialize controller - called after request is available
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Must call parent's initController first
        parent::initController($request, $response, $logger);

        // Skip authentication for health check endpoint
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (strpos($path, '/health') !== false) {
            return; // No authentication required for health check
        }

        // Get the JWT token from the request
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->authToken = $matches[1];
        }

        // Validate token (optional - Lua will also validate)
        $authResult = $this->authenticateJWT();

        if ($authResult !== true && $authResult !== null) {
            // Authentication failed, response is already set
            return;
        }
    }

    /**
     * Authenticate user from JWT Bearer token
     *
     * @return bool|ResponseInterface True if authenticated, Response object if failed
     */
    protected function authenticateJWT()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->response->setStatusCode(401);
            $this->response->setJSON([
                'success' => false,
                'message' => 'Missing or invalid Authorization header'
            ]);
            return $this->response;
        }

        $token = $matches[1];

        try {
            $secret  = Services::getSecretKey();
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            // Set current user from token
            $this->currentUser = [
                'uuid'             => $decoded->uuid ?? null,
                'email'            => $decoded->email ?? null,
                'name'             => $decoded->name ?? null,
                'role'             => $decoded->role ?? null,
                'uuid_business_id' => $decoded->uuid_business_id ?? null,
            ];

            if (empty($this->currentUser['uuid'])) {
                $this->response->setStatusCode(401);
                $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid token payload'
                ]);
                return $this->response;
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'JWT authentication failed: ' . $e->getMessage());
            $this->response->setStatusCode(401);
            $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid or expired token: ' . $e->getMessage()
            ]);
            return $this->response;
        }
    }

    /**
     * Proxy HTTP request to Lua API
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $endpoint Lua API endpoint
     * @param array|null $data Request body data
     * @param array $queryParams Query parameters
     * @return ResponseInterface
     */
    protected function proxyToLua($method, $endpoint, $data = null, $queryParams = [])
    {
        try {
            $url = $this->luaApiBaseUrl . $endpoint;

            // Add query parameters
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }

            $client = \Config\Services::curlrequest();

            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authToken,
                    'Content-Type' => 'application/json',
                ],
                'http_errors' => false, // Don't throw exceptions on HTTP errors
                'timeout' => 10,
            ];

            if ($data !== null) {
                $options['json'] = $data;
            }

            $response = $client->request($method, $url, $options);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            // Try to decode JSON
            $jsonData = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->response
                    ->setStatusCode($statusCode)
                    ->setJSON($jsonData);
            } else {
                // Not JSON, return as-is
                return $this->response
                    ->setStatusCode($statusCode)
                    ->setBody($body);
            }
        } catch (\Exception $e) {
            log_message('error', 'Lua API proxy error: ' . $e->getMessage());
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'Failed to communicate with chat service: ' . $e->getMessage()
                ]);
        }
    }

    /**
     * GET /api/chat/channels
     *
     * List all channels accessible to the current user
     */
    public function channels()
    {
        $method = strtolower($this->request->getMethod());

        if ($method === 'get') {
            return $this->listChannels();
        }

        if ($method === 'post') {
            return $this->createChannel();
        }

        return $this->response
            ->setStatusCode(405)
            ->setJSON([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
    }

    /**
     * List channels - Proxied to Lua
     */
    protected function listChannels()
    {
        return $this->proxyToLua('GET', '/api/chat/channels');
    }

    /**
     * Create new channel - Proxied to Lua
     */
    protected function createChannel()
    {
        $data = $this->request->getJSON(true);
        return $this->proxyToLua('POST', '/api/chat/channels', $data);
    }

    /**
     * GET /api/chat/messages
     *
     * Get messages for a channel
     */
    public function messages()
    {
        $method = strtolower($this->request->getMethod());

        if ($method === 'get') {
            return $this->listMessages();
        }

        if ($method === 'post') {
            return $this->sendMessage();
        }

        return $this->response
            ->setStatusCode(405)
            ->setJSON([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
    }

    /**
     * List messages - Proxied to Lua
     */
    protected function listMessages()
    {
        $queryParams = [
            'channel_uuid' => $this->request->getGet('channel_uuid'),
            'limit' => $this->request->getGet('limit') ?? 50,
            'offset' => $this->request->getGet('offset') ?? 0,
        ];

        return $this->proxyToLua('GET', '/api/chat/messages', null, $queryParams);
    }

    /**
     * Send message - Proxied to Lua
     */
    protected function sendMessage()
    {
        $data = $this->request->getJSON(true);
        return $this->proxyToLua('POST', '/api/chat/messages', $data);
    }

    /**
     * GET /api/chat/health
     *
     * Health check endpoint - Proxied to Lua
     */
    public function health()
    {
        try {
            // Check Lua API health
            return $this->proxyToLua('GET', '/api/chat/health');
        } catch (\Exception $e) {
            // Fallback if Lua is not available
            return $this->response
                ->setJSON([
                    'success' => false,
                    'status' => 'error',
                    'service' => 'chat',
                    'version' => '1.0.0',
                    'implementation' => 'CodeIgniter Proxy',
                    'lua_status' => 'unavailable',
                    'error' => $e->getMessage(),
                    'timestamp' => time(),
                ]);
        }
    }
}
