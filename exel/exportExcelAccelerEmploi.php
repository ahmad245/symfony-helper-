<?php

namespace UfmcpBundle\Controller;

use function explode;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use UfmcpBundle\Entity\Commande;

class CommandeController extends Controller
{
	/**
	 * @Route("/commande/detail/{annee}/{mois}/{numero}", name="commandes_detail", options={"expose":true})
	 * @Route("/commande/liste/{annee}/{mois}/{numero}", name="commandes_liste", options={"expose":true})
	 * @param Request $request
	 * @param int $annee
	 * @param int $mois
	 * @param int $numero
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function detailCommandeAction(Request $request , int $annee=-1, int $mois=-1, int $numero=-1)
	{
		$em = $this->getDoctrine()->getManager();
		$session = new Session();
		
		$readonly = ($request->get('_route') != 'commandes_detail');
		
		if($request->isMethod('post') && !$readonly) {
			$annee = $request->request->get('annee');
			$mois = $request->request->get('mois');
			$numero = $request->request->get('numero');
			$listeCmdLieuPresta = $request->request->get('lieu_presta');
			$listeCmdPrestataire = $request->request->get('prestataire');
			$ListeCmdDate = $request->request->get('date');
			$ListeCmdHeure= $request->request->get('heure');
			$ListeCmdDate2 = $request->request->get('date2');
			$ListeCmdHeure2= $request->request->get('heure2');
			
			$commandes = $em->getRepository('UfmcpBundle:Commande')->findByMoisAndNum($annee, $mois, $numero);

			
			foreach ($commandes as $commande) {
				$cmdId = $commande->getId();
				/*$idLieuPresta = $listeCmdLieuPresta[$cmdId];
				$cmdLieuPresta = $em->getRepository('UfmcpBundle:LieuFormation')->find($idLieuPresta);*/
				$idPrestataire = $listeCmdPrestataire[$cmdId];
				$cmdPrestataire = $em->getRepository('UfmcpBundle:Organisme')->find($idPrestataire);

                $cmdDate = $ListeCmdDate[$cmdId];
                $cmdHeure = $ListeCmdHeure[$cmdId];
                if (empty($cmdDate)) {
                    $cmdDateHeure = null;
                } else if (empty($cmdHeure)) {
                    $cmdDateHeure = \DateTime::createFromFormat('d/m/Y H:i', $cmdDate . " 00:00");
                } else {
                    $cmdDateHeure = \DateTime::createFromFormat('d/m/Y H:i', $cmdDate . " " . $cmdHeure);
                }

                $cmdDate2 = $ListeCmdDate2[$cmdId];
                $cmdHeure2 = $ListeCmdHeure2[$cmdId];
                if (empty($cmdDate2)) {
                    $cmdDateHeure2 = null;
                } else if (empty($cmdHeure2)) {
                    $cmdDateHeure2 = \DateTime::createFromFormat('d/m/Y H:i', $cmdDate2 . " 00:00");
                } else {
                    $cmdDateHeure2 = \DateTime::createFromFormat('d/m/Y H:i', $cmdDate2 . " " . $cmdHeure2);
                }

//				$commande->setLieuFormation($cmdLieuPresta);
				$commande->setPrestataire($cmdPrestataire);
				$commande->setDateHeureInfoColl($cmdDateHeure);
				$commande->setDateHeureInfoColl2($cmdDateHeure2);
				
				$em->persist($commande);
			}
			
			$em->flush();
			
			$session->getFlashBag()->add("info", "Les commandes ont été modifiées.");
			
			return $this->redirectToRoute('commandes_detail', ['annee' => $annee, 'mois' => $mois, 'numero' => $numero]);
		}
		
		$results = $em->getRepository('UfmcpBundle:Commande')->getListeMoisAndNum();
		$liste_annees = [];
		$liste_mois = [];
		$liste_num = [];
		foreach($results as $result) {
			$a = (int) $result['mois']->format("Y");
			if (!in_array($a, $liste_annees)) {
				$liste_annees[] = $a;
			}
			if (!isset($liste_mois[$a])) {
				$liste_mois[$a] = [];
				$liste_num[$a] = [];
			}
			
			$m = (int) $result['mois']->format("m");
			$liste_mois[$a][] = $m;
			$liste_num[$a][$m] = explode(";", $result['list_num']);
		}
		
		if (!in_array($annee, $liste_annees)) {
			$annee = max($liste_annees);
		}
		if (!in_array($mois, $liste_mois[$annee])) {
			$mois = max($liste_mois[$annee]);
		}
		if (!in_array($numero, $liste_num[$annee][$mois])) {
			$numero = 1;
		}
		
		$prestataires = $em->getRepository('UfmcpBundle:Organisme')->findAll();
		$lieuxPrestation = $em->getRepository('UfmcpBundle:LieuFormation')->findAll();
		$commandes = $em->getRepository('UfmcpBundle:Commande')->findByMoisAndNum($annee, $mois, $numero);


		
		return $this->render(':commande:detail.html.twig', [
			'base_dir'      	=> realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
			'titre' => 'Dates RV collectifs',
			'commandes' => $commandes,
			'prestataires' => $prestataires,
			'lieuxPrestation' => $lieuxPrestation,
			'libelles_mois' => ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
			'annee' => $annee,
			'mois' => $mois,
			'numero' => $numero,
			'liste_mois' => $liste_mois,
			'liste_annees' => $liste_annees,
			'liste_num' => $liste_num,
			'readonly' => $readonly
		]);
	}
	
	/**
	 * @Route("/commande/importer", name="commande_importer")
	 *
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response|null
	 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
	 */
	public function commandeImportAction(Request $request) {
		$em = $this->getDoctrine()->getManager();
		
		if($request->isMethod('post')) {
			$file = $request->files->get('fichier_commande');
			$formatCorrect = false;
			
			$msgError = $msgInfo = '';
			
			if(!empty($file)) {
				$formatCorrect = in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);
			}
			
			if(!empty($file) && $formatCorrect) {
				switch($file->getClientOriginalExtension()) {
					case 'xlsx':
						$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
						break;
						
					case 'xls':
						$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
						break;
				}
				
				$reader->setReadDataOnly(true);
				$spreadsheet = $reader->load($file->getPathname());
				
				$comAgenceVolume = [];
				$nbVolumesVides = 0;
				
				// Recherche de "SITES" colonne B
				$sheet = $spreadsheet->getActiveSheet();
				$col = 2;
				$rowStart = 2;
				$rowSite = null;
				$rowTotal = null;
				
				$annee = null;
				$moisCommande = null;
				
				for ($row = $rowStart; $row < 100; $row++) {
					$val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					
					if (trim($val) == 'SITES') {
						$rowSite = $row;
					}
					
					if (!empty($val) && strpos($val, 'Total ') !== false) {
						$rowTotal = $row;
					}
					
					if ($rowSite !== null && $rowTotal !== null) {
						break;
					}
				}
				
				if ($rowSite === null || $rowTotal === null) {
					$msgError = "Aucun site n'a été trouvé dans le fichier.";
				} else {
					// Recherche de l'année et du mois
					for ($row = 2; $row < $rowSite; $row++) {
						for ($col = 3; $col < 8; $col++) {
							$val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
							if (stripos($val, 'annee') !== false || stripos($val, 'année') !== false) {
								$val = explode(' ', $val);
								if (count($val) > 1 && is_numeric(trim($val[1]))) {
									$annee = trim($val[1]);
									break;
								}
							}
						}
						
						if ($annee !== null) {
							break;
						}
					}
					
					if ($annee === null) {
						$msgError = "L'année n'a pas été trouvée dans le fichier.";
					} else {
						$mois = '';
						$moisPrefix = '';
						
						for ($row = 2; $row < $rowSite; $row++) {
							for ($col = 3; $col < 8; $col++) {
								$val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
								
								if (stripos($val, 'Commandes pour le mois') !== false) {
									$mois = $sheet->getCellByColumnAndRow($col + 1, $row)->getValue();
									if (!empty($mois)) {
										$mois = mb_strtolower($mois, 'UTF-8');
										
										switch ($mois) {
											case 'janvier':
												$moisCommande = new \DateTime($annee . '-01-01');
												$moisPrefix = 'de';
												break;
											
											case 'février':
											case 'fevrier':
												$moisCommande = new \DateTime($annee . '-02-01');
												$moisPrefix = 'de';
												break;
											
											case 'mars':
												$moisCommande = new \DateTime($annee . '-03-01');
												$moisPrefix = 'de';
												break;
											
											case 'avril':
												$moisCommande = new \DateTime($annee . '-04-01');
												$moisPrefix = "d'";
												break;
											
											case 'mai':
												$moisCommande = new \DateTime($annee . '-05-01');
												$moisPrefix = 'de';
												break;
											
											case 'juin':
												$moisCommande = new \DateTime($annee . '-06-01');
												$moisPrefix = 'de';
												break;
											
											case 'juillet':
												$moisCommande = new \DateTime($annee . '-07-01');
												$moisPrefix = 'de';
												break;
											
											case 'août':
											case 'aout':
												$moisCommande = new \DateTime($annee . '-08-01');
												$moisPrefix = "d'";
												break;
											
											case 'septembre':
												$moisCommande = new \DateTime($annee . '-09-01');
												$moisPrefix = 'de';
												break;
											
											case 'octobre':
												$moisCommande = new \DateTime($annee . '-10-01');
												$moisPrefix = "d'";
												break;
											
											case 'novembre':
												$moisCommande = new \DateTime($annee . '-11-01');
												$moisPrefix = 'de';
												break;
											
											case 'décembre':
											case 'decembre':
												$moisCommande = new \DateTime($annee . '-12-01');
												$moisPrefix = 'de';
												break;
										}
									}
								}
							}
						}
						
						//TODO:
						// Dupliquer MONTBARD-CHATILLON en CHATILLON
						$col = 2;
						for ($row = $rowSite + 1; $row < $rowTotal; $row++) {
							$val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
							if (!empty($val) && !is_numeric($val) && strpos($val, 'TOTAL') === false) {
								// Fakes : TONNERRE, CHATEAU CHINON, TOURNUS
								/*if ($val == 'Montbard') {
									$val = 'Montbard-Chatillon';
								} else */if ($val == 'Cosne Sur Loire') {
									$val = 'COSNE COURS SUR LOIRE';
								} else if ($val == 'Auxerre') {
									$val = 'AUXERRE CLAIRIONS';
								}
								
								$agence = $em->getRepository('UfmcpBundle:Prescripteur')->findOneBy([
									'email' => null,
									'nom' => ' ',
									'prenom' => ' ',
									'libelleStructure' => $val
								]);
								
								if (!empty($agence)) {
									// Lecture du volume
									$volume = $sheet->getCellByColumnAndRow($col + 1, $row)->getValue();
									
									if (!is_numeric($volume)) {
										// Problème de volume
									} else {
										$comAgenceVolume[] = [
											'agence' => $agence,
											'volume' => $volume
										];
										
										if($volume == 0) {
											$nbVolumesVides++;
										}
									}
								}
							}
						}
						
						if (!empty($comAgenceVolume)) {
							foreach ($comAgenceVolume as $com) {
								$commande = $em->getRepository('UfmcpBundle:Commande')->findOneBy([
									'mois' => $moisCommande,
									'numero' => 1,
									'prescripteur' => $com['agence']
								]);
								
								if (empty($commande)) {
									$commande = new Commande();
									$commande->setMois($moisCommande);
									$commande->setNumero(1);
									$commande->setPrescripteur($com['agence']);
									$commande->setLieuFormation($com['agence']->getLieuFormation());
									if (!empty($com['agence']->getLieuFormation()) && $com['agence']->getLieuFormation()->getOrganismesLieux()->count() > 0) {
										$commande->setPrestataire($com['agence']->getLieuFormation()->getOrganismesLieux()->first()->getOrganismeSite()->getOrganisme());
									}
								}
								
								$commande->setVolume($com['volume']);
								
								$em->persist($commande);
							}
						}
						
						$em->flush();
						
						$msgInfo = 'Les commandes pour ' . count($comAgenceVolume) . ' sites ont été importées pour le mois '.$moisPrefix.$mois.' '.$annee
							.($nbVolumesVides > 0 ? ", dont $nbVolumesVides sans volume indiqué." : '.');
					}
				}
			} else {
				if(!$formatCorrect) {
					$msgError = 'Format de fichier incorrect, le fichier doit être au format Excel (xls, xlsx).';
				} else {
					$msgError = 'Aucun fichier n\'a été déposé.';
				}
			}
			
			$session = new Session();
			if(!empty($msgError)) {
				$session->getFlashBag()->add('alert', $msgError);
			} else {
				$session->getFlashBag()->add('info', $msgInfo);
			}
		}
		
		return $this->render(':commande:importer.html.twig', [
			'base_dir'      	=> realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
			'titre'         	=> "Importer une commande"
		]);
	}
	
//	/**
//	 * @Route("/commande/export_xls/{mois}/{annee}/{numero}", name="commande_export", options={"expose":true}, defaults={"numero":1})
//	 * @param Request $request
//	 */
//	public function exportXlsAction(Request $req, $mois, $annee, $numero = 1)
//	{
//		$em = $this->getDoctrine()->getManager();
//
//		$dtMois = \DateTime::createFromFormat('Y-m-d', $annee.'-'.sprintf('%02d', $mois).'-01');
//		$commandes = $em->getRepository('UfmcpBundle:Commande')->findBy([
//			'mois' => $dtMois,
//			'numero' => $numero
//		]);
//		//dump($commandes);die;
//
//		$commandeData=[
//			'mois' => $dtMois
//		];
//		foreach ($commandes as $commande){
//			$commandeData[ucwords(strtolower($commande->getPrescripteur()->getLibelleStructure()))]=  [
//				$commande->getVolume(),
//				'',
//				!is_null($commande->getLieuFormation()) ? $commande->getLieuFormation()->getAdresse().' '.$commande->getLieuFormation()->getCp().' '.$commande->getLieuFormation()->getVille() : '',
//				!is_null($commande->getDateHeureInfoColl())? $commande->getDateHeureInfoColl() ->format('d/m/Y'):'',
//				!is_null($commande->getDateHeureInfoColl())? $commande->getDateHeureInfoColl()->format('H\hi'):'', !is_null($commande->getPrestataire())? $commande->getPrestataire() ->getNom(true) : ''
//
//			];
//		}
//
//		$res = $this->get('ufmcp.excel_generator')->generateXlsCommandesFromTemplate($commandeData, $commande->getDateCreation());
//
//		return $res;
//	}
	
	/**
	 * @Route("/commande/edit", name="commande_save", options={"expose":true})
	 * @param Request $request
	 */
	public function commandeEditAJAX(Request $request) {
		$em = $this->getDoctrine()->getManager();

		$idCommande = $request->request->get('commande');
		if($idCommande !== null) {
			$commande = $em->getRepository('UfmcpBundle:Commande')->findOneById($idCommande);
			
			if($commande !== null) {
				if($request->request->has('value')) { // modif de date/heure
					$modifInfoColl2 = (bool) $request->request->get('isInfoColl2');
					$set = 'setDateHeureInfoColl';
					
					if($modifInfoColl2 ) {
						$set .= '2';
					}
					
					$value = $request->request->get('value');
					if($value !== '') {
						$dateHeure = \DateTime::createFromFormat('d/m/Y H:i' , $value);
					} else {
						$dateHeure = null;
					}
					$commande->$set($dateHeure);
				} elseif($request->request->has('prestataire')) { // modif de prestataire
					$idPrestataire = $request->request->get('prestataire');
					$prestataire = $em->getRepository('UfmcpBundle:Organisme')->find($idPrestataire);
					
					if($prestataire !== null) {
						$commande->setPrestataire($prestataire);
					}
				} else {
					return new JsonResponse(['succes' => false]);
				}
				
				$em->persist($commande);
				$em->flush();
				return new JsonResponse(['succes' => true]);
			}
		}
		
		return new JsonResponse(['succes' => false]);
	}


    /**
     * @Route("/commande/export_xls/{mois}/{annee}/{numero}", name="commande_export", options={"expose":true}, defaults={"numero":1})
     * @param Request $request
     */
    public function exportXlsAction(Request $req, $mois, $annee, $numero = 1)
    {
        $em = $this->getDoctrine()->getManager();

        $dtMois = \DateTime::createFromFormat('Y-m-d', $annee.'-'.sprintf('%02d', $mois).'-01');
        $allCommandes=$em->getRepository('UfmcpBundle:Commande')->findAll();

        $prescripteur=$em->getRepository('UfmcpBundle:Prescripteur')->find(3);


        $semaines =$this->getSemaines($annee,$mois);

        $commandes = $em->getRepository('UfmcpBundle:Commande')->findBy([
            'mois' => $dtMois,
            'numero' => $numero,
        //  'id'=>3
        ]);
        $AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteurs();


//       dump($commandes);die;


       // $datePrescripteur=[];

        $allAgenc = [];
        $allJourOut = [];
        $AllterritoireArray = [];
        $allNameOfAgance = [];
        foreach ($AllPreinscripteurs as $preinscripteur) {
            $commandes=$preinscripteur->getCommandes();
            $datePrescripteur=[];
            foreach ($commandes as $commande) {


                if ($commande->getDateHeureInfoColl() && $commande->getDateHeureInfoColl2()) {
                    if ($commande->getDateHeureInfoColl()) {
                        $datePrescripteur[$commande->getDateHeureInfoColl()->format('d/m/Y H:i')] = (int)round($commande->getVolume() / 2, 0);
                    }
                    if ($commande->getDateHeureInfoColl2()) {
                        $datePrescripteur[$commande->getDateHeureInfoColl2()->format('d/m/Y H:i')] = (int)floor($commande->getVolume() / 2);
                    }
                } elseif ($commande->getDateHeureInfoColl()) {
                    $datePrescripteur[$commande->getDateHeureInfoColl()->format('d/m/Y H:i')] = (int)$commande->getVolume();
                } elseif ($commande->getDateHeureInfoColl2()) {
                    $datePrescripteur[$commande->getDateHeureInfoColl2()->format('d/m/Y H:i')] = (int)$commande->getVolume();
                }
                //   dump( $commande->getPrescripteur()->getLieuFormation()->getOrganismesLieux()->first()->getOrganismeSite()->getOrganisme()->getNom());die;
            }
         //   dump($datePrescripteur);

            $organiseData = $this->organiseData($semaines, $mois, $datePrescripteur, $preinscripteur);
            $wArray = $organiseData[0];
            $jourOut = $organiseData[1];
            $territoireArray = $organiseData[2];




            array_push($allAgenc, $wArray);
            array_push($allJourOut, $jourOut);
            array_push($AllterritoireArray, $territoireArray);
            array_push($allNameOfAgance, $commande->getPrescripteur()->getLibelleStructure());

        }
//        dump($allAgenc, $allJourOut, $AllterritoireArray, $allAgenc);
     //   die;
       // dump($AllterritoireArray,$allAgenc,$allJourOut,$allNameOfAgance);   die;

        $res = $this->get('ufmcp.excel_generator')->generateFilesPlanification($AllterritoireArray,$allAgenc,$allJourOut,$allNameOfAgance,$mois,$annee);
        $TOKEN = "downloadToken";

        return $res;

        exit;
        die();

       // $res = $this->get('ufmcp.excel_generator')->generateXlsCommandesFromTemplate($commandeData, $commande->getDateCreation());

       // return $res;
    }





    public  function  getSemaines($annee,$mois){
        $dateDebut = new \DateTime($annee."-".sprintf("%02d", $mois)."-01");
        $nextMonth = clone $dateDebut;
        $nextMonth->modify("first day of next month");
        $dateFin = clone($nextMonth);
        $dateFin->modify("-1 day");
        $semaines = $this->get('ufmcp_utils')->splitWeeks($dateDebut, $dateFin, true);
        return $semaines;
        //        $prevMonth = clone $dateDebut;
//        $prevMonth->modify("first day of previous month");
//
//        if($nextMonth->format('Y') > date('Y')) {
//            $nextMonth = clone $dateDebut;
//        }
    }

    public function organiseData($semaines,$mois,$datePrescripteur,$prescripteur){
        $jourOut=[];
        $jArray=[];
        $wArray=[];
        $i=1;
        $semaineIndex=1;
       $days=['Mon'=>'lundi','Tue'=>'mardi','Wed'=>'mercredi','Thu'=>'jeudi','Fri'=>'vendredi'];
        foreach ($semaines as $w){
            $wArray[]=['Semaine '.$semaineIndex,"","8h00", "8h15", "8h30", "8h45", "9h00",
                "9h15", "9h30", "9h45", "10h00", "10h15",
                "10h30", "10h45", "11h00", "11h15", "11h30",
                "11h45", "12h00", "12h15", "12h30", "12h45",
                "13h00", "13h15", "13h30", "13h45", "14h00",
                "14h15", "14h30", "14h45", "15h00", "15h15", "15h30",
                "15h45", "16h00", "16h15", "16h30", "16h45", "17h00",
                "17h15", "17h30", "17h45", "18h00"];
            foreach ($w as $j)
            {
                $sum=0;
                $wArray[]=[
                    $days[$j->format('D')],
                    $j->format('d/m/Y'),
                    array_key_exists($j->format('d/m/Y').' '.'08:00',$datePrescripteur) && ($j->format('m')==$mois) ? $datePrescripteur[$j->format('d/m/Y').' '.'08:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:00'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:15'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:30'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:45'] : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'18:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'18:00'] : ' '

                ];

                // find jour out mois

                if ($j->format('m')!=$mois){
                    array_push($jourOut,$j->format('d/m/Y'));
                }

                $sumRow= array_reduce($wArray[$i],function ($c,$item){

                    // dump($item);
                    if(strpos($item,'red')!==false ){
                        $item=str_replace('red','',$item);
                    }
                    if(is_numeric($item)){
                        $c +=$item;
                    }
                    return $c;
                },0);

                array_push($wArray[$i],$sumRow);
                $i++;
            }
            $i++;
            $semaineIndex++;

        }

        $wArray[]=['TOTAL Plages',""];
        $sumCol = 0;
        $sumTotalAll=0;


        // finding the column sum
        for ($i = 2; $i < 43; ++$i) {
            for ($j = 0; $j < count($wArray)-1; ++$j) {

                if(strpos($wArray[$j][$i],'red')!==false ){
                    $sumCol=$sumCol + (int)str_replace('red','',$wArray[$j][$i]);
                    // dump($sumCol,$wArray[$j][$i]);die;
                }

                if (is_numeric($wArray[$j][$i])){
                    $sumCol = $sumCol + $wArray[$j][$i];
                }

            }
            if ($i==41){

            }

            array_push($wArray[count($wArray)-1],$sumCol);
            $sumTotalAll=$sumTotalAll+$sumCol;
            $sumCol = 0;
        }
        array_push($wArray[count($wArray)-1],$sumTotalAll);






        //$territoire =  $em->getRepository('UfmcpBundle:Territoire')->findFromSession();

        //  dump($prescripteur->getLieuFormation()->getBassin()->getNom(true));die;
        $territoireArray=[
            ["Raison Sociale du Titulaire du Marché (mandataire)","ONLINEFORMAPRO"] ,
            ["N° du Marché","14673 "] ,
            ["Adresse du Titulaire du Marché","19, rue du Praley"] ,
            ["Code Postal et Ville","70000 VESOUL"] ,
            ["Téléphone","03 84 76 52 44"] ,
            ["Adresse électronique Titulaire du Marché","pf@onlineformapro.com"] ,
            ["Nom et téléphone de la personne en charge de la planification","Michèle GUERRIN - 03 84 76 52 44"] ,
            ["Site demandeur",   $prescripteur->getLieuFormation() !== null ? $prescripteur->getLieuFormation()->getBassin()->getNom(true) :  ""] ,
            ["PRESTATAIRE Intervenant", $prescripteur->getLieuFormation() !== null  && $prescripteur->getLieuFormation()->getOrganismesLieux()->first() ?  $prescripteur->getLieuFormation()->getOrganismesLieux()->first()->getOrganismeSite()->getOrganisme()->getNom() : ""] ,
            ["Adresse du lieu de réalisation", $prescripteur->getLieuFormation() !== null ? $prescripteur->getLieuFormation()->getCp().' '.$prescripteur->getLieuFormation()->getVille() :""] ,

        ];
        //  ;die;
        //  dump($wArray);die;
        return  [$wArray,$jourOut,$territoireArray];

    }















}