<?php

namespace App\Controller;

use App\Entity\LeaveRequest;
use App\Entity\User;
use App\Service\AdminUserService;
use App\Service\GeneralUserService;
use App\Service\LeaveService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiController extends AbstractController
{
    private GeneralUserService $generalUserService;
    private UserRepository $userRepository;
    private JWTTokenManagerInterface $jwtManager;

    private AdminUserService $adminUserService;

    private LeaveService $leaveService;

    private EntityManagerInterface $entityManager;


    private $tokenStorage;

    public function __construct(GeneralUserService $generalUserService, UserRepository $userRepository, JWTTokenManagerInterface $jwtManager, TokenStorageInterface $tokenStorage,
                                AdminUserService $adminUserService,LeaveService $leaveService, EntityManagerInterface $entityManager)
    {
        $this->generalUserService = $generalUserService;
        $this->userRepository = $userRepository;
        $this->jwtManager = $jwtManager;
        $this->tokenStorage = $tokenStorage;
        $this->adminUserService = $adminUserService;
        $this->leaveService = $leaveService;
        $this->entityManager = $entityManager;
    }

    private function getUserFromJwt(string $jwtToken): ?User
    {
        $tokenParts = explode('.', $jwtToken);
        $encodedPayload = $tokenParts[1];
        $decodedPayload = base64_decode($encodedPayload);
        $payloadData = json_decode($decodedPayload, true);
        $email = $payloadData['email'] ?? null;

        return $this->userRepository->findOneBy(['email' => $email]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function registerUser(Request $request, SerializerInterface $serializer, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];
        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$this->generalUserService->isAdmin($currentUser)) {
            throw new AccessDeniedException('Only administrators are allowed to register new users.');
        }

        if (!$currentUser instanceof User) {
            return new JsonResponse(['error' => 'User not found or invalid user type.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $gender = $data['gender'] ?? '';
        $designation = $data['designation'] ?? '';
        $phone = $data['phone'] ?? '';
        $password = $data['password'] ?? '';
        $roleName = $data['roleName'] ?? '';
        $teamLead = isset($data['teamLead']) ? $this->userRepository->find($data['teamLead']) : null;

        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = $this->generalUserService->RegisterGeneralUser($name, $email, $gender, $designation, $phone, $password, $roleName, $currentUser, $teamLead);
            }

            $jsonData = $serializer->serialize($user, 'json', ['groups' => ['user:read']]);
            $responseData = [
                'message' => 'User registered successfully.',
                'user' => json_decode($jsonData, true),
            ];
            return new JsonResponse($responseData, JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/assign-team-lead', name: 'assign_team_lead', methods: ['POST'])]
    public function assignTeamLead(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$this->generalUserService->isAdmin($currentUser)) {
            throw new AccessDeniedException('Only administrators are allowed to assign team leads.');
        }

        $email = $currentUser->getEmail();
        $data = json_decode($request->getContent(), true);
        $userEmail = $data['email'] ?? '';
        $userId = $data['userId'] ?? null;
        $teamLeadEmail = $data['teamLeadEmail'] ?? '';

        $user = $this->userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid user or team lead ID.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $this->adminUserService->assignUserToTeamLead($userEmail,$teamLeadEmail,$email);
            return new JsonResponse(['message' => 'Team lead assigned successfully.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/apply-leave', name: 'apply_leave', methods: ['POST'])]
    public function applyLeave(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $user = $this->getUserFromJwt($jwtToken);
        $data = json_decode($request->getContent(), true);
        $startDate = new \DateTime($data['startDate'] ?? '');
        $endDate = new \DateTime($data['endDate'] ?? '');
        $leaveReason = $data['leaveReason'] ?? '';

        try {
            $this->leaveService->applyLeave($user, $startDate, $endDate, $leaveReason);

            return new JsonResponse(['message' => 'Leave request submitted successfully.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/approve-leave', name: 'approve_leave', methods: ['POST'])]
    public function approveLeave(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];
        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$currentUser) {
            throw new AccessDeniedException('User not found.');
        }

        $data = json_decode($request->getContent(), true);
        $leaveRequestId = $data['leaveRequestId'] ?? '';

        $leaveRequest = $this->entityManager->getRepository(LeaveRequest::class)->find($leaveRequestId);
        if (!$leaveRequest) {
            return new JsonResponse(['error' => 'Leave request not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($leaveRequest->getTeamLead() !== $currentUser) {
            throw new AccessDeniedException('Only the assigned team lead can approve this leave request.');
        }

        try {
            $this->leaveService->approveLeave($currentUser, $leaveRequest);

            return new JsonResponse(['message' => 'Leave request approved successfully.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/sign-in', name: 'sign_in', methods: ['POST'])]
    public function signIn(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $userSignIn = $this->generalUserService->signInUser($email, $password);

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid credentials.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

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


            return new JsonResponse(['token' => $token], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to generate token.'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/clock-in', name: 'clock_in', methods: ['POST'])]
    public function clockIn(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];
        $currentUser = $this->getUserFromJwt($jwtToken);

        $data = json_decode($request->getContent(), true);
        $createdAt = new \DateTime($data['createdAt']);

        try {
            $this->generalUserService->ClockIn($currentUser, $createdAt);

            return new JsonResponse(['message' => 'Clock-in successful.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/clock-out', name: 'clock_out', methods: ['POST'])]
    public function clockOut(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);
        $data = json_decode($request->getContent(), true);
        $clockOutTime = new \DateTime($data['clockOutTime']);

        try {
            $this->generalUserService->ClockOut($currentUser, $clockOutTime);

            return new JsonResponse(['message' => 'Clock-out successful.'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }
}

