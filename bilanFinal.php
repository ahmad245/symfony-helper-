<?php

namespace UfmcpBundle\PDF;

use Symfony\Component\DependencyInjection\Container;
use UfmcpBundle\Controller\ReunionController;
use UfmcpBundle\Entity\Entretien;
use UfmcpBundle\Entity\Motif;
use UfmcpBundle\Entity\Parcours;
use UfmcpBundle\Entity\Stagiaire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;
use Doctrine\ORM\EntityManager;
use UfmcpBundle\Service\Utils;
use UfmcpBundle\Entity\Reunion;
use setasign\Fpdi;

class Bilan
{
    /**
     * @var Container
     */
    private $container;
    
    private $em;
    private $utils;
    
	public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
        $this->utils = $container->get('ufmcp_utils');
    }
    
    /**
     * @param Stagiaire $stagiaire
     * @return Fpdi\TcpdfFpdi
     * @throws Fpdi\PdfParser\CrossReference\CrossReferenceException
     * @throws Fpdi\PdfParser\Filter\FilterException
     * @throws Fpdi\PdfParser\PdfParserException
     * @throws Fpdi\PdfParser\Type\PdfTypeException
     * @throws Fpdi\PdfReader\PdfReaderException
     */
    public function generate(Stagiaire $stagiaire)
    {
        $em = $this->em;
        $utils = $this->utils;
        
        $drawBorders = false;
    
        $stagiaire = $stagiaire;
        $orgSite = $stagiaire->getOrganismeSiteDestinataire();
        $organisme = $orgSite->getOrganisme();
        $territoire = $em->getRepository('UfmcpBundle:Territoire')->findFromSession();
        $prescripteur = $stagiaire->getPrescripteur();
        $referent = $stagiaire->getReferent();
        $site = $stagiaire->getLieuFormation();
    
        $pdf = new Fpdi\TcpdfFpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setFooterMargin(0);
        $pdf->SetAutoPageBreak(false);
        
        $projDir = $this->container->get('kernel')->getProjectDir();
    
        $pdf->setSourceFile($projDir . '/src/UfmcpBundle/PDF/Templates/bilan.pdf');
        $pdf->SetFontSize(9);


        $this->page1($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page2($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page3($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page4($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page5($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page6($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        $this->page7($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site);
        return $pdf;
    }

    private  function page1($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){
        $pdf->AddPage();

        $tplId = $pdf->importPage(1);

        $pdf->useTemplate($tplId, 0, 0);

        // Num marché
        $pdf->SetXY(32.5, 28.8);
        $pdf->Cell(90, 0, $territoire->getNumeroAthena(), $drawBorders);

        // Num lot
        $pdf->SetXY(146.9, 28.8);
        $pdf->Cell(40, 0, $territoire->getLot(), $drawBorders);

        $dateDebut = $stagiaire->getDateDebutFormationPrevue();
        $dateFin = $stagiaire->getDateFinFormation() ?? $stagiaire->getDateFinFormationPrevue();

        // Date début
        $pdf->SetXY(49.8, 36.6);
        $pdf->Cell(7.5, 0, $dateDebut->format('d'), $drawBorders, 0, '', false, '', 4);
        $pdf->SetX(62.2);
        $pdf->Cell(7.5, 0, $dateDebut->format('m'), $drawBorders, 0, '', false, '', 4);
        $pdf->SetX(74.8);
        $pdf->Cell(7.5, 0, $dateDebut->format('y'), $drawBorders, 0, '', false, '', 4);

        // Date fin
        $pdf->SetX(90.5);
        $pdf->Cell(7.5, 0, $dateFin->format('d'), $drawBorders, 0, '', false, '', 4);
        $pdf->SetX(103);
        $pdf->Cell(7.5, 0, $dateFin->format('m'), $drawBorders, 0, '', false, '', 4);
        $pdf->SetX(115.5);
        $pdf->Cell(7.5, 0, $dateFin->format('y'), $drawBorders, 0, '', false, '', 4);

        // Num commande
        $pdf->SetXY(156.5, 36.2);
        $pdf->Cell(40, 0, $stagiaire->getLettreCommande(), $drawBorders);

        // Bénéficiaire
        // Nom, prénom

        $pdf->SetXY(38.8, 65.8);
        $pdf->Cell(60, 0, $stagiaire->getNom().' '.$stagiaire->getPrenom(), $drawBorders);

        // Identifiant PE

        $pdf->SetXY(38.8, 73.7);
        $pdf->Cell(60, 0, $stagiaire->getIdPe(), $drawBorders);

        // Tel
        if (!empty($stagiaire->getTelephone())) {
            $pdf->SetXY(23.5, 87.2);
            $pdf->Cell(45.4, 0, str_replace(' ', '', trim($stagiaire->getTelephone())), $drawBorders, 0, '', false, '', 4);
        }


        // Mail
        $splitMail = explode('@', $stagiaire->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(22.5, 95.1);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(72.3,95.1);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }

        // OF
        // Nom
        $pdf->SetXY(117.8,  65.8);
        $pdf->Cell(70, 0, $organisme->getNom(true), $drawBorders);


        if(isset($site)){
            // Lieu
            $pdf->SetXY(107, 78.2);
            $pdf->Cell(90, 0, $site->getAdresse().' '.$site->getAdresseCplt(), $drawBorders);

            // Code postal
            $pdf->SetXY(107, 82.8);
            $pdf->Cell(22, 0, $site->getCp(), $drawBorders, 0, '', false, '', 4);

            // Ville
            $pdf->SetXY(137, 82.8);
            $pdf->Cell(60, 0, $site->getVille(), $drawBorders);
        }


        // Tel
        $lOrgSiteLieu = $em->getRepository('UfmcpBundle:LOrganismeLieu')->findOneBy([
            'organismeSite' => $orgSite,
            'lieuFormation' => $site
        ]);

        if(!empty($lOrgSiteLieu)) {
            $pdf->SetXY(117.6, 90.8);
            $pdf->Cell(45.6, 0, str_replace(' ', '', trim($lOrgSiteLieu->getTel1())), $drawBorders, 0, '', false, '', 4);
        }

        // Mail
        $splitMail = explode('@', $orgSite->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(116.2, 98);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(166, 98);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }

        // Prescripteur
        // Nom prénom
        $pdf->SetXY(39.5, 116.6);
        $pdf->Cell(70, 0, $prescripteur->getNom().' '.$prescripteur->getPrenom(), $drawBorders);

        // Agence
        $pdf->SetXY(41.5, 124.4);
        $pdf->Cell(70, 0, $prescripteur->getLibelleStructure(), $drawBorders);

        // Référent
        // Nom prénom
        $pdf->SetXY(132, 116.8);
        $pdf->Cell(70, 0, $referent->getNom().' '.$referent->getPrenom(), $drawBorders);

        // Tel
        if(!empty($referent->getTelephone())) {
            $pdf->SetXY(117.6, 124.5);
            $pdf->Cell(45.6, 0, str_replace(' ', '', trim($referent->getTelephone())), $drawBorders, 0, '', false, '', 4);
        }

        $splitMail = explode('@', $referent->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(116.2, 132.1);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(166,  132.1);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Motif
        if($stagiaire->getStatut() == Stagiaire::STATUT_FIN_PARCOURS) {
//            $pdf->SetXY(10.6, 145.8);
//            $pdf->Cell(10, 10, 'X', $drawBorders, 0);
        }
        else {
            $pdf->SetXY(10.6, 160.7);
            $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            $customX=140;
            $pdf->SetXY($customX, 164);
            $pdf->Cell(7, 0, $dateFin->format('d'), $drawBorders, 0, '', false, '', 4);
            $pdf->SetX($customX+10.5);
            $pdf->Cell(7, 0, $dateFin->format('m'), $drawBorders, 0, '', false, '', 4);
            $pdf->SetX($customX+21.5);
            $pdf->Cell(7, 0, $dateFin->format('y'), $drawBorders, 0, '', false, '', 4);

            $drawCheck = false;

            if(!empty($stagiaire->getCodeMotifSortie())) {
                $codeY=181.5;
                switch ($stagiaire->getCodeMotifSortie()->getCode()) {

                    //   switch (10) {

                    case Motif::MOTIF_CREATION_REPRISE_ENTREPRISE:
                        $pdf->SetXY(26.4, $codeY);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_ENTREE_FORMATION:
                        $pdf->SetXY(26.4, $codeY+6);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_ARRET_MALADIE_CONGE_MATERNITE:
                        $pdf->SetXY(26.4, $codeY+12.2);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_RAISON_MATERIELLE:
                        $pdf->SetXY(26.4,$codeY+18.4);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_DEMENAGEMENT:
                        $pdf->SetXY(26.4, $codeY+24.6);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_AUTRE:
                        $pdf->SetXY(72, $codeY+31);
                        $pdf->Cell(100, 0, $stagiaire->getMotifSortie(), $drawBorders, 0);
                        $pdf->SetXY(26.4, $codeY+31);
                        $drawCheck = true;
                        break;
                }
            }

            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            }
        }
    }
    private  function page2($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){
        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // Page 2 - Entretien initial

        $pdf->AddPage();

        $tplId = $pdf->importPage(2);

        $pdf->useTemplate($tplId, 0, 0);

        // $entInit = $stagiaire->getEntretienInitial();
        $entInit = $stagiaire->getEntretienDiagnostic();
        // dump($entInit);die;
        if(!empty($entInit)) {
            $pdf->SetXY(73, 25);
            $pdf->Cell(7, 0, $entInit->getDateHeure()->format('d'), $drawBorders, 0, '', false, '');
            $pdf->SetX(84);
            $pdf->Cell(7, 0, $entInit->getDateHeure()->format('m'), $drawBorders, 0, '', false, '');
            $pdf->SetX(100);
            $pdf->Cell(7, 0, $entInit->getDateHeure()->format('Y'), $drawBorders, 0, '', false, '');

            $pdf->SetXY(30.5, 29.9);
            $pdf->Cell(70, 0, $entInit->getFormateur()->getNom().' '.$entInit->getFormateur()->getPrenom(), $drawBorders);

            $drawCheck = false;
            switch ($entInit->getMoyen()){
                //    switch (5){
                case Entretien::MOYEN_PRESENTIEL :
                    $pdf->SetXY(69.5, 33.2);
                    $drawCheck = true;
                    break;
                case Entretien::MOYEN_VISIO :
                    $pdf->SetXY(95, 33.2);
                    $drawCheck = true;
                    break;
            }

            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            }

        }


        /////////////////////////////////////////////////////////////////////////////////////////////////////////

//        $preinscription = $em->getRepository('UfmcpBundle:Preinscription')->findOneByStagiaire($stagiaire);
//        if(!empty($preinscription)) {
//        if(stripos($preinscription->getObjectifPrestation(), 'elaborer') !== false) {
//            $pdf->SetXY(7.4, 55.7);
//            $pdf->Cell(10, 10, 'X', $drawBorders, 0);
//        } else if(stripos($preinscription->getObjectifPrestation(), 'confirmer') !== false) {
//            $pdf->SetXY(7.4, 63.2);
//            $pdf->Cell(10, 10, 'X', $drawBorders, 0);
//        }
//    }
//
//        $pdf->SetXY(46, 76);
//        if($stagiaire->getTypeProjetPro() > 0) {
//            $pdf->MultiCell(149, 12, $stagiaire->getProjetPro(), $drawBorders, 'L');
//        } else {
//            $pdf->MultiCell(149, 12, "Aucune", $drawBorders, 'L');
//        }
//
//        if(!empty($entInit)) {
//            $pdf->SetXY(13, 100);
//            $pdf->MultiCell(177, 40, $entInit->getCommentaire(), $drawBorders, 'L');
//        }

        $bilan = $stagiaire->getBilan();

        // dump($bilan);die;
        //"int-premier-mois" => "test"
        //  "int-besoins-identifies" => "test 2"
        // dump($this->_getSyntess($stagiaire,'besoins_reperes'));die;
        //   dump(  $stagiaire->getSynthese(),$stagiaire->getDiagnosticBlockData('besoins_reperes')['fields']);die;


        ///////////////////////////////////////////////Atouts identifiés : //////////////////////////////////////////////////////////

        $besoinsReperes=$this->_getSyntess($stagiaire,'besoins_reperes');
        //  dump($besoinsReperes);die;
        if(strlen($besoinsReperes) >0){
            $textY=60;
            $pdf->SetXY(13, $textY);

            $this->_insertMutiLineText($besoinsReperes,$pdf,$drawBorders);
        }
        ///////////////////////////////////////////////////////Points à renforcer////////////////////////////////////////////////////////////////////////////////////

        $capacites=$this->_getSyntess($stagiaire,'capacites');
        //  dump($capacites);die;
        if(strlen($capacites) >0){
            $textY=122.3;
            $pdf->SetXY(13, $textY);

            $this->_insertMutiLineText($capacites,$pdf,$drawBorders);
        }
    }
    private  function page3($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){
        /////////////////////////////////////////////Atelier 6 - Bilan des actions réalisées et choix des ateliers optionnels///////////////////////////////////////////////////////////////////////////////
        // Page 3 - Atelier 6 - Bilan des actions réalisées et choix des ateliers optionnels

        $pdf->AddPage();

        $tplId = $pdf->importPage(3);

        $pdf->useTemplate($tplId, 0, 0);

        $bilan = $stagiaire->getBilan();

        if(isset($bilan['int-premier-mois'])){
            $bilanPremierMois=$bilan['int-premier-mois'];
            $textY=52.8;
            $pdf->SetXY(13, $textY);

            $this->_insertMutiLineText($bilanPremierMois,$pdf,$drawBorders);

        }

        if(isset($bilan['int-besoins-identifies'])){
            $bilanBesoinsIdentifies=$bilan['int-besoins-identifies'];
            $textY=108;
            $pdf->SetXY(13, $textY);

            $this->_insertMutiLineText($bilanBesoinsIdentifies,$pdf,$drawBorders);
        }



    }
    private  function page4($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){
        /////////////////////////////////////////////////////// Page 4/////////////////////////////////////////////////////////////////
        $iPlan = 1;
        $offsetPlan = 0;
        $pdf->SetXY(7.4, 195.3);


        // Page 4
        $pdf->AddPage();

        $tplId = $pdf->importPage(4);

        $pdf->useTemplate($tplId, 0, 0);


//
//
//        //   dump($stagiaire->getReunions()->toArray());die;
//        foreach ($stagiaire->getEntretiens() as $val){
//            dump($val);
//        }
//        foreach ($stagiaire->getReunions() as $val){
//            //  dump($val->getReunion()->getReference());
//        }
//
//        for ($i=0 ;$i<count($stagiaire->getReunions()) ;$i++){
//            dump($stagiaire->getReunions()[$i]->getReunion());
//        }
        $this->fillReunions($stagiaire,$pdf,$drawBorders);
        $this->fillEntretiens($stagiaire,$pdf,$drawBorders);
//
        //       die;
    }
    private  function page5($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){

        // Page 5 - entretien mi-parcours
        $pdf->SetFontSize(9);



        $pdf->AddPage();

        $tplId = $pdf->importPage(5);

        $pdf->useTemplate($tplId, 0, 0);
        $this->fillReunions($stagiaire,$pdf,$drawBorders,9,20);
        $this->fillEntretiens($stagiaire,$pdf,$drawBorders,9,20);

    }
    private  function page6($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){
        // Page 6 - entretien final/////////////////////////////////////////////////////////////////////////////////

        $pdf->SetFontSize(9);
        $pdf->AddPage();

        $tplId = $pdf->importPage(6);

        $pdf->useTemplate($tplId, 0, 0);
        $bilan = $stagiaire->getBilan();

        $entBilan = $stagiaire->getEntretienBilan();
        // dump($entBilan);die;
        if(!empty($entBilan) ) { //&& $entBilan->getDuree() > 0
            $pdf->SetXY(48.5, 29);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('d'), $drawBorders, 0, '', false, '');
            $pdf->SetX(60.8);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('m'), $drawBorders, 0, '', false, '');
            $pdf->SetX(72.3);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('Y'), $drawBorders, 0, '', false, '');

            $pdf->SetXY(30.5, 38.2);
            $pdf->Cell(70, 0, $entBilan->getFormateur()->getNom().' '.$entBilan->getFormateur()->getPrenom(), $drawBorders);
            $drawCheck = false;
            switch ($entBilan->getMoyen()){
                //    switch (5){
                case Entretien::MOYEN_PRESENTIEL :
                    $pdf->SetXY(69.5, 41.8);
                    $drawCheck = true;
                    break;
                case Entretien::MOYEN_VISIO :
                    $pdf->SetXY(95, 41.8);
                    $drawCheck = true;
                    break;
            }

            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            }
//            $pdf->SetXY(13, 70);
//            $pdf->MultiCell(177, 40, $entBilan->getCommentaire(), $drawBorders, 'L');
        }



        ////////////////////////////////////////////////////////////////////Bilan du parcours :

        if(isset($bilan['parcours'])){
            $bilanParcours=$bilan['parcours'];
            $textY=76.9;
            $pdf->SetXY(10, $textY);

            $this->_insertMutiLineText($bilanParcours,$pdf,$drawBorders,10);

        }

        ////////////////////////////////////////////////////////////////Eléments de valorisation du bénéficiaire :
        if(isset($bilan["elements-valorisation"]) && count($bilan["elements-valorisation"]) > 0){
            if(isset($bilan["elements-valorisation"][1])) {
                $drawCheck = false;
                switch ($bilan["elements-valorisation"][1]['appreciation']) {
                    //          switch ('A améliorer'){
                    case 'Excellente' :
                        $pdf->SetXY(88.5, 205);
                        $drawCheck = true;
                        break;
                    case 'Bonne':
                        $pdf->SetXY(88.5, 212.8);
                        $drawCheck = true;
                        break;
                    case 'A améliorer':
                        $pdf->SetXY(88.5, 221);
                        $drawCheck = true;
                        break;
                }
                if ($drawCheck) {
                    $pdf->SetFontSize(6);
                    $pdf->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,255,0)));
                    $pdf->Cell(3, 2, 'X', 1, 0, 'C');
                }
            }
            if(isset($bilan["elements-valorisation"][2])) {
                $drawCheck = false;
                switch ($bilan["elements-valorisation"][2]['appreciation']) {
                    //             switch ('A améliorer'){
                    case 'Excellente' :
                        $pdf->SetXY(88.5, 248.5);
                        $drawCheck = true;
                        break;
                    case 'Bonne':
                        $pdf->SetXY(88.5, 256);
                        $drawCheck = true;
                        break;
                    case 'A améliorer':
                        $pdf->SetXY(88.5, 264);
                        $drawCheck = true;
                        break;
                }
                if ($drawCheck) {
                    $pdf->SetFontSize(6);
                    $pdf->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,255,0)));
                    $pdf->Cell(3, 2, 'X', 1, 0, 'C');
                }
            }
        }

    }
    private  function page7($pdf,$drawBorders,$em,$stagiaire,$orgSite,$organisme,$territoire,$prescripteur,$referent,$site){


        $pdf->AddPage();

        $tplId = $pdf->importPage(7);

        $pdf->useTemplate($tplId, 0, 0);
        $bilan = $stagiaire->getBilan();

        if(isset($bilan["elements-valorisation"]) && count($bilan["elements-valorisation"]) > 0){
            if(isset($bilan["elements-valorisation"][3])) {
                $drawCheck = false;
                switch ($bilan["elements-valorisation"][3]['appreciation']) {
                    //                switch ('Excellente'){
                    case 'Excellente' :
                        $pdf->SetXY(88.5, 26.9);
                        $drawCheck = true;
                        break;
                    case 'Bonne':
                        $pdf->SetXY(88.5, 34.7);
                        $drawCheck = true;
                        break;
                    case 'A améliorer':
                        $pdf->SetXY(88.5, 42.7);
                        $drawCheck = true;
                        break;
                }
                if ($drawCheck) {
                    $pdf->SetFontSize(6);
                    $pdf->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,255,0)));
                    $pdf->Cell(3, 2, 'X', 1, 0, 'C');
                }
            }
            if(isset($bilan["elements-valorisation"][4])) {
                $drawCheck = false;
                switch ($bilan["elements-valorisation"][4]['appreciation']) {
                    //                   switch ('Excellente'){
                    case 'Excellente' :
                        $pdf->SetXY(88.5, 66.5);
                        $drawCheck = true;
                        break;
                    case 'Bonne':
                        $pdf->SetXY(88.5, 74.5);
                        $drawCheck = true;
                        break;
                    case 'A améliorer':
                        $pdf->SetXY(88.5, 82.5);
                        $drawCheck = true;
                        break;
                }
                if ($drawCheck) {
                    $pdf->SetFontSize(6);
                    $pdf->SetLineStyle(array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0,255,0)));
                    $pdf->Cell(3, 2, 'X', 1, 0, 'C');
                }
            }
        }
        //////////////////////////////////////////////////////Perspectives d’emploi à 6 mois : //////////////////

        $pdf->SetFontSize(9);

        if(isset($bilan['perspectives'])){
            $bilanPerspectives=$bilan['perspectives'];
            $textY=101.5;
            $pdf->SetXY(10, $textY);

            $this->_insertMutiLineText($bilanPerspectives,$pdf,$drawBorders,10);

        }

        //////////////////////////////////////////Plan d’actions après la prestation
        // dump($bilan['plan-action-final']);die;
        if(!empty($bilan['plan-action-final'])) {
            $iPlan = 1;
            $pdf->SetXY(13, 190.1);
            $cellHeightRatio = $pdf->getCellHeightRatio();
            if (isset($bilan['plan-action-final'])) {
                $pdf->setCellHeightRatio(0.8);

                foreach ($bilan['plan-action-final'] as $oPlan => $plan) {
                    if ( empty($plan['actions'])) {
                        continue;
                    }

                    $pdf->SetFontSize(8);
                    $pdf->SetX(7);

                    $pdf->MultiCell(61, 10.2, $plan['actions'], $drawBorders, 'L', false, 0);
                    $pdf->SetX(66.8);
                    $pdf->MultiCell(30, 10.3, $plan['echeance'], $drawBorders, 'L', false, 0);
                    $pdf->SetX(104);
                    $pdf->MultiCell(61, 10.5, $plan['commentaire'], $drawBorders, 'L', false, 0);
                    $pdf->Ln();

                    $pdf->SetFontSize(9);

                    $iPlan++;

                    if ($iPlan > 6) {
                        break;
                    }
                }
            }
            $pdf->setCellHeightRatio($cellHeightRatio);
        }
    }

    private function  _insertMutiLineText($text,$pdf,$drawBorders,$x=13,$w=175){
        $text=str_replace(',',', ',$text);

        if(strlen($text) >= 550){
            $text=substr($text,0,550).' ...';
        }

        $cellHeightRatio = $pdf->getCellHeightRatio();

        $pdf->setCellHeightRatio(2.3);
        $pdf->setY($pdf->getY()+1.5);
        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        $pdf->MultiCell($w, 10.2, $text, 0, 'L', false, 1,$x,'',true,1,false,false,0,10.2);
        $pdf->setCellHeightRatio($cellHeightRatio);

    }

    private  function  _getSyntess($stagiaire,$block){
        $rr=$stagiaire->getSynthese();
        $resultArr=[];

        if(isset($rr)){
            $arr1=  $stagiaire->getSynthese()[$block];
            $arr2= $stagiaire->getDiagnosticBlockData($block)['fields'];
            foreach ($arr2 as $key=>$value){
                if($arr1[$key]===true){
                    $resultArr[$key]=$value;
                }
            }
        }



        $result=implode(',',$resultArr);

        // dump($resultArr,$result);die;
        return $result;

    }

    private  function fillReunions($stagiaire,$pdf,$drawBorders,$index=0,$count=9){
        //        for ($i=0 ;$i<count($stagiaire->getReunions()) ;$i++){
//            dump($stagiaire->getReunions()[$i]->getReunion());
//        }

        $typeY=34.9;
        $typeYfix=24.8;
        $typeX=37.5;
        $aIY=2.6; // Atelier incontournable
        $eEY=23.1; // Evènement entreprise
        $aOY=10.5;// Atelier optionnel
        $reunions=$stagiaire->getReunions();

        if( !isset($reunions[$index])){
            return;
        }

        for ($i=$index ;$i<$count ;$i++){
            //  foreach($stagiaire->getReunions() as $lrs) {
            //    dump($lrs->getReunion());
            if(is_null($reunions[$i])) {
                break;
            }
            $reunion = $reunions[$i]->getReunion();
            $dateReunion = $reunion->getDateDebut()->format('Y-m-d');
            $heureReunion = $reunion->getHeureDebut()->format('H\hi');


            $pdf->SetFontSize(6);
            $drawCheck = false;

            $pdf->SetXY($typeX-25, $typeY+($typeYfix / 2));
            $pdf->Cell(10, 10,$dateReunion.' '.$heureReunion, $drawBorders, 0);

            if($reunion->getType() == Reunion::TYPE_ATELIER ) { // themes ateliers optionnels > 100 (101, 102, 103 ... ) && $reunion->getTheme() < 100

                switch (true){
                    //    switch (5){
                    //  case   $reunion->getTheme() <100 :
                    case   $reunion->getTheme() <5 :
                        $pdf->SetXY($typeX, $typeY+$aIY);
                        $drawCheck = true;
                        break;
                    //  case $reunion->getTheme() >100 :
                    case $reunion->getTheme() >= 5 :
                        $pdf->SetXY($typeX, $typeY+$aOY);
                        $drawCheck = true;
                        break;
                }

            }
            elseif ($reunion->getType() == Reunion::TYPE_ADHESION ){
                $pdf->SetXY($typeX, $typeY+$eEY);
                $drawCheck = true;
            }
            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                switch ($reunion->getMoyen()){
                    case   Reunion::MOYEN_PRESENTIEL :
                        $pdf->SetXY($typeX+53.9, $typeY+$aIY);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;

                    case Reunion::MOYEN_VISIO :
                        $pdf->SetXY($typeX+53.9, $typeY+$aOY+5);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;
                }
                $typeY+=$typeYfix;
            }

        }
    }
    private  function fillEntretiens($stagiaire,$pdf,$drawBorders,$index=0,$count=9){
        $typeY=34.9;
        $typeYfix=24.8;
        $typeX=37.5;
        $aIY=2.6; // Atelier incontournable
        $eEY=23.1; // Evènement entreprise
        $aOY=10.5;// Atelier optionnel
        $cm1=6.6; // Contact au cours du 1er mois
        $cm2=15; //  Contact hebdomadaireau cours du 2e mois

        $entertiens=$stagiaire->getEntretiens();

        //   foreach ($stagiaire->getEntretiens() as $val){
        if( !isset($entertiens[$index])){
            return;
        }

        for ($i=$index ;$i<$count ;$i++){
            //  foreach($stagiaire->getReunions() as $lrs) {
            //    dump($lrs->getReunion());
            if(is_null($entertiens[$i])) {
                break;
            }


            $drawCheck = false;
            $diff = abs(strtotime($entertiens[$i]->getDateHeure()->format('Y/m/d')) - strtotime($stagiaire->getDateCreation()->format('Y/m/d')));

            $years = floor($diff / (365*60*60*24));
            $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
            $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
            //  dump($val->getDateHeure(),$stagiaire->getDateCreation(),'annee:'. $years,'mois :'.$months,'jour :'.$days);

            if($months == 1){
                $pdf->SetXY($typeX, $typeY+$cm1);
                $drawCheck = true;

            }
            elseif ($months == 2){
                $pdf->SetXY($typeX, $typeY+$cm2);
                $drawCheck = true;
            }
            else{
                $drawCheck=false;
            }

            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                switch ($entertiens[$i]->getMoyen()){
                    //  switch (4){
                    case   Entretien::MOYEN_PRESENTIEL :
                        $pdf->SetXY($typeX+53.9, $typeY+$aIY);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;
                    case Entretien::MOYEN_EMAIL :
                        $pdf->SetXY($typeX+53.9, $typeY+$cm1);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;

                    case Entretien::MOYEN_TELEPHONE :
                        $pdf->SetXY($typeX+53.9, $typeY+$aOY+0.2);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;

                    case Entretien::MOYEN_VISIO :
                        $pdf->SetXY($typeX+53.9, $typeY+$cm2);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;


                    case Entretien::MOYEN_CHAT :
                        $pdf->SetXY($typeX+53.9, $typeY+$cm2+3.9);
                        $pdf->Cell(10, 10, 'X', $drawBorders, 0);
                        break;
                }
                $typeY+=$typeYfix;
            }
        }
        //  die;
    }




}