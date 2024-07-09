<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\Github;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Flex\Response;

class GithubController extends AbstractController
{
    private $githubProvider;
    private $tokenStorage;

    private $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $entityManager)
    {
        $this->githubProvider = new Github([
            'clientId' => $_ENV['GITHUB_ID'],
            'clientSecret' => $_ENV['GITHUB_SECRET'],
            'redirectUri' => $_ENV['GITHUB_CALLBACK'],
        ]);
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    #[Route('/github-login', name: 'github_login')]
    public function githubLogin() : \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $options = [
            'scope' => ['user','user:email']
        ];

        $helperUrl = $this->githubProvider->getAuthorizationUrl($options);
        return $this->redirect($helperUrl);
    }

    #[Route('/github-callback', name: 'github_callback')]
    public function githubCallBack() : Response
    {
        $token = $this->githubProvider
            ->getAccessToken('authorization_code', ['code' => $_GET['code']]);

        try {
            $user = $this->githubProvider->getResourceOwner($token);
            $email = $user->getEmail();
            $name = $user->getName();

            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setName($name);
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $jwtSecret = '%kernel.project_dir%/config/jwt/private.pem';
            $issuedAt = time();
            $expirationTime = $issuedAt + 14400;

            $payload = [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'exp' => $expirationTime,
            ];

            try {
                $token = JWT::encode($payload, $jwtSecret, 'HS256');

                $symfonyToken = new UsernamePasswordToken($user, $token, ['Admin'], $user->getRoles());

                $this->tokenStorage->setToken($symfonyToken);

                dd($token);

                return new JsonResponse(['token' => $token], JsonResponse::HTTP_OK);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Failed to generate token.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }


            dd($user->getEmail());
        } catch (IdentityProviderException $e) {
             return $e->getMessage();
        }
    }
}