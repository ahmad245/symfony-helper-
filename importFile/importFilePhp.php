<?php
/**
     * @Route("/commande/importer", name="commande_importer")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function commandeImportAction(Request $request)
    {
        $success=false;
        $info=[];
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
                }
                else {
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

                        $col = 2;
                        for ($row = $rowSite + 1; $row < $rowTotal; $row++) {
                            $val = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                            if (!empty($val) && !is_numeric($val) && strpos($val, 'TOTAL') === false) {
                                // Fakes : TONNERRE, CHATEAU CHINON, TOURNUS
                                if($val == 'Montbéliard Hexagones'){
                                    $val='Montbéliard Hexagone';
                                }
                               elseif($val == 'Pontarlier'){
                                    $val='Pontarlier-City-Parc';
                                }

                                elseif ($val == 'Luxeuil les Bains'){
                                    $val='Luxeuil';
                                }

                                 $val=str_replace(' ','-',$val);
                                if($val == 'Saint-Claude'){
                                    $val='Saint-Claude Carmes';
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
                            $time = strtotime($moisCommande->format('m').'/01/'.$annee);

                            $newformat = date('Y-m-d',$time);
                           foreach ($comAgenceVolume as $com) {
                               $prescripteur = $em->getRepository('UfmcpBundle:Prescripteur')->findOneBy(['id'=>$com['agence']->getId()]);

                               /// verfy if is exeist

                               $exist=$em->getRepository('UfmcpBundle:Commande')->findByMonthAndYear($moisCommande->format('m'),$annee,$com['agence']->getId());
                               if($exist){
                                   //  dump($exist);die;
                                   $exist->setPrescripteur($prescripteur);
                                   $exist->setDate(new DateTime($newformat));
                                   if( isset($com['volume'])){
                                       $exist->setVolume1($com['volume']);
                                   }
                               }
                               else{
                                   if(isset($com['volume'])){
                                       $commande=new Commande();
                                       $commande->setPrescripteur($prescripteur);
                                       $commande->setDate(new DateTime($newformat));
                                       $commande->setVolume1($com['volume']);

                                       $em->persist($commande);
                                   }

                               }
//                               $em->flush();

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
                $success=true;
                $info=['annee'=>$annee,'mois'=>$moisCommande->format('m'),'commande'=>'Les commandes pour ' . count($comAgenceVolume) . ' sites'];
            }
        }
        return $this->render(':commande:importer.html.twig', [
            'base_dir'      	=> realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
            'titre'         	=> "Importer une commande",
            'success'=>$success,
            'info'=>$info
        ]);
    }