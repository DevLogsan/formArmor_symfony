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

class ClientController extends AbstractController
{
    /**
     * @Route("/client", name="login_client")
     */
    // Affichage du formulaire d'authentification
	public function authentif(Request $request)
    {
		// Création du formulaire
		$client = new Client();
		$form = $this->createForm(ClientType::class, $client);
		
		// Contrôle du mdp si method POST ou affichage du formulaire dans le cas contraire
		if ($request->getMethod() == 'POST')
		{
			$form->handleRequest($request); // permet de récupérer les valeurs des champs dans les inputs du formulaire.
			if ($form->isValid())
			{
				// Récupération des données saisies (le nom des controles sont du style nomDuFormulaire[nomDuChamp] (ex. : client[nom] pour le nom) )
				$donneePost = $request->request->get('client');
				$nom = $donneePost['nom'];
				$mdp = $donneePost['password'];
				
				// Controle du nom et du mdp
				$manager = $this->getDoctrine()->getManager();
				$rep = $manager->getRepository(Client::class);
				$nbClient = $rep->verifMDP($nom, $mdp);
				if ($nbClient > 0)
				{
					return $this->render('client/accueil.html.twig');
				}
				$request->getSession()->getFlashBag()->add('connection', 'Login ou mot de passe incorrects');
			}
		}
		// Si formulaire pas encore soumis ou pas valide (affichage du formulaire)
		return $this->render('client/connection.html.twig', array('form' => $form->createView()));
    }
}