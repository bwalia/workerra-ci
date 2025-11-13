<?php

namespace App\Filters;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class JWTAuthenticationFilter implements FilterInterface
{
    use ResponseTrait;

    public function before(RequestInterface $request, $arguments = null)
    {
        // echo '<pre>'; print_r($_SERVER); echo '</pre>'; die;
        $pos = strpos($_SERVER['REQUEST_URI'], "api/sendEmail") ?? false;
        $ping = strpos($_SERVER['REQUEST_URI'], "api/v1/ping") ?? false;
        $token = strpos($_SERVER['REQUEST_URI'], "api/v2/token") ?? false;
        $enquiry = strpos($_SERVER['REQUEST_URI'], "api/v2/enquiries") ?? false;
        $publicBlog = $this->isMatchingPattern($_SERVER['REQUEST_URI']);
        $method = $_SERVER['REQUEST_METHOD'];

        if ($pos  ===  false && $ping  ===  false && $token  ===  false && $publicBlog === false && ($enquiry === false || $method === "POST")) {

            $authenticationHeader = $request->getServer('HTTP_AUTHORIZATION');

            // If no Authorization header, check for valid session authentication
            if (is_null($authenticationHeader)) {
                $session = Services::session();
                if ($session->has('uuid') && $session->has('uuid_business')) {
                    // User is authenticated via session, allow the request
                    return $request;
                }
            }

            try {
                helper('jwt');
                $encodedToken = getJWTFromRequest($authenticationHeader);
                validateJWTFromRequest($encodedToken);
                return $request;
            } catch (Exception $e) {
                return Services::response()
                    ->setJSON(
                        [
                            'error' => $e->getMessage()
                        ]
                    )
                    ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
            }
        }
    }

    function isMatchingPattern($string) {
        $patterns = [
            '/^\/api\/v2\/business\/[^\/]+\/public\/blogs+(\?.*)?$/',
            '/^\/api\/v2\/business\/[^\/]+\/public\/blog\/[^\/]+$/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return true;
            }
        }
        return false;
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
    }
}
