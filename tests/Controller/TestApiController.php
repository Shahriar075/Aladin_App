<?php

// tests/Controller/ApiControllerTest.php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TestApiController extends WebTestCase
{
    public function testRegisterUser()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Imran',
                'email' => 'imran@exabyting.com',
                'gender' => 'Male',
                'designation' => 'Trainee',
                'phone' => 1234567890,
                'password' => 'password',
                'roleName' => 'General',
                'teamLeadId' => null,
            ])
        );

        $this->assertEquals(201, $client->getResponse()->getStatusCode());
    }

    public function testSignIn()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/signin',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'riyad@exabyting.com',
                'password' => 'password'
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
    }

    public function testClockIn()
    {
        $client = static::createClient();

        // Assume we have a valid JWT token for the user
        $jwtToken = '<your_user_jwt_token>';

        $client->request(
            'POST',
            '/clock-in',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $jwtToken
            ],
            json_encode(['userId' => 1])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testClockOut()
    {
        $client = static::createClient();
        $jwtToken = '<your_user_jwt_token>';

        $client->request(
            'POST',
            '/clock-out',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $jwtToken
            ],
            json_encode(['userId' => 1])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
