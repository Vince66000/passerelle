<?php


namespace App\Controller;

use GuzzleHttp\Client;
//use Psr\Http\Client\Client;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;


class connectController extends AbstractController
{

    private Client $client;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;


    public function __construct(Client $client)
    {
        $this->client = $client;


    }

    public function getToken():HttpFoundation\Response
    {
        $uriToken = 'https://aeb.a26.fr/dossier/restapi/v1/Utilisateurs/Login?id=admin&password=Expertise66';
        $request = $this->client->post($uriToken, ['verify' => false, 'headers' =>  ['X-Avensys-API-Key' => 'puKukTaOh0zDxnMg0zD4DAeWoKnTIKlz'] ]);

        $response = $request->getBody()->getContents();
        $reponse2 = json_decode($response, true);
        $refreshToken = $reponse2['refresh_token'];
        $headers = [];


        return new HttpFoundation\Response( $refreshToken, 200, $headers);

    }

    /**
     * @return string
     * @Route("/", name="index")
     */
    public function getDate()
    {


        return $this->render("date.html.twig");
    }

    /**
     *
     * @Route("/date2", name="date2")
     */
    public function getDate2():HttpFoundation\Response
    {

        $dateDebut = strtotime($_POST["dateDebut"]);
        $dateFin =  strtotime($_POST["dateFin"]);

        $dateDebut2 = date("d/m/Y", $dateDebut);
        $dateFin2 = date("d/m/Y", $dateFin);

        $dateArr[0] = $dateDebut2;
        $dateArr[1] = $dateFin2;

        return $this->redirectToRoute('dossiers', ['dateDebut' => $dateDebut2, 'dateFin' => $dateFin2]);

    }

    /**
     *
     * @return HttpFoundation\Response
     * @Route("/dossiers", name="dossiers")
     * requête de récupération des dossiers
     */
    public function getFile() :HttpFoundation\Response
    {

        $DateArr = $this->getDate2();
        $dateDebut = "";
        $dateFin = "";


            if (isset($_POST["dateDebut"]))
            {
                $dateDebut = strtotime($_POST["dateDebut"]);

            }
            else
            {
                $dateDebut = strtotime($DateArr[0]);
            }

            if (isset($_POST["dateFin"]))
            {
                $dateFin =  strtotime($_POST["dateFin"]);

            }
            else
            {
                $dateFin = strtotime($DateArr[1]);

            }

        $dateFin2 = date('d/m/Y', $dateFin);
        $dateDebut2 = date('d/m/Y', $dateDebut);
        $dateArr[0] = $dateDebut2;
        $dateArr[1] = $dateFin2;

        $tokenButton = 0;
        $token = $this->getToken()->getContent();


        $uriListAff = 'https://aeb.a26.fr/dossier/restapi/v1/DossiersLight/Search';
        $request = $this->client->get($uriListAff, ['verify' => false, 'headers' =>  ['X-Avensys-API-Key' => 'puKukTaOh0zDxnMg0zD4DAeWoKnTIKlz',
            'Authorization' => 'Bearer ' . $token ], 'query' => ['dateCreationDebut' => $dateArr[0], 'dateCreationFin' => $dateArr[1]]]);
        $response = $request->getBody()->getContents();
        $response2 = json_decode($response);


        return  $this->render('dossiers.html.twig', [
            'responses' => $response2,
            'button' => $tokenButton,
            'dateDebut' => $dateDebut2,
            'dateFin' => $dateFin2,
        ]);


    }

    /**
     * @Route("test/{id}", name="test")
     * @param Request $request
     * @return HttpFoundation\RedirectResponse
     */
    public function affiche(Request $request)
    {

        $param = $request->attributes->get('_route_params');
        $k = '';

        $boolButton = 0;
        foreach ($param as $k ) {
            $k = $param["id"];
        }

        /**
         * récupération token.
         */
        $token = $this->getToken()->getContent();


        /**
         * récupération des infos du dossier
         */
        $uriAff = 'https://aeb.a26.fr/dossier/restapi/v1/Dossiers';
        $requestAff = $this->client->get($uriAff, ['verify' => false, 'headers' =>  ['X-Avensys-API-Key' => 'puKukTaOh0zDxnMg0zD4DAeWoKnTIKlz',
            'Authorization' => 'Bearer ' . $token ], 'query' => ['idDossier' => $k]]);
        $responseAff = $requestAff->getBody()->getContents();
        $responseAff2 = json_decode($responseAff, true);


        $idComp = $responseAff2[0]['IdCompagnie'];
        $codeExpert = $responseAff2[0]['IdActeurExpert'];

        /**
         * récupération des infos du client attaché au dossier
         */
        $uriComp = 'https://aeb.a26.fr/dossier/restapi/v1/Compagnies/{idCompagnie}';
        $requestComp = $this->client->get($uriComp, ['verify' => false, 'headers' =>  ['X-Avensys-API-Key' => 'puKukTaOh0zDxnMg0zD4DAeWoKnTIKlz',
            'Authorization' => 'Bearer ' . $token ], 'query' => ['idCompagnie' => $idComp]]);
        $responseComp = $requestComp->getBody()->getContents();
        $responseComp2 = json_decode($responseComp, true);

//        var_dump($responseComp2);


        $codeClient = 1;
        $CPVille = $responseComp2['Adresse']['CPVille'];
        $CPVille2 = explode(' ',$CPVille);

        $nomComp =  ($responseComp2['Adresse']['Nom']);
        $adresseComp = ($responseComp2['Adresse']['Adr1']);
        $CP = ($CPVille2[0]);
        $ville = ($CPVille2[1]);
        $numAff = $responseComp2['Id'];

        /**
         * on vérifie que le client n'existe pas déjà. SI oui => on récupère l'ID, sinon on créé le client
         */
        $ifExist = $this->connecteur()->prepare('SELECT rowid
                                                        FROM llx_societe                                           
                                                        WHERE nom like :nom');
        $ifExist->bindParam(':nom', $nomComp);
        $ifExist->execute();
        $rez = $ifExist->fetch();
        $bouh = 0;
        $rowid = $rez;
        if($rez == false)
        {
            $newClient = $this->connecteur()->prepare('INSERT INTO llx_societe( nom, client, address,zip, town, datec)
                                                            VALUES (:nom, :client, :address, :zip, :town, now())');
            $newClient->bindParam(':nom', $nomComp);
            $newClient->bindParam(':client', $codeClient);
            $newClient->bindParam(':address', $adresseComp);
            $newClient->bindParam(':zip', $CP);
            $newClient->bindParam(':town', $ville);
            $newClient->execute();
            $bouh = 'ok';

            $maxClient = $this->connecteur()->query('SELECT MAX(rowid) as MaxClient from llx_societe');
            $maxClient2 = $maxClient->fetch();
            $maxClient3 = $maxClient2['MaxClient'];


            $numDefinitif = $this->numAffaire($numAff, $codeExpert);


            $title = $responseAff2[0]['Libelle'];
            $userCreat = 1;
            $newProject = $this->connecteur()->prepare('INSERT INTO llx_projet( fk_soc, ref, title, datec, dateo, fk_user_creat) 
                                        VALUES(
                                               :fk_soc,
                                               :ref,
                                               :title,
                                               now(),
                                               now(),
                                               :fk_user_creat
                                        )');
            $newProject->bindParam(':fk_soc', $maxClient3);
            $newProject->bindParam(':ref', $numDefinitif);
            $newProject->bindParam(':title', $title);
            $newProject->bindParam(':fk_user_creat', $userCreat);
            $bouh = "user + projet créé";
            $newProject->execute();
            $err = $newProject->errorInfo();

                var_dump($err);


        }
        else
        {
            $rowid = (int)$rez["rowid"];
            $maxProj = $this->connecteur()->query('SELECT MAX(ref) as max FROM LLX_projet');
//            $numProj = $maxProj->fetch();

            $numDefinitif = $this->numAffaire( $numAff, $codeExpert);


            $title = $responseAff2[0]['Libelle'];
            $userCreat = 1;
            $newProject = $this->connecteur()->prepare('INSERT INTO llx_projet( fk_soc, ref, title, datec, dateo, fk_user_creat) 
                                        VALUES(
                                               :fk_soc,
                                               :ref,
                                               :title,
                                               now(),
                                               now(),
                                               :fk_user_creat
                                        )');
            $newProject->bindParam(':fk_soc', $rowid);
            $newProject->bindParam(':ref', $numDefinitif);
            $newProject->bindParam(':title', $title);
            $newProject->bindParam(':fk_user_creat', $userCreat);

            $bouh = 'projet créé';
            $newProject->execute();
            $err = $newProject->errorInfo();
            if (!empty($err))
            {
                var_dump($err);
            }
        }



        return $this->redirectToRoute('dossiers');
//        return new HttpFoundation\Response($boolButton,200,[]);

    }

//    const dbLocal = "aeb_dol";
//    const usrLocal = "aeb.dol";
//    const mdpLocal = 'DmPzTs61NzG4';


    const dbLocal = "dolibarr";
    const usrLocal = "vincent";
    const mdpLocal = 'root';

    /**
     * @return \PDO
     * Connecteur BDD
     */
    public function connecteur()
    {
        return    $db = new \PDO('mysql:host=localhost;dbname='.self::dbLocal.'', ''.self::usrLocal.'', ''.self::mdpLocal.'');
    }

    /**
     * @param $numAff
     * @param $codeExpert
     * @return string
     * Prend le résultat de la requête maxProj, l'explose, prend la partie chiffre, l'augmente de 1, met à jour la date, et retourne le numéro d'affaire
     * modifié.
     */
    public function numAffaire( $numAff,$codeExpert)
    {
//        $numProjExpl = explode('-',$numAff);
//        $numProj3 = $numProjExpl[0];
//        $aff  = substr($numProj3, 3);
//        $count = strlen($aff) ;

//        $aff2 = $aff + 1;
//        if (strlen($aff2) == 1)
//        {
//            $aff = '00000' . $aff2;
//        }
//        elseif (strlen($aff2) == 2)
//        {
//            $aff = '0000' . $aff2;
//        }

        $date = date('Ym');

        return $numAff =  $numAff . '-' . $date . '-' . $codeExpert;

    }




}