<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Nécessaire pour la pagination
use Symfony\Component\HttpFoundation\Request; // Nous avons besoin d'accéder à la requête pour obtenir le numéro de page
use Knp\Component\Pager\PaginatorInterface; // Nous appelons le bundle KNP Paginator

use App\Entity\Client;
use App\Entity\Statut;
use App\Entity\Formation;
use App\Entity\Session_formation;
use App\Entity\Plan_formation;

use App\Form\ClientType;
use App\Form\ClientCompletType;
use App\Form\StatutType;
use App\Form\FormationType;
use App\Form\SessionType;
use App\Form\PlanFormationType;

use App\Repository\ClientRepository;
use App\Repository\StatutRepository;
use App\Repository\FormationRepository;
use App\Repository\Session_formationRepository;
use App\Repository\Plan_formationRepository;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="login_administrateur")
	 *
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="deconnexion")
     */
    public function logout()
    {
        return new RedirectResponse($this->urlGenerator->generate('accueil'));
    }

    /**
     * @Route("/security/statut/liste", name="adminStatutListe")
	 *
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Gestion des statuts
	public function listeStatut(Request $request, PaginatorInterface $paginator)
	{
		$manager = $this->getDoctrine()->getManager();
		$rep = $manager->getRepository(Statut::class);
		$lesStatuts = $rep->findAll();
		
		$lesStatutsPagines = $paginator->paginate(
            $lesStatuts, // Requête contenant les données à paginer (ici nos statuts)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            4 // Nombre de résultats par page
        );
				
		return $this->render('Admin/statut.html.twig', Array('lesStatuts' => $lesStatutsPagines));
    }
    /**
     * @Route("/security/statut/modif/{id}", name="adminStatutModif")
	 *
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Affichage du formulaire de modification d'un statut
	public function modifStatut($id, Request $request, PaginatorInterface $paginator)
    {
        // Récupération du statut d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Statut::class);
		$statut = $rep->find($id);
		
		
		// Création du formulaire à partir du statut récupéré
		$form = $this->createForm(StatutType::class, $statut);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// mise à jour de la bdd
				$em->persist($statut);
				$em->flush();
				//echo "URL:" . $this->getBaseUrl();

				// Réaffichage de la liste des statuts
				$lesStatuts = $rep->listeStatuts();
				$lesStatutsPagines = $paginator->paginate(
					$lesStatuts, // Requête contenant les données à paginer (ici nos statuts)
					$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
					4 // Nombre de résultats par page
				);
				return $this->render('security/statut.html.twig', Array('lesStatuts' => $lesStatutsPagines));
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formStatut.html.twig', array('form' => $form->createView(), 'action' => 'modification'));
    }
    /**
     * @Route("/security/statut/supp/{id}", name="adminStatutSupp")
	 *
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	public function suppStatut($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de suppression d'un statut
    {
        // Récupération du statut d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Statut::class);
		$statut = $rep->find($id);
		
		// Création du formulaire à partir du statut récupéré
		$form = $this->createForm(StatutType::class, $statut);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			
			// mise à jour de la bdd
			$res = $rep->suppStatut($id);
			$em->persist($statut);
			$em->flush();
				
			// Réaffichage de la liste des statuts
			$lesStatuts = $rep->listeStatuts();
			$lesStatutsPagines = $paginator->paginate(
				$lesStatuts, // Requête contenant les données à paginer (ici nos statuts)
				$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
				4 // Nombre de résultats par page
			);
			return $this->render('security/statut.html.twig', Array('lesStatuts' => $lesStatutsPagines));
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formStatut.html.twig', array('form' => $form->createView(), 'action' => 'suppression'));
    }
	/**
     * @Route("/security/client/liste", name="adminClientListe")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Gestion des clients
	public function listeClient(Request $request, PaginatorInterface $paginator)
	{
		$manager = $this->getDoctrine()->getManager();
		$rep = $manager->getRepository(Client::class);
		$lesClients = $rep->findAll();
		
		$lesClientsPagines = $paginator->paginate(
            $lesClients, // Requête contenant les données à paginer (ici nos clients)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            4 // Nombre de résultats par page
        );
				
		return $this->render('security/client.html.twig', Array('lesClients' => $lesClientsPagines));
	}
	/**
     * @Route("/security/client/modif/{id}", name="adminClientModif")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Affichage du formulaire de modification d'un client
	public function modifClient($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de modification d'un client
    {
        // Récupération du client d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Client::class);
		$client = $rep->find($id);
		
		
		// Création du formulaire à partir du client récupéré
		$form = $this->createForm(ClientCompletType::class, $client);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// mise à jour de la bdd
				$em->persist($client);
				$em->flush();
				
				// Réaffichage de la liste des clients
				$lesClients = $rep->listeClients();
				$lesClientsPagines = $paginator->paginate(
					$lesClients, // Requête contenant les données à paginer (ici nos clients)
					$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
					4 // Nombre de résultats par page
				);
				return $this->render('security/client.html.twig', Array('lesClients' => $lesClientsPagines));
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formClient.html.twig', array('form' => $form->createView(), 'action' => 'modification'));
	}
	/**
     * @Route("/security/client/supp/{id}", name="adminClientSupp")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	public function suppClient($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de suppression d'un client
    {
        // Récupération du client d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Client::class);
		$client = $rep->find($id);
		
		// Création du formulaire à partir du client récupéré
		$form = $this->createForm(ClientCompletType::class, $client);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			
			// mise à jour de la bdd
			$res = $rep->suppClient($id);
			$em->persist($client);
			$em->flush();
				
			// Réaffichage de la liste des clients
			$lesClients = $rep->listeClients();
			$lesClientsPagines = $paginator->paginate(
				$lesClients, // Requête contenant les données à paginer (ici nos clients)
				$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
				4 // Nombre de résultats par page
			);
			return $this->render('security/client.html.twig', Array('lesClients' => $lesClientsPagines));
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formClient.html.twig', array('form' => $form->createView(), 'action' => 'suppression'));
    }
	/**
     * @Route("/security/formation/liste", name="adminFormationListe")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Gestion des formations
	public function listeFormation(Request $request, PaginatorInterface $paginator)
	{
		$manager = $this->getDoctrine()->getManager();
		$rep = $manager->getRepository(Formation::class);
		$lesFormations = $rep->findAll();
		
		$lesFormationsPagines = $paginator->paginate(
            $lesFormations, // Requête contenant les données à paginer (ici nos formations)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            4 // Nombre de résultats par page
        );
		
		return $this->render('security/formation.html.twig', Array('lesFormations' => $lesFormationsPagines));
	}
	/**
     * @Route("/security/formation/modif/{id}", name="adminFormationModif")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Affichage du formulaire de modification d'une formation
	public function modifFormation($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de modification d'un client
    {
        // Récupération de la formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Formation::class);
		$formation = $rep->find($id);
		
		
		// Création du formulaire à partir de la formation récupérée
		$form = $this->createForm(FormationType::class, $formation);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// mise à jour de la bdd
				$em->persist($formation);
				$em->flush();
				
				// Réaffichage de la liste des formations
				$lesFormations = $rep->listeFormations();
				$lesFormationsPagines = $paginator->paginate(
					$lesFormations, // Requête contenant les données à paginer (ici nos formations)
					$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
					4 // Nombre de résultats par page
				);
				return $this->render('security/formation.html.twig', Array('lesFormations' => $lesFormationsPagines));
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formFormation.html.twig', array('form' => $form->createView(), 'action' => 'modification'));
	}
	/**
     * @Route("/security/formation/supp/{id}", name="adminFormationSupp")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	public function suppFormation($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de suppression d'une formation
    {
        // Récupération de la formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Formation::class);
		$formation = $rep->find($id);
		
		// Création du formulaire à partir de la formation récupérée
		$form = $this->createForm(FormationType::class, $formation);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			
			// mise à jour de la bdd
			$res = $rep->suppFormation($id);
			$em->persist($formation);
			$em->flush();
				
			// Réaffichage de la liste des formations
			$lesFormations = $rep->listeFormations();
			$lesFormationsPagines = $paginator->paginate(
				$lesFormations, // Requête contenant les données à paginer (ici nos formations)
				$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
				4 // Nombre de résultats par page
			);
			return $this->render('security/formation.html.twig', Array('lesFormations' => $lesFormationsPagines));
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formFormation.html.twig', array('form' => $form->createView(), 'action' => 'suppression'));
    }
	/**
     * @Route("/security/session/liste", name="adminSessionListe")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Gestion des sessions de formation
	public function listeSession(Request $request, PaginatorInterface $paginator)
	{
		$manager = $this->getDoctrine()->getManager();
		$rep = $manager->getRepository(Session_formation::class);
		$lesSessions = $rep->findAll();
		
		$lesSessionsPagines = $paginator->paginate(
            $lesSessions, // Requête contenant les données à paginer (ici nos sessions de formation)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            4 // Nombre de résultats par page
        );
		
		return $this->render('security/session.html.twig', Array('lesSessions' => $lesSessionsPagines));
	}
	/**
     * @Route("/security/session/modif/{id}", name="adminSessionModif")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Affichage du formulaire de modification d'une session de formation
	public function modifSession($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de modification d'une session de formation
    {
        // Récupération de la session de formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Session_formation::class);
		$session = $rep->find($id);
		
		// Création du formulaire à partir de la session de formation récupérée
		$form = $this->createForm(SessionType::class, $session);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// mise à jour de la bdd
				$em->persist($session);
				$em->flush();
				
				// Réaffichage de la liste des sessions de formation
				$lesSessions = $rep->listeSessions();
				$lesSessionsPagines = $paginator->paginate(
					$lesSessions, // Requête contenant les données à paginer (ici nos sessions de formation)
					$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
					4 // Nombre de résultats par page
				);
				return $this->render('security/session.html.twig', Array('lesSessions' => $lesSessionsPagines));
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formSession.html.twig', array('form' => $form->createView(), 'action' => 'modification'));
	}
	/**
     * @Route("/security/session/supp/{id}", name="adminSessionSupp")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	public function suppSession($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de suppression d'une session de formation
    {
        // Récupération de la formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Session_formation::class);
		$session = $rep->find($id);
		
		// Création du formulaire à partir de la session de formation récupérée
		$form = $this->createForm(SessionType::class, $session);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			
			// mise à jour de la bdd
			$res = $rep->suppSession($id);
			$em->persist($session);
			$em->flush();
				
			// Réaffichage de la liste des sessions de formation
			$lesSessions = $rep->listeSessions();
			$lesSessionsPagines = $paginator->paginate(
				$lesSessions, // Requête contenant les données à paginer (ici nos sessions de formation)
				$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
				4 // Nombre de résultats par page
			);
			return $this->render('security/session.html.twig', Array('lesSessions' => $lesSessionsPagines));
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formSession.html.twig', array('form' => $form->createView(), 'action' => 'suppression'));
    }
	/**
     * @Route("/security/plan/liste", name="adminPlanListe")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Gestion des plans de formation
	public function listePlanFormation(Request $request, PaginatorInterface $paginator)
	{
		$manager = $this->getDoctrine()->getManager();
		$rep = $manager->getRepository(Plan_formation::class);
		$lesPlans = $rep->findAll();
		
		$lesPlansPagines = $paginator->paginate(
            $lesPlans, // Requête contenant les données à paginer (ici nos plans de formation)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            4 // Nombre de résultats par page
        );
		
		return $this->render('security/plan.html.twig', Array('lesPlans' => $lesPlansPagines));
	}
	/**
     * @Route("/security/plan/modif/{id}", name="adminPlanModif")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	// Affichage du formulaire de modification d'un plan de formation
	public function modifPlanFormation($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de modification d'un plan de formation
    {
        // Récupération du plan de formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Plan_formation::class);
		$plan = $rep->find($id);
		
		// Création du formulaire à partir du plan de formation récupéré
		$form = $this->createForm(PlanFormationType::class, $plan);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// mise à jour de la bdd
				$em->persist($plan);
				$em->flush();
				
				// Réaffichage de la liste des plans de formation
				$lesPlans = $rep->listePlans();
				$lesPlansPagines = $paginator->paginate(
					$lesPlans, // Requête contenant les données à paginer (ici nos plans de formation)
					$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
					4 // Nombre de résultats par page
				);
				return $this->render('security/plan.html.twig', Array('lesPlans' => $lesPlansPagines));
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formPlan.html.twig', array('form' => $form->createView(), 'action' => 'modification'));
	}
	/**
     * @Route("/security/plan/supp/{id}", name="adminPlanSupp")
	 * 
	 * @Security("is_granted('ROLE_ADMIN')")
     */
	public function suppPlanFormation($id, Request $request, PaginatorInterface $paginator) // Affichage du formulaire de suppression d'un plan de formation
    {
        // Récupération du plan de formation d'identifiant $id
		$em = $this->getDoctrine()->getManager();
		$rep = $em->getRepository(Plan_formation::class);
		$plan = $rep->find($id);
		
		// Création du formulaire à partir du plan de formation récupéré
		$form = $this->createForm(PlanFormationType::class, $plan);
		
		// Mise à jour de la bdd si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			
			// mise à jour de la bdd
			$res = $rep->suppPlan($id);
			$em->persist($plan);
			$em->flush();
				
			// Réaffichage de la liste des plans de formation
			$lesPlans = $rep->listePlans();
			$lesPlansPagines = $paginator->paginate(
				$lesPlans, // Requête contenant les données à paginer (ici nos plans de formation)
				$request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
				4 // Nombre de résultats par page
			);
			return $this->render('security/plan.html.twig', Array('lesPlans' => $lesPlansPagines));
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('security/formPlan.html.twig', array('form' => $form->createView(), 'action' => 'suppression'));
    }
}
