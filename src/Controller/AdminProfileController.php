<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\SearchingType;
use App\Form\StatusType;
use App\Repository\UserRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/gestion/profil", name="admin_profile")
 */
class AdminProfileController extends AbstractController
{
    /**
     * @Route("/", name="_index")
     * @param UserRepository $userRepository
     * @param Request $request
     * @return Response
     * @IsGranted("ROLE_ADMINISTRATEUR")
     */
    public function index(UserRepository $userRepository, Request $request) : Response
    {
        $form = $this->createForm(SearchingType::class);
        $form->handleRequest($request);

        $users = $userRepository->findAllWhithoutAdmin();

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $users = $userRepository->findBySearchWhithoutAdmin($data['search']);
        }

        return $this->render('admin_profile/index.html.twig', [
            'users' => $users,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{login}", name="_show", methods={"GET","POST"})
     * @param User $user
     * @param Request $request
     * @return Response
     * @IsGranted("ROLE_ADMINISTRATEUR")
     */
    public function show(User $user, Request $request): Response
    {
        $form = $this->createForm(StatusType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getStatus() === 'Validé') {
                $user->setRoles(['ROLE_AMBASSADEUR']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }


            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'success',
                'Le profil de l\'Ambassadeur a bien été mis à jour !'
            );
            return $this->redirectToRoute('admin_profile_index');
        }

        return $this->render('admin_profile/show.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
