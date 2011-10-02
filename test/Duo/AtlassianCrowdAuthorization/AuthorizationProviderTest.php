<?php
 /**
 * ${description}
 *
 * Propriedade intelectual de Duo Criativa (www.duocriativa.com.br).
 *
 * @author     Paulo R. Ribeiro <paulo@duocriativa.com.br>
 * @package    ${package}
 * @subpackage ${subpackage}
 */

namespace Duo\AtlassianCrowdAuthorization;

class AuthorizationProviderTest extends \PHPUnit_Framework_TestCase
{

    protected $provider;
    protected $valid_user;
    protected $remote_address;

    public function setUp()
    {
        $this->provider = new \Duo\AtlassianCrowdAuthorization\AuthorizationProvider();

        $ini = parse_ini_file('/home/paulo/dev/test-settings.ini');

        $this->provider->setServer($ini['crowd_server']);
        //        $this->provider->setServer('http://sandbox.local/log/request.php');
        $this->provider->setAppName($ini['crowd_app_name']);
        $this->provider->setAppPassword($ini['crowd_app_password']);

        $valid_user = array();
        $valid_user['username'] = $ini['crowd_valid_user_username'];
        $valid_user['password'] = $ini['crowd_valid_user_password'];
        $this->valid_user = $valid_user;
        $this->remote_address = $ini['crowd_remote_address'];

    }

    public function testAuthenticate()
    {
        $response = $this->provider->authenticate($this->valid_user['username'], $this->valid_user['password']);
        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response['email']), "Test the XML response is email property");
        $this->assertTrue(isset($response['active']), "Test the XML response has active property");
    }

    public function testCreateSessionToken()
    {
        $response = $this->provider->createSessionToken($this->valid_user['username'], $this->valid_user['password'], $this->remote_address);
        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response['token']), "Test the XML response has token property");
        $this->assertTrue(isset($response['user']), "Test the XML response has user property");
        $this->assertTrue(isset($response['user']['email']), "Test the XML response has user email property");
    }

    public function testRetrieveToken()
    {
        $response = $this->provider->createSessionToken($this->valid_user['username'], $this->valid_user['password'], $this->remote_address);
        $response = $this->provider->retrieveToken($response['token']);
        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response['token']), "Test the XML response has token property");
    }

    /**
     * @expectedException Exception
     */
    public function testRetrieveTokenNotFound()
    {
        $response = $this->provider->retrieveToken('TOKEN-NOT-FOUND');
    }

    public function testValidateSessionToken()
    {
        $response = $this->provider->createSessionToken($this->valid_user['username'], $this->valid_user['password'], $this->remote_address);
        $response = $this->provider->validateSessionToken($response['token'], $this->remote_address);
        $this->assertTrue($response, "Test token validates successfully");
    }

    public function testInvalidateSessionToken()
    {
        $response = $this->provider->createSessionToken($this->valid_user['username'], $this->valid_user['password'], $this->remote_address);
        $response = $this->provider->invalidateSessionToken($response['token']);
        $this->assertTrue($response);
    }

    public function testRetrieveUser()
    {
        $response = $this->provider->retrieveUser($this->valid_user['username']);
        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response['email']), "Test the XML response is email present");
        $this->assertTrue(isset($response['active']), "Test the XML response has active present");
    }

    public function testRetrieveUserWithAttributes()
    {
        $response = $this->provider->retrieveUser($this->valid_user['username'], true);
        $this->assertTrue(is_array($response));
        $this->assertTrue(isset($response['email']), "Test the XML response is email present");
        $this->assertTrue(isset($response['active']), "Test the XML response has active present");
        $this->assertTrue(isset($response['passwordLastChanged']), "Test the XML response has passwordLastChanged present");
    }

    public function testRetrieveUserGroups()
    {
        $response = $this->provider->retrieveUserGroups($this->valid_user['username'], true);
        $this->assertTrue(is_array($response));
        $this->assertTrue(count($response)>0);
    }


}
