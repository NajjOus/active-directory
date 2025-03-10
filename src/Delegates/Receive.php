<?php

namespace Magium\ActiveDirectory\Delegates;

use League\OAuth2\Client\Provider\AbstractProvider;
use Magium\ActiveDirectory\ActiveDirectory;
use Magium\ActiveDirectory\Entity;
use Magium\ActiveDirectory\InvalidRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Http\Header\Location; // Change Zend to Laminas here
use Laminas\Http\PhpEnvironment\Response; // Change Zend to Laminas here
use Laminas\Psr7Bridge\Psr7Response; // Change Zend to Laminas here

class Receive
{

    protected $provider;
    protected $request;
    protected $response;
    protected $returnUrl;

    public function __construct(
        ServerRequestInterface $request,
        AbstractProvider $provider,
        ResponseInterface $response,
        $returnUrl
    )
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->response = $response;
        $this->returnUrl = $returnUrl;
    }

    /**
     * @return AbstractProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function execute()
    {
        $params = $this->getRequest()->getQueryParams();
        if (
            !isset($_SESSION[ActiveDirectory::SESSION_KEY]['state'])
            || empty($params['state'])
            || ($params['state'] !== $_SESSION[ActiveDirectory::SESSION_KEY]['state'])
        ) {
            unset($_SESSION[ActiveDirectory::SESSION_KEY]);
            throw new InvalidRequestException('Request state did not match');
        }
        // Get an access token using the authorization code grant
        $accessToken = $this->getProvider()->getAccessToken('authorization_code', [
            'code' => $params['code']
        ]);

        // The id token is a JWT token that contains information about the user
        // It's a base64 coded string that has a header, payload and signature
        $idToken = $accessToken->getToken(); // Change getValues()['id_token'] to getToken() because AccessToken no longer has a getValues() method
        $decodedAccessTokenPayload = base64_decode(
            explode('.', $idToken)[1]
        );
        $jsonAccessTokenPayload = json_decode($decodedAccessTokenPayload, true);

        $data = $jsonAccessTokenPayload;
        $data['access_token'] = $accessToken->getToken(); // Change getAccessToken() to getToken() because AccessToken no longer has a getAccessToken() method
        $entity = new Entity($data);
        $_SESSION[ActiveDirectory::SESSION_KEY]['entity'] = $entity;

        $response = $this->getResponse();
        if ($response instanceof ResponseInterface) {
            $response = new Response(Psr7Response::toLaminas($response)); // Change toLaminas() from toZend() to convert PSR-7 response to Laminas response
        }
        $location = (new Location())->setUri($this->getReturnUrl());
        $response->getHeaders()->addHeader($location);
        $response->setStatusCode(302);
        $response->sendHeaders();
        exit;
    }

}
