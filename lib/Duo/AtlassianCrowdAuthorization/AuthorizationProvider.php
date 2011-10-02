<?php

/*
 * This file is part of AtlassianCrowdAuthorizationPHP.
 *
 * (c) 2011 Paulo Ribeiro
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class responsible to communicate with the Atlassian Crowd website.
 *
 * @author     Paulo R. Ribeiro <paulo@duocriativa.com.br>
 * @package    atlassian_crowd_authorization_php
 */
namespace Duo\AtlassianCrowdAuthorization;


class AuthorizationProvider
{

    const API_VERSION = 'latest';

    /*
     * Server url  https://people.mydomain.com
     */
    protected $server;
    protected $app_name;
    protected $app_password;

    function __construct($server = null, $app_name = null, $app_password = null)
    {
        $this->server = $server;
        $this->app_name = $app_name;
        $this->app_password = $app_password;
    }


    /**
     * Sets the server url  https://people.mydomain.com
     *
     * @param $server Server url
     * @return void
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * Gets the server url. E.g.: https://people.mydomain.com/crowd
     *
     * @return string Server url
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param $app_name The application name to authenticate agains Atlassian Crowd server
     * @return void
     */
    public function setAppName($app_name)
    {
        $this->app_name = $app_name;
    }

    /**
     * @return string Application name
     */
    public function getAppName()
    {
        return $this->app_name;
    }

    /**
     * @param $app_password The application password to authenticate agains Atlassian Crowd server
     * @return void
     */
    public function setAppPassword($app_password)
    {
        $this->app_password = $app_password;
    }

    public function getAppPassword()
    {
        return $this->app_password;
    }

    protected function checkParamsAreSet()
    {
        if (is_null($this->server)) throw new Exception('Server url must be set before using the provider');
        if (is_null($this->app_name)) throw new Exception('Application name must be set before using the provider');
        if (is_null($this->app_password)) throw new Exception('Application password must be set before using the provider');
    }

    protected function  getCommonHeaders()
    {
        $headers = array("Authorization: Basic " . base64_encode($this->getAppName() . ":" . $this->getAppPassword()));
        $headers[] = 'Content-Type: application/xml';
        //        $headers[] = 'Accept: application/xml,application/xhtml+xml,application/html;q=0.9,*/*;q=0.8';
        $headers[] = 'Accept: application/xml;q=0.9';
        return $headers;
    }

    protected function getBaseUri()
    {
        return "/crowd/rest/usermanagement/" . self::API_VERSION;
    }

    /**
     * Authenticates a user against Atlassian Crowd server
     *
     * @throws \Exception If the user cannot be authenticated and exception is throwed.
     * @param $username Username of the user
     * @param $password Password of the user
     * @return \SimpleXMLElement Object containing the response of server in case of successful authentication
     */
    public function authenticate($username, $password)
    {

        // sets the uri
        $uri = $this->getBaseUri() . "/authentication?username={USERNAME}";
        $uri = str_replace('{USERNAME}', $username, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        $post_body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><password><value>{$password}</value></password>";

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->post($url, $headers, $post_body);
        if ($response->getStatusCode() == 200)
        { // successful
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            return $this->xml2arrayUser($xmlObject);
        } else
        { // unsuccessful
            if ($response->getStatusCode() == 400)
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                $this->throwReasonException($xmlObject->reason, $xmlObject->message);
            }
        }
    }

    public function createSessionToken($username, $password, $remote_address = '127.0.0.1')
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/session";
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        $post_body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<authentication-context>
  <username>{$username}w</username>
  <password>{$password}</password>
  <validation-factors>
    <validation-factor>
      <name>remote_address</name>
      <value>{$remote_address}</value>
    </validation-factor>
  </validation-factors>
</authentication-context>";

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->post($url, $headers, $post_body);
        if ($response->getStatusCode() == 201)
        { // successful
            $resource = $response->getHeader('Location');
            $response = $browser->get($resource, $headers);
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            $token = array();
            $token['token'] = $xmlObject->token;
            $token['user'] = $this->xml2arrayUser($xmlObject->user);
            return $token;
        } else
        { // unsuccessful
            if ($response->getStatusCode() == 400)
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                $this->throwReasonException($xmlObject->reason, $xmlObject->message);
            }
        }

    }

    public function retrieveToken($token)
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/session/{TOKEN}";
        $uri = str_replace('{TOKEN}', $token, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->get($url, $headers);
        if ($response->getStatusCode() == 200)
        { // successful
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            $token = array();
            $token['token'] = $xmlObject->token;
            $token['user'] = $this->xml2arrayUser($xmlObject->user);
            return $token;
        } else
        { // unsuccessful
            if ($response->getStatusCode() == 400)
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                throw new \Exception($xmlObject->message);
            }
        }
    }

    /**
     * Validated a session token
     *
     * @throws Exception\InvalidSsoTokenException|\Exception
     * @param $token
     * @param string $remote_address
     * @return bool
     */
    public function validateSessionToken($token, $remote_address = '127.0.0.1')
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/session/{TOKEN}";
        $uri = str_replace('{TOKEN}', $token, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        $post_body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
  <validation-factors>
    <validation-factor>
      <name>remote_address</name>
      <value>{$remote_address}</value>
    </validation-factor>
  </validation-factors>";

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->post($url, $headers, $post_body);
        if ($response->getStatusCode() == 200)
        { // successful
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            return true;
        } else
        { // unsuccessful
            if ($response->getStatusCode() == 404)
            {
                throw new Exception\InvalidSsoTokenException();
            } elseif ($response->getStatusCode() == 400)
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                $this->throwReasonException($xmlObject->reason, $xmlObject->message);
            } else
            {
                throw new \Exception('Unexpected error: ' . $response->getStatusCode() . "\n" . $response->getContent());
            }
        }

    }


    public function invalidateSessionToken($token)
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/session/{TOKEN}";
        $uri = str_replace('{TOKEN}', $token, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->delete($url, $headers);
        if ($response->getStatusCode() == 204)
        { // successful
            return true;
        } else
        { // unsuccessful
            throw new \Exception('Unexpected error: ' . $response->getStatusCode() . "\n" . $response->getContent());
        }

    }


    public function retrieveUser($username, $include_attributes = false)
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/user?username={USERNAME}" . ($include_attributes ? '&expand=attributes' : '');
        $uri = str_replace('{USERNAME}', $username, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->get($url, $headers);
        if ($response->getStatusCode() == 200)
        { // successful
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            $user = array();
            $user['username'] = (string)$xmlObject->attributes()->name;
            foreach (array('first-name', 'last-name', 'display-name', 'email', 'active') as $value)
            {
                $user[$value] = (string)$xmlObject->$value;
            }
            if ($include_attributes)
            {
                $attributes = (array)$xmlObject->attributes;
                $attributes = (array)$attributes['attribute'];
                foreach ($attributes as $att)
                {
                    $user[(string)$att->attributes()->name] = (string)$att->values->value;
                }
            }
            return $user;
        } else
        { // unsuccessful
            if (in_array($response->getStatusCode(), array(400, 404)))
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                throw new \Exception($xmlObject->message);
            } else
            {
                throw new \Exception('Unexpected error: ' . $response->getStatusCode() . "\n" . $response->getContent());
            }
        }

    }


    /**
     * Retrieves the groups that the user is a direct member of
     *
     * @throws \Exception
     * @param $username
     * @return \SimpleXMLElement
     */
    public function retrieveUserGroups($username)
    {
        // sets the uri
        $uri = $this->getBaseUri() . "/user/group/direct?username={USERNAME}";
        $uri = str_replace('{USERNAME}', $username, $uri);
        $url = $this->getServer() . $uri;

        // additional headers
        $headers = $this->getCommonHeaders();

        // query the server
        $browser = new \Buzz\Browser();
        $response = $browser->get($url, $headers);
        if ($response->getStatusCode() == 200)
        { // successful
            @$xmlObject = simplexml_load_string($response->getContent());
            if (false === $xmlObject)
            { // test if the content is a valid xml
                throw new \Exception('Content from server is not a valid XML.' . $response);
            }
            // create array of groups
            $groups = array();
            foreach ($xmlObject->group as $key => $group)
            {
                //                $atts = $xmlObject->group[$key]->attributes();
                $groups[] = (string)$group->attributes()->name;
            }
            return $groups;
        } else
        { // unsuccessful
            if (in_array($response->getStatusCode(), array(404)))
            {
                @$xmlObject = simplexml_load_string($response->getContent());
                if (false === $xmlObject)
                { // test if the content is a valid xml
                    throw new \Exception('Content from server is not a valid XML. ' . $response);
                }
                throw new \Exception($xmlObject->message);
            } else
            {
                throw new \Exception('Unexpected error: ' . $response->getStatusCode() . "\n" . $response->getContent());
            }
        }

    }


    protected function xml2arrayUser(\SimpleXMLElement $xmlObject, $include_attributes = false)
    {
        $user = array();
        $user['username'] = (string)$xmlObject->attributes()->name;
        foreach (array('first-name', 'last-name', 'display-name', 'email', 'active') as $value)
        {
            $user[$value] = (string)$xmlObject->$value;
        }
        if ($include_attributes)
        {
            $attributes = (array)$xmlObject->attributes;
            $attributes = (array)$attributes['attribute'];
            foreach ($attributes as $att)
            {
                $user[(string)$att->attributes()->name] = (string)$att->values->value;
            }
        }
        return $user;
    }

    /**
     * @throws Exception\ApplicationAccessDeniedException|Exception\ApplicationPermissionDeniedException|Exception\ExpiredCredentialException|Exception\GroupNotFoundException|Exception\IllegalArgumentException|Exception\InactiveAccountException|Exception\InvalidCredentialException|Exception\InvalidEmailException|Exception\InvalidGroupException|Exception\InvalidSsoTokenException|Exception\InvalidUserAuthenticationException|Exception\InvalidUserException|Exception\MembershipNotFoundException|Exception\NestedGroupsNotSupportedException|Exception\OperationFailedException|Exception\UnsupportedOperationException|Exception\UserNotFoundException
     * @param $reason
     * @return void
     */
    protected function throwReasonException($reason, $message = null)
    {
        switch ($reason)
        {
            case 'APPLICATION_ACCESS_DENIED':
                if ($message)
                    throw new Exception\ApplicationAccessDeniedException($message);
                else
                    throw new Exception\ApplicationAccessDeniedException();
                break;
            case 'APPLICATION_PERMISSION_DENIED':
                if ($message)
                    throw new Exception\ApplicationPermissionDeniedException($message);
                else
                    throw new Exception\ApplicationPermissionDeniedException();
                break;
            case 'EXPIRED_CREDENTIAL':
                if ($message)
                    throw new Exception\ExpiredCredentialException($message);
                else
                    throw new Exception\ExpiredCredentialException();
                break;
            case 'GROUP_NOT_FOUND':
                if ($message)
                    throw new Exception\GroupNotFoundException($message);
                else
                    throw new Exception\GroupNotFoundException();
                break;
            case 'ILLEGAL_ARGUMENT':
                if ($message)
                    throw new Exception\IllegalArgumentException($message);
                else
                    throw new Exception\IllegalArgumentException();
                break;
            case 'INACTIVE_ACCOUNT':
                if ($message)
                    throw new Exception\InactiveAccountException($message);
                else
                    throw new Exception\InactiveAccountException();
                break;
            case 'INVALID_USER_AUTHENTICATION':
                if ($message)
                    throw new Exception\InvalidUserAuthenticationException($message);
                else
                    throw new Exception\InvalidUserAuthenticationException();
                break;
            case 'INVALID_CREDENTIAL':
                if ($message)
                    throw new Exception\InvalidCredentialException($message);
                else
                    throw new Exception\InvalidCredentialException();
                break;
            case 'INVALID_EMAIL':
                if ($message)
                    throw new Exception\InvalidEmailException($message);
                else
                    throw new Exception\InvalidEmailException();
                break;
            case 'INVALID_GROUP':
                if ($message)
                    throw new Exception\InvalidGroupException($message);
                else
                    throw new Exception\InvalidGroupException();
                break;
            case 'INVALID_SSO_TOKEN':
                if ($message)
                    throw new Exception\InvalidSsoTokenException($message);
                else
                    throw new Exception\InvalidSsoTokenException();
                break;
            case 'INVALID_USER':
                if ($message)
                    throw new Exception\InvalidUserException($message);
                else
                    throw new Exception\InvalidUserException();
                break;
            case 'MEMBERSHIP_NOT_FOUND':
                if ($message)
                    throw new Exception\MembershipNotFoundException($message);
                else
                    throw new Exception\MembershipNotFoundException();
                break;
            case 'NESTED_GROUPS_NOT_SUPPORTED':
                if ($message)
                    throw new Exception\NestedGroupsNotSupportedException($message);
                else
                    throw new Exception\NestedGroupsNotSupportedException();
                break;
            case 'UNSUPPORTED_OPERATION':
                if ($message)
                    throw new Exception\UnsupportedOperationException($message);
                else
                    throw new Exception\UnsupportedOperationException();
                break;
            case 'USER_NOT_FOUND':
                if ($message)
                    throw new Exception\UserNotFoundException($message);
                else
                    throw new Exception\UserNotFoundException();
                break;
            case 'OPERATION_FAILED':
                if ($message)
                    throw new Exception\OperationFailedException($message);
                else
                    throw new Exception\OperationFailedException();
                break;
        }
    }

}
