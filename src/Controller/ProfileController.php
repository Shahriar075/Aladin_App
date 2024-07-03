<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LeaveService;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use http\Env\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProfileService $profileService;
    private LeaveService $leaveService;

    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, ProfileService $profileService,
                                LeaveService $leaveService, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->profileService = $profileService;
        $this->leaveService = $leaveService;
        $this->userRepository = $userRepository;
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

    #[Route('/profile/{id}', name: 'profile_view', methods: ['GET'])]
    public function getUserProfile(int $id,\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($currentUser->getId() !== $id) {
            return new JsonResponse(['error' => 'Unauthorized access.'], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $profileData = $this->profileService->getUserProfile($currentUser);
            return new JsonResponse($profileData, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/leave-requests/{userName}', name: 'leave_requests', methods: ['GET'])]
    public function getLeaveRequestsForTeamMembers(string $userName,\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($currentUser->getName() !== $userName) {
            return new JsonResponse(['error' => 'Unauthorized access.'], JsonResponse::HTTP_FORBIDDEN);
        }
        $leaveRequests = $this->leaveService->getLeaveRequestsForTeamMembers($userName);
        return new JsonResponse($leaveRequests, JsonResponse::HTTP_OK);
    }

    #[Route('/attendance-history/{id}', name: 'attendance_history', methods: ['GET'])]
    public function getAttendanceHistory(int $id, \Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];


        $currentUser = $this->getUserFromJwt($jwtToken);
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }
        if ($currentUser->getId() !== $id) {
            return new JsonResponse(['error' => 'Unauthorized access.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $attendanceHistory = $this->profileService->getAttendanceHistory($user);
        return new JsonResponse($attendanceHistory, JsonResponse::HTTP_OK);
    }

    #[Route('/search-users/{name}', name: 'search_users', methods: ['GET'])]
    public function getUsersBySearch(string $name, \Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);

        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }
        $users = $this->profileService->getUsersBySearch($name);
        return new JsonResponse($users, JsonResponse::HTTP_OK);
    }

    #[Route('/users', name: 'all_users', methods: ['GET'])]
    public function getAllUsers(\Symfony\Component\HttpFoundation\Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return new JsonResponse(['error' => 'Token not provided.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $jwtToken = $matches[1];

        $currentUser = $this->getUserFromJwt($jwtToken);
        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $users = $this->profileService->getAllUsers();
            return new JsonResponse($users, JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
