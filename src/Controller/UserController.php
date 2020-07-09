<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\SearchingType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Form\RegistrationFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{

    const USERS_PER_PAGE = 8;

    /**
     * @Route("/trombinoscope", name="user_index")
     * @param PaginatorInterface $paginator
     * @param UserRepository $userRepository
     * @param Request $request
     * @return Response
     */
    public function index(
        PaginatorInterface $paginator,
        UserRepository $userRepository,
        Request $request
    ): Response {

        $form = $this->createForm(SearchingType::class);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $ambassadors = $userRepository->findBySearch($data['search']);
        } else {
            $ambassadors = $userRepository->findAll();
        }

        $users = $paginator->paginate(
            $ambassadors,
            $request->query->getInt('page', 1),
            self::USERS_PER_PAGE
        );

        return $this->render('trombinoscope/index.html.twig', [
            'form' => $form->createView(),
            'users' => $users

        ]);
    }

    /**
     * @Route("/{login}", name="user_show")
     * @param User $user
     * @return Response
     */
    public function show(User $user): Response
    {

        return $this->render('ambassador/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/modification/{login}", name="user_edit")
     * @param User $user
     * @param Request $request
     * @return Response
     * @IsGranted("ROLE_AMBASSADEUR")
     */
    public function edit(User $user, Request $request): Response
    {
        if ($this->getUser() !== $user) {
            $this->addFlash(
                'danger',
                'Il ne vous est pas possible de modifier le profil d\'un autre Ambassadeur'
            );
        }
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre profil Ambassadeur a bien été mis à jour!'
            );
            return $this->redirectToRoute('user_show', ['login' => $user->getLogin()]);
        }

        return $this->render('ambassador/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'registrationForm' => $form->createView(),
        ]);
    }
}
