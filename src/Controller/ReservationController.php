<?php

namespace App\Controller;

use App\Entity\EmailConfirmation;
use App\Entity\Reservation;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\MailingHelperException;
use App\Service\GetterHelper\GetterHelperInterface;
use App\Service\MailingHelper\MailingHelper;
use App\Service\SetterHelper\SetterHelperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class ReservationController extends AbstractApiController
{
    #[Route('reservation', name: 'reservation_new', methods: ['POST'])]
    public function new(
        EntityManagerInterface $entityManager, 
        SetterHelperInterface $setterHelper, 
        ValidatorInterface $validator, 
        MailingHelper $mailingHelper,
        Request $request
        ): JsonResponse
    {

        $reservation = new Reservation();

        try{
            $setterHelper->updateObjectSettings($reservation, $request->request->all(), ['Default', 'initOnly']);
            $validationErrors = $setterHelper->getValidationErrors();
            
            $violations = $validator->validate($reservation, groups: $setterHelper->getValidationGroups());

            foreach ($violations as $violation) {
                $requestParameterName = $setterHelper->getPropertyRequestParameter($violation->getPropertyPath());
                $validationErrors[$requestParameterName] = $violation->getMessage();
            }

            if(count($validationErrors) > 0){
                return $this->newApiResponse(status: 'fail', data: ['message' => 'Validation error', 'errors' => $validationErrors], code: 400);
            }

            $setterHelper->runPostValidationTasks();

        }
        catch(InvalidRequestException){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Invalid request', 'errors' => $setterHelper->getRequestErrors()], code: 400);
        }

        $reservation->setVerified(false);
        $reservation->setConfirmed(false);

        $entityManager->persist($reservation);
        $entityManager->flush();
        
        try{
            $mailingHelper->newReservationVerification($reservation); 
        }
        catch(MailingHelperException){
            $entityManager->remove($reservation);
            $entityManager->flush();
            return $this->newApiResponse(status: 'error', data: ['message' => 'Mailing provider error'], code: 500);
        }

        return $this->newApiResponse(data: ['message' => 'Reservation created successfully']);
    }

    #[Route('reservation_verify', name: 'reservation_verify', methods: ['GET'])]
    public function verify(
        EntityManagerInterface $entityManager, 
        VerifyEmailHelperInterface $verifyEmailHelper, 
        MailingHelper $mailingHelper, 
        Request $request
        )
    {
        $id = (int)$request->get('id');
        $emailConfirmation = $entityManager->getRepository(EmailConfirmation::class)->find($id);
        if(!($emailConfirmation instanceof EmailConfirmation)){
            return $this->render(
                'reservationVerification.html.twig', 
                ['header' => 'Verification Failed', 'description' => 'Verification link is invalid']
            );
        }

        try{
            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $emailConfirmation->getId(), $emailConfirmation->getEmail());
            $reservationId = $emailConfirmation->getParams()['reservationId'];
            $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
            if(!($reservation instanceof Reservation)){
                return $this->render(
                    'reservationVerification.html.twig', 
                    ['header' => 'Verification Failed', 'description' => 'Reservation not found']
                );
            }

            $mailingHelper->newReservationInformation($reservation, 'Reservation Verified', 'emails/reservationVerified.html.twig', true);

            $reservation->setVerified(true);
            $reservation->setExpiryDate(null);
            $entityManager->remove($emailConfirmation);
            $entityManager->flush();
            return $this->render(
                'reservationVerification.html.twig', 
                ['header' => 'Verification Completed', 'description' => 'Your reservation was verfied successfully']
            );

        } 
        catch(VerifyEmailExceptionInterface $e) {
            return $this->render(
                'reservationVerification.html.twig', 
                ['header' => 'Verification Failed', 'description' => $e->getReason()]
            );
        }
        catch(MailingHelperException){
            return $this->render(
                'reservationVerification.html.twig', 
                ['header' => 'Verification Failed', 'description' => 'Server Error']
            );
        }

        

    }

    #[Route('reservation_cancel', name: 'reservation_cancel', methods: ['GET'])]
    public function cancel(
        EntityManagerInterface $entityManager, 
        VerifyEmailHelperInterface $verifyEmailHelper,  
        Request $request
        )
    {
        $id = (int)$request->get('id');
        $emailConfirmation = $entityManager->getRepository(EmailConfirmation::class)->find($id);
        if(!($emailConfirmation instanceof EmailConfirmation)){
            return $this->render(
                'reservationCancellation.html.twig', 
                ['header' => 'Cancellation Failed', 'description' => 'Cancellation link is invalid']
            );
        }

        try{
            $verifyEmailHelper->validateEmailConfirmation($request->getUri(), $emailConfirmation->getId(), $emailConfirmation->getEmail());
            $reservationId = $emailConfirmation->getParams()['reservationId'];
            $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
            if(!($reservation instanceof Reservation)){
                return $this->render(
                    'reservationCancellation.html.twig', 
                    ['header' => 'Cancelation Failed', 'description' => 'Reservation not found']
                );
            }

            $entityManager->remove($reservation);
            $entityManager->remove($emailConfirmation);
            $entityManager->flush();

            return $this->render(
                'reservationCancellation.html.twig', 
                ['header' => 'Reservation Cancelled', 'description' => 'Your reservation was cancelled']
            );

        } 
        catch(VerifyEmailExceptionInterface $e) {
            return $this->render(
                'reservationCancellation.html.twig', 
                ['header' => 'Cancellation Failed', 'description' => $e->getReason()]
            );
        }
    }

    #[Route('reservation/{reservationId}', name: 'reservation_get', methods: ['GET'])]
    public function get(
        EntityManagerInterface $entityManager, 
        GetterHelperInterface $getterHelper,  
        Request $request, 
        int $reservationId
        ): JsonResponse
    {
        $currentUser = $this->getUser();
        if(!$currentUser){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 401);
        }

        $allowedDetails = ['organization', 'schedule', 'service'];
        $details = $request->query->get('details');
        $detailGroups = !is_null($details) ? explode(',', $details) : [];
        if(!empty(array_diff($detailGroups, $allowedDetails))){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Invalid request', 'errors' => ['details' => 'Requested details are invalid']], code: 400);
        }

        $detailGroups = array_map(fn($group) => 'reservation-' . $group, $detailGroups);
        $groups = array_merge(['reservation'], $detailGroups);

        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if(!($reservation instanceof Reservation)){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Reservation not found'], code: 404);
        }

        $schedule = $reservation->getSchedule();
        $organization = $schedule->getOrganization();
        $member = $organization->getMember($currentUser);
        $assignment = $member ? $schedule->getAssignments()->findFirst(function($key, $element) use ($member){
            return $element->getOrganizationMember() == $member;
        }) : null;

        $hasAccess = $member && ($member->hasRoles(['ADMIN']) || !is_null($assignment));
        if(!$hasAccess){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 403);
        }

        $responseData = $getterHelper->get($reservation, $groups);

        return $this->newApiResponse(data: $responseData);
    }

    #[Route('reservation/{reservationId}', name: 'reservation_modify', methods: ['PATCH'])]
    public function modify(
        EntityManagerInterface $entityManager, 
        SetterHelperInterface $setterHelper, 
        ValidatorInterface $validator, 
        MailingHelper $mailingHelper,
        Request $request, 
        int $reservationId
        ): JsonResponse
    {
        $currentUser = $this->getUser();
        if(!$currentUser){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 401);
        }

        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if(!($reservation instanceof Reservation)){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Reservation not found'], code: 404);
        }

        $schedule = $reservation->getSchedule();
        $organization = $schedule->getOrganization();
        $member = $organization->getMember($currentUser);
        $assignment = $member ? $schedule->getAssignments()->findFirst(function($key, $element) use ($member){
            return $element->getOrganizationMember() == $member;
        }) : null;

        $hasAccess = $member && ($member->hasRoles(['ADMIN']) || !is_null($assignment));
        if(!$hasAccess){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 403);
        }

        try{
            $setterHelper->updateObjectSettings($reservation, $request->request->all(), [], ['Default']);
            $validationErrors = $setterHelper->getValidationErrors();
            
            $violations = $validator->validate($reservation, groups: $setterHelper->getValidationGroups());

            foreach ($violations as $violation) {
                $requestParameterName = $setterHelper->getPropertyRequestParameter($violation->getPropertyPath());
                $validationErrors[$requestParameterName] = $violation->getMessage();
            }            

            if(count($validationErrors) > 0){
                return $this->newApiResponse(status: 'fail', data: ['message' => 'Validation error', 'errors' => $validationErrors], code: 400);
            }

            $setterHelper->runPostValidationTasks();
        }
        catch(InvalidRequestException $e){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Invalid request', 'errors' => $setterHelper->getRequestErrors()], code: 400);
        }

        $entityManager->flush();

        try{
            $mailingHelper->newReservationInformation($reservation, 'Reservation Modified', 'emails/reservationModified.html.twig', true);
        }
        catch(MailingHelperException){
            return $this->newApiResponse(status: 'error', data: ['message' => 'Mailing provider error'], code: 500);
        }

        return $this->newApiResponse(data: ['message' => 'Reservation modified successfully']);
    }

    #[Route('reservation/{reservationId}', name: 'reservation_delete', methods: ['DELETE'])]
    public function delete(
        EntityManagerInterface $entityManager, 
        MailingHelper $mailingHelper,
        int $reservationId
        ): JsonResponse
    {
        $currentUser = $this->getUser();
        if(!$currentUser){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 401);
        }

        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if(!($reservation instanceof Reservation)){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Reservation not found'], code: 404);
        }

        $schedule = $reservation->getSchedule();
        $organization = $schedule->getOrganization();
        $member = $organization->getMember($currentUser);
        $assignment = $member ? $schedule->getAssignments()->findFirst(function($key, $element) use ($member){
            return $element->getOrganizationMember() == $member;
        }) : null;

        $hasAccess = $member && ($member->hasRoles(['ADMIN']) || !is_null($assignment));
        if(!$hasAccess){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 403);
        }
        
        $entityManager->remove($reservation);
        $entityManager->flush();

        try{
            $mailingHelper->newReservationInformation($reservation, 'Reservation Removed', 'emails/reservationRemoved.html.twig', false); 
        }
        catch(MailingHelperException){
            return $this->newApiResponse(status: 'error', data: ['message' => 'Mailing provider error'], code: 500);
        }

        return $this->newApiResponse(data: ['message' => 'Reservation removed successfully']);
    }

    #[Route('reservation_confirm/{reservationId}', name: 'reservation_confirm', methods: ['POST'])]
    public function confirm(EntityManagerInterface $entityManager, int $reservationId): JsonResponse
    {    
        $currentUser = $this->getUser();
        if(!$currentUser){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 401);
        }

        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if(!($reservation instanceof Reservation)){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Reservation not found'], code: 404);
        }

        $schedule = $reservation->getSchedule();
        $organization = $schedule->getOrganization();
        $member = $organization->getMember($currentUser);
        $assignment = $member ? $schedule->getAssignments()->findFirst(function($key, $element) use ($member){
            return $element->getOrganizationMember() == $member;
        }) : null;

        $hasAccess = $member && ($member->hasRoles(['ADMIN']) || !is_null($assignment));
        if(!$hasAccess){
            return $this->newApiResponse(status: 'fail', data: ['message' => 'Access denied'], code: 403);
        }
        
        $reservation->setConfirmed(true);
        $entityManager->flush();

        return $this->newApiResponse(data: ['message' => 'Reservation confirmed']);
    }
}
