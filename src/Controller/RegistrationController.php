<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UsersAuthenticator $authenticator, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $header = [
                'typ'=>'JWT',
                'alg'=>'H256'
            ];

            $payload = [
                'user_id'=>$user->getId()
            ];

            $token = $jwt->generate($header,$payload,$this->getParameter('app.jwtsecret'));

            // do anything else you need here, like send an email
            $mail->send(
                'no-reply@monsite.fr',
                $user->getEmail(),
                'Activation de votre compte sur le site e-commerce',
                'register',
                compact('user', 'token')
            );

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function verifyUser($token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $em): Response
    {
        if(
            $jwt->isValid($token)
            &&!$jwt->isExpired($token)
            &&$jwt->check($token,$this->getParameter('app.jwtsecret'))
        ) {
            $payload=$jwt->getPayload($token);

            $user = $usersRepository->find($payload['user_id']);

            if($user && !$user->getIsVerified())
            {
                $user->setIsVerified(true);
                $em->flush($user);
                $this->addFlash('success', 'utilisateur activé', );
                return $this->redirectToRoute('profile_index');

            }
        }
        $this->addFlash('danger', 'le token a expiré', );
        return $this->redirectToRoute('app_login');
    }

    #[Route('/renvoiverif', name:'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user =$this->getUser();
        if(!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour accéder à cette page', );
            return $this->redirectToRoute('app_login');
        }
        if ($user->getIsVerified()){
            $this->addFlash('warning', 'cet utilisateur est déjà activé', );
            return $this->redirectToRoute('profile_index');
        }
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload = ['user_id' => $user->getId()];

        $token = $jwt->generate($header, $payload, $this->getParameter('app.jwtsecret'));

        $mail->send(
            'no-reply@monsite.net',
            $user->getEmail(),
            'Activation de votre compte sur le site e-commerce',
            'register',
            compact('user', 'token')
        );
        $this->addFlash('success', 'Email de vérification envoyé');
        return $this->redirectToRoute('profile_index');
    }
}
