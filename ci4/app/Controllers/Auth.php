<?php
//https://www.twilio.com/blog/create-secured-restful-api-codeigniter-php
namespace App\Controllers;

use App\Models\Core\Common_model;
use App\Models\Users_model;
use App\Models\Contact;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ReflectionException;
use Config\Services;

class Auth extends BaseController
{
    /**
     * API endpoint to generate a JWT token for API access (enquiries, contacts, customers, etc.)
     * POST /api/v2/token
     * Body: { "email": "...", "password": "...", "type": "contacts|customers|enquiries|users|employees" }
     */
    public function apiToken()
    {
        $input = $this->getRequestInput($this->request);
        $type = isset($input['type']) ? $input['type'] : 'contacts';
        $email = isset($input['email']) ? $input['email'] : null;
        $password = isset($input['password']) ? $input['password'] : null;
        if (!$email || !$password) {
            return $this->getResponse([
                'error' => 'Email and password are required.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
        return $this->getJWTForUser($email, $password, $type);
    }

    /**
     * Register a new user
     * @return Response
     * @throws ReflectionException
     */
    /* public function register()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[user.email]',
            'password' => 'required|min_length[8]|max_length[255]'
        ];

 $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }

        $userModel = new Users_model();
       $userModel->save($input);
     

       

return $this
            ->getJWTForUser(
                $input['email'],
                ResponseInterface::HTTP_CREATED
            );

    } */

    /**
     * Authenticate Existing User
     * @return Response
     */
    public function login()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
            'password' => 'required|min_length[3]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput($this->request);


        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }
        return $this->getJWTForUser($input['email'], $input['password'], "contacts");
    }

    public function user_login()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
            'password' => 'required|min_length[3]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput($this->request);


        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }
        return $this->getJWTForUser($input['email'], $input['password'], "users");
    }
    public function employee_login()
    {
        $rules = [
            'email' => 'required|min_length[6]|max_length[50]|valid_email',
            'password' => 'required|min_length[3]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput($this->request);

        if (!$this->validateRequest($input, $rules, $errors)) {
            return $this
                ->getResponse(
                    $this->validator->getErrors(),
                    ResponseInterface::HTTP_BAD_REQUEST
                );
        }
        return $this->getJWTForUser($input['email'], $input['password'], "employees");
    }

    private function getJWTForUser(
        string $emailAddress,
        string $password,
        string $loginType,
        int $responseCode = ResponseInterface::HTTP_OK
    ) {
        try {
            $model = new Common_model();
            $user = $model->findByEmailAddress($loginType, $emailAddress);
            
            if ($user['password'] != md5($password)) {
                return $this
                    ->getResponse(
                        [
                            'error' => 'Password Not match',
                        ],
                        ResponseInterface::HTTP_FORBIDDEN
                    );
            }
            if ($loginType == "contacts") {
                if ($user['allow_web_access'] != '1') {
                    return $this
                        ->getResponse(
                            [
                                'error' => 'User do do have access to webpage!',
                            ],
                            ResponseInterface::HTTP_FORBIDDEN
                        );
                }
            }

            unset($user['password']);

            helper('jwt');

            return $this
                ->getResponse(
                    [
                        'message' => 'User authenticated successfully',
                        'user' => $user,
                        'access_token' => getSignedJWTForUser($emailAddress)
                    ],
                    $responseCode
                );
        } catch (Exception $exception) {
            return $this
                ->getResponse(
                    [
                        'error' => $exception->getMessage(),
                    ],
                    ResponseInterface::HTTP_FORBIDDEN
                );
        }
    }


    public function googleLogin()
    {
        $oidc = Services::openIDConnect();
        $oidc->authenticate();

        $userInfo = $oidc->requestUserInfo();

        session()->set('user', [
            'id' => $userInfo->sub,
            'name' => $userInfo->name,
            'email' => $userInfo->email,
        ]);

        return redirect()->to('/dashboard');
    }

    public function callback()
    {
        $oidc = Services::openIDConnect();

        // Handle the callback from Keycloak
        if ($oidc->authenticate()) {
            return redirect()->to('/dashboard');
        }

        return redirect()->to('/login')->with('error', 'Authentication failed.');
    }

    public function googleLogout()
    {
        $oidc = Services::openIDConnect();

        // Destroy session
        session()->destroy();

        // Redirect to Keycloak's logout URL
        return redirect()->to($oidc->getProviderURL() . '/protocol/openid-connect/logout?redirect_uri=' . urlencode(base_url()));
    }
}
