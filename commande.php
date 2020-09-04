<?php

namespace UfmcpBundle\Controller;



use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UfmcpBundle\Entity\Commande;
use UfmcpBundle\Entity\Planification;
use UfmcpBundle\Entity\Prescripteur;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use UfmcpBundle\Types\DateTime;

use Symfony\Component\HttpFoundation\Cookie;

/*
 * cree par ahmad 11/03/2020 13:56
 *
//* sous-menu COMMANDES dans le menu PARAMÉTRAGE em Planification dans ce sous-menu COMMANDES,
//* permet d'accéder à la nouvelle route commande/planification par isOrganisme => isPorteur
 * */
class CommandeController extends  Controller{
//    /**
//     * @Route("/commande/planification/export/{id}/{annee}/{mois}/{commande}", name="planification_export",requirements={"id"="\d+"} ,options={"expose":true})
//     * @param Request $request
//
//     */
//    public function exportPlanification(Request $request,$id=null,$annee=null,$mois=null,$commande=1)
//    {
//        ini_set('max_execution_time', 500);
//
//        set_time_limit(300);
//        if (is_null($annee)){
//            $annee=date("Y");
//        }
//        if (is_null($mois)){
//            $mois=date("m");
//        }
//
//        $dateDebut = new \DateTime($annee."-".sprintf("%02d", $mois)."-01");
//        $nextMonth = clone $dateDebut;
//        $nextMonth->modify("first day of next month");
//        $dateFin = clone($nextMonth);
//        $dateFin->modify("-1 day");
//        $semaines = $this->get('ufmcp_utils')->splitWeeks($dateDebut, $dateFin, true);
//
//        $prevMonth = clone $dateDebut;
//        $prevMonth->modify("first day of previous month");
//
//        if($nextMonth->format('Y') > date('Y')) {
//            $nextMonth = clone $dateDebut;
//        }
//
//
//        ///////////////////////////////////
//        $em = $this->getDoctrine()->getManager();
//        $allAgenc=[];
//        $allJourOut=[];
//        $AllterritoireArray=[];
//        $allNameOfAgance=[];
//
//        //$AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteurs();
//       // $AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteursByCommande($commande);
//       $AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteurs();
//
//        foreach ($AllPreinscripteurs as $agance){
//            $datePrescripteur=[];
//
//                //
//                $agenceDate= $agance->getPlanifications();
//
//                foreach ($agenceDate as $dateP)
//                {
//                   // array_push($datePrescripteur,$dateP->getJourHeure()->format('d/m/Y H:i'));
//                    $datePrescripteur[$dateP->getJourHeure()->format('d/m/Y H:i')]=$dateP->getNombre();
//                 //
//
//                }
//             $organiseData=  $this->organiseData($semaines,$mois,$datePrescripteur);
//            $wArray= $organiseData[0];
//            $jourOut=$organiseData[1];
//            $territoireArray=$organiseData[2];
//
//
//            array_push($allAgenc,$wArray);
//            array_push($allJourOut,$jourOut);
//            array_push($AllterritoireArray,$territoireArray);
//            array_push($allNameOfAgance,$agance->getLibelleStructure());
//          //  var_dump($datePrescripteur);
//    }
//
//   // dump($allAgenc);die;
//       // var_dump($AllPreinscripteurs);
//        //die;
//
//  // var_dump($allAgenc);die;
//   // var_dump($allJourOut);die;
//
//
//       // var_dump(count($em->getRepository('UfmcpBundle:Prescripteur')->findBy(['email'=>null,'nom'=>" "])));die;
//
//
//
//        /////////////////////////////////////////
//
//
//        ////////////////////////////////////////////////////////////////////////////////////
////        $territoire =  $em->getRepository('UfmcpBundle:Territoire')->findFromSession();
////
////        $territoireArray=[
////          ["Raison Sociale du Titulaire du Marché (mandataire)",$territoire->getNom()] ,
////          ["N° du Marché",$territoire->getNumeroAthena()] ,
////          ["Adresse du Titulaire du Marché",$territoire->getPorteur()->getAdresse().' '.$territoire->getPorteur()->getAdresseCplt()] ,
////          ["Code Postal et Ville",$territoire->getPorteur()->getCp().' '.$territoire->getPorteur()->getVille()] ,
////          ["Téléphone",$territoire->getPorteur()->getTel()] ,
////          ["Adresse électronique Titulaire du Marché",$territoire->getPorteur()->getRespMarcheEmail()] ,
////          ["Nom et téléphone de la personne en charge de la planification",$territoire->getPorteur()->getRespMarcheNom()] ,
////          ["Site demandeur",$territoire->getPorteur()->getSiteInternet()] ,
////          ["PRESTATAIRE Intervenant",$territoire->getPorteur()->getSiteInternet()] ,
////          ["Adresse du lieu de réalisation",$territoire->getPorteur()->getAdresseCplt()] ,
////
////        ];
//
//
//        $headers = [
//            ["text" => "PRESTATION", "colspan" => 5, "rowspan" => 1, "row" => 0],
//            ["text" => "ACTIV'PROJET-AP2", "colspan" => 7, "rowspan" => 1, "row" => 0]
//
//        ];
//        $headers2 = [
//            ["text" => "DATE", "colspan" => 2, "rowspan" => 1, "row" => 0],
//            ["text" => "PLAGES HORAIRES", "colspan" => 41, "rowspan" => 1, "row" => 0],
//            ["text" => "NB PLAGES", "colspan" => 1, "rowspan" => 1, "row" => 0],
//        ];
//
//
//
//
//        $res = $this->get('ufmcp.excel_generator')->generateFiles($AllterritoireArray,$allAgenc,$allJourOut,$allNameOfAgance,$mois,$annee);
//        $TOKEN = "downloadToken";
//       // $TOKEN, $_GET[ $TOKEN ], false
//   //    $res->headers->setCookie(new Cookie('downloadToken','downloadToken'))
//        return $res;
//        exit;
//        die();
//    }



    /**
     * @Route("/commande/planification/export/{id}/{annee}/{mois}/{commande}", name="planification_export",requirements={"id"="\d+"} ,options={"expose":true})
     * @param Request $request

     */
    public function exportPlanification(Request $request,$id=null,$annee=null,$mois=null,$commande=1)
    {
        ini_set('max_execution_time', 500);

        set_time_limit(300);
        if (is_null($annee)){
            $annee=date("Y");
        }
        if (is_null($mois)){
            $mois=date("m");
        }

        $dateDebut = new \DateTime($annee."-".sprintf("%02d", $mois)."-01");
        $nextMonth = clone $dateDebut;
        $nextMonth->modify("first day of next month");
        $dateFin = clone($nextMonth);
        $dateFin->modify("-1 day");
        $semaines = $this->get('ufmcp_utils')->splitWeeks($dateDebut, $dateFin, true);

        $prevMonth = clone $dateDebut;
        $prevMonth->modify("first day of previous month");

        if($nextMonth->format('Y') > date('Y')) {
            $nextMonth = clone $dateDebut;
        }

        $em = $this->getDoctrine()->getManager();
        $allAgenc=[];
        $allJourOut=[];
        $AllterritoireArray=[];
        $allNameOfAgance=[];

        //$AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteurs();
        // $AllPreinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteursByCommande($commande);
        $preinscripteurs=$em->getRepository('UfmcpBundle:Prescripteur')->find($id);
        $allCommandes=range(1,$commande);

            $datePrescripteur=[];
            //
            $agenceDate= $preinscripteurs->getPlanificationsByCommande($allCommandes);

            foreach ($agenceDate as $dateP)
            {
                $datePrescripteur[$dateP->getJourHeure()->format('d/m/Y H:i')]=$dateP->getNombre();
            }

            $organiseData=  $this->organiseData($semaines,$mois,$datePrescripteur,$agenceDate,$commande);
            $wArray= $organiseData[0];
            $jourOut=$organiseData[1];
            $territoireArray=$organiseData[2];

            array_push($allAgenc,$wArray);
            array_push($allJourOut,$jourOut);
            array_push($AllterritoireArray,$territoireArray);
            array_push($allNameOfAgance,$preinscripteurs->getLibelleStructure());
            //  var_dump($datePrescripteur);


        $res = $this->get('ufmcp.excel_generator')->generateFiles($AllterritoireArray,$allAgenc,$allJourOut,$allNameOfAgance,$mois,$annee);
        $TOKEN = "downloadToken";

        return $res;
        exit;
        die();
    }
    /**
     * @Route("/commande/planificationJour/{jour}/{h}/{agence}", name="commande_planification_add",options={"expose":true})
     * @param Request $request
     * @return Response
     */
    public function planificatioInsert($jour=null,$h=null,$agence=null){
        $em = $this->getDoctrine()->getManager();
        $agenceInf = $em->getRepository('UfmcpBundle:Prescripteur')-> findOneBy(['id'=>$agence]);

        $dateFromString=strtotime($jour);
        $date=date('Y-m-d',$dateFromString);
        $h=explode("h",$h);
        $h=implode(":",$h);

        $totalDate=$date.' '.$h;
        $test2=strtotime($totalDate);
        $test =date("Y/m/d H:i",$test2);
        $oo=new DateTime($test);

        //////////////////////////////////
        $planification=new Planification();


        $agenceInf->addPlanification($planification);
        $planification->setJourHeure($oo);

        $em->persist($planification);
        $em->persist($agenceInf);

        $em->flush();

            ////////////////////////
        return  $this->json([
            'code'=>200,
            'message'=>'has been added',
            'response'=>[$date,$h,$agence,$totalDate,$oo,$planification->getPrescripteur()->getId()]
        ],200);
    }



    public function dateFormat($jour,$h){
        $dateFromString=strtotime($jour);
        $date=date('Y-m-d',$dateFromString);
        $h=explode("h",$h);
        $h=implode(":",$h);

        $totalDate=$date.' '.$h;
        $test2=strtotime($totalDate);
        $test =date("Y/m/d H:i",$test2);
        $oo=new DateTime($test);
        return $oo;

    }


    /**
     * @Route("/commande/planificationJoursave", name="commande_planification_save",options={"expose":true},methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function planificatioSave(Request $req){
        $em = $this->getDoctrine()->getManager();
        $agenceInf = $em->getRepository('UfmcpBundle:Prescripteur')-> findOneBy(['id'=>(int)$req->request->get('agenda')]);
      //  $lastCommande = $em->getRepository('UfmcpBundle:Planification')-> getLastCommande();
        $lastCommande = $em->getRepository('UfmcpBundle:Planification')-> getLastCommandeByAgance((int)$req->request->get('agenda'));
        $commande=0;
        if($lastCommande==null){
            $commande=1;
        }else{
            $commande=$lastCommande['commande']+1;
        }



      //  dump($req->request->all());die;
        foreach ($req->request->all() as $key=>$value){
           if($key != "agenda" && $key != "annee" && $key != "mois" &&$key !="commande"){
               if($value !== ""){
                   $oo=$this->dateFormat(explode('/',$key)[0],explode('/',$key)[1]);

                   $planificationExist = $em->getRepository('UfmcpBundle:Planification')->findByPrescripteurJourHeure((int)$req->request->get('agenda'), $oo);

                  if($agenceInf->isExist($planificationExist)){

                      $agenceInf->addPlanification($planificationExist);
                  }
                  else{
                      $planification=new Planification();
                      $agenceInf->addPlanification($planification);
                      $planification->setJourHeure($oo);
                      $planification->setNombre((int)$value);

                      //  dump($lastCommande['commande']);die;
                      $planification->setCommande($commande);

                      $em->persist($planification);
                  }



               }
           }
        }
        $em->persist($agenceInf);
        $em->flush();
//        foreach (  $em->getRepository('UfmcpBundle:Planification')-> findAll() as $all){
//            $em->remove($all);
//        }
//        $em->flush();
       // $all = $em->getRepository('UfmcpBundle:Planification')-> findAll();
//        dump($all);
//        die;




        ////////////////////////
        return $this->redirectToRoute('commande_planification',[
            'agenda'=>$req->request->get('agenda'),
            'annee'=>$req->request->get('annee'),
            'mois'=>trim($req->request->get('mois'))

        ]);

    }


    /**
     * @Route("/commande/planificationJour/delete/{jour}/{h}/{agence}", name="commande_planification_delete",options={"expose":true})
     * @param Request $request
     * @return Response
     */
    public function planificatioRemove($jour=null,$h=null,$agence=null){

        $dateFromString=strtotime($jour);
        $date=date('Y-m-d',$dateFromString);
        $h=explode("h",$h);
        $h=implode(":",$h);

        $totalDate=$date.' '.$h;
        $test2=strtotime($totalDate);
        $test =date("Y/m/d H:i",$test2);
        $oo=new DateTime($test);

        $em = $this->getDoctrine()->getManager();
       $agenceInf = $em->getRepository('UfmcpBundle:Prescripteur')-> findOneBy(['id'=>$agence]);
       $planification = $em->getRepository('UfmcpBundle:Planification')-> findByPrescripteurJourHeure($agence,$oo);

        $agenceInf->removePlanification($planification);

        $em->remove($planification);
        $em->persist($agenceInf);
//
        $em->flush();


        return  $this->json([
            'code'=>200,
            'message'=>'has been removed',
            'response'=>[$jour,$h,$agence]],200);
    }






    /**
     * @Route("/commande/planification/{agenda}/{annee}/{mois}/{commande}", name="commande_planification" ,options={"expose":true})

     * @return Response
     *

     */
    public function planificationIndex(Request $req,$agenda=null,$annee=null ,$mois=null,$commande=null){
       // dump($agenda,$annee,$mois);die;

        $em = $this->getDoctrine()->getManager();
        $agences =  $em->getRepository('UfmcpBundle:Prescripteur')->getAllPreinscripteurs();
      //  $agences = $em->getRepository('UfmcpBundle:Prescripteur')-> getAgence();
 //      $all = $em->getRepository('UfmcpBundle:Planification')-> findAll();
//                foreach (  $em->getRepository('UfmcpBundle:Planification')-> findAll() as $all){
//            $em->remove($all);
//        }
//        $em->flush();
//       dump($all);
//die;

        if (assert($agenda)){
            $agenceInf = $em->getRepository('UfmcpBundle:Prescripteur')-> findOneBy(['id'=>$agenda]);
            $territoire =  $em->getRepository('UfmcpBundle:Territoire')->findFromSession();
        }

        if (is_null($annee)){
            $annee=date("Y");
        }
        if (is_null($mois)){
            $mois=date("m");
        }
        if (is_null($agenda)){
            $agenceInf=$agences[0];
        }




      // dump($territoire);die;
        $dateDebut = new \DateTime($annee."-".sprintf("%02d", $mois)."-01");
        $nextMonth = clone $dateDebut;
        $nextMonth->modify("first day of next month");
        $dateFin = clone($nextMonth);
        $dateFin->modify("-1 day");
        $semaines = $this->get('ufmcp_utils')->splitWeeks($dateDebut, $dateFin, true);

        $prevMonth = clone $dateDebut;
        $prevMonth->modify("first day of previous month");

        if($nextMonth->format('Y') > date('Y')) {
            $nextMonth = clone $dateDebut;
        }




        return $this->render('commande/commande2.html.twig',
            ['controller'=>'controller',
                'semaines'=>$semaines,
                'mois'=>$mois,
                'annee'=>$annee,
                 'agenda'=>$agenda,
                 'agences'=>$agences,
                'commande'=>$commande,
                'agenceInf'=>$agenceInf,'territoire'=>$territoire]
        );
    }


    /**
     * @Route("/commande/gere/{annee}/{mois}", name="commande_planification_gere" ,options={"expose":true})

     * @return Response
     *

     */
    public function gereCommandes(Request $req,$annee=null ,$mois=null){

        if (is_null($annee)){
            $annee=date("Y");
        }
        if (is_null($mois)){
            $mois=date("m");
        }
        $em = $this->getDoctrine()->getManager();
     // $planificationPrescripteur = $em->getRepository('UfmcpBundle:Planification')-> findAgencesByDate($annee,$mois);
        $planificationPrescripteur = $em->getRepository('UfmcpBundle:Planification')->findAgences();

      $result=$this->group_by("departement",$planificationPrescripteur);




        $commandes= $em->getRepository('UfmcpBundle:Commande')->findAllByMonthAndYear($mois,$annee);


        $commandeGroub=[];
        foreach ($commandes as $item){
            $commandeGroub[$item->getPrescripteur()->getId()]=$item;
        }


        $dateDebut = new \DateTime($annee."-".sprintf("%02d", $mois)."-01");
        $nextMonth = clone $dateDebut;
        $nextMonth->modify("first day of next month");
        $dateFin = clone($nextMonth);
        $dateFin->modify("-1 day");
        $semaines = $this->get('ufmcp_utils')->splitWeeks($dateDebut, $dateFin, true);

        $prevMonth = clone $dateDebut;
        $prevMonth->modify("first day of previous month");

        if($nextMonth->format('Y') > date('Y')) {
            $nextMonth = clone $dateDebut;
        }
        return $this->render('commande/gereCommande.html.twig',[
            'gere'=>'gere les command',
            'mois'=>$mois,
            'annee'=>$annee,
             'planificationPrescripteur'=>$result,
            'commandes'=>$commandeGroub
        ]);
    }

    /**
     * @Route("/commandegere/create", name="commande_planification_gere_create",options={"expose":true},methods={"POST"})
     *
     */
    public function createCommande(Request $req){
       // $data=json_decode($req->getContent(),true);
        $em = $this->getDoctrine()->getManager();
        $planificationPrescripteur = $em->getRepository('UfmcpBundle:Planification')->findAgences();
        $result=$this->group_by("departement",$planificationPrescripteur);
            $annee=date("Y");
            $mois=date("m");



        $commandeReqAnnee=$req->query->get('annee');
        $commandeReqMois=$req->query->get('mois');



        $dateObj   = DateTime::createFromFormat('!m', $commandeReqMois);



        $time = strtotime($commandeReqMois.'/01/'.$commandeReqAnnee);

        $newformat = date('Y-m-d',$time);






        $uniqeKey=[];

        //////// divid array each array contain 3 element
       $arrayData=array_chunk($req->request->all(),3,true);


       ////////////////////// get the ids of Prescripteur
        foreach ($req->request->all() as $key=>$value){
            $uniqeKey[substr($key,7)]=$value;

        }
        $keys=array_keys($uniqeKey);

        ///////////////// get final array (for each id there are array of volumes )
        $final=[];
        $count=0;
        foreach ($arrayData as $key=>$value){
            $final[ $keys[$count]]=array_values($value);
            $count++;
        }




        $prescripteurArray=[];

     //  dump($final);die;
        foreach ($final as $key=>$value){

            $prescripteur = $em->getRepository('UfmcpBundle:Prescripteur')->findOneBy(['id'=>$key]);

            /// verfy if is exeist
            ///
            $exist=$em->getRepository('UfmcpBundle:Commande')->findByMonthAndYear($commandeReqMois,$commandeReqAnnee,$key);

            if($exist){
                $exist->setPrescripteur($prescripteur);
                $exist->setDate(new DateTime($newformat));
                $exist->setVolume1((int)$value[0]);
                $exist->setVolume2((int)$value[1]);
                $exist->setVolume3((int)$value[2]);
//                $em->persist($exist);

            }
            else{
                $commande=new Commande();
                $commande->setPrescripteur($prescripteur);
                $commande->setDate(new DateTime($newformat));
                $commande->setVolume1((int)$value[0]);
                $commande->setVolume2((int)$value[1]);
                $commande->setVolume3((int)$value[2]);

                $em->persist($commande);
            }



        }
        $em->flush();

   //     $finalResult= $em->getRepository('UfmcpBundle:Commande')->findAll();
//        foreach ($finalResult as $item){
//            $em->remove($item);
//        }
//        $em->flush();
     //   dump($finalResult);die;

        $params=$req->query->all();
     //   dump($req->request->all(),$uniqeKey,$arrayData,$params,$final);die;

        return $this->redirectToRoute('commande_planification_gere',[
//            'gere'=>'gere les command',
            'mois'=>$commandeReqMois,
            'annee'=>$commandeReqAnnee,
//            'planificationPrescripteur'=>$result
        ]);

    }



    /**
     * @Route("/commandegere/delete", name="commande_planification_gere_delete",options={"expose":true})
     *
     */
    public function deleteCommande(Request $req){
        // $data=json_decode($req->getContent(),true);
        $em = $this->getDoctrine()->getManager();
        $planificationPrescripteur = $em->getRepository('UfmcpBundle:Planification')->findAgences();
        $result=$this->group_by("departement",$planificationPrescripteur);
        $annee=date("Y");
        $mois=date("m");



        $commandeReqAnnee=$req->query->get('annee');
        $commandeReqMois=$req->query->get('mois');



        $dateObj   = DateTime::createFromFormat('!m', $commandeReqMois);



        $time = strtotime($commandeReqMois.'/01/'.$commandeReqAnnee);

        $newformat = date('Y-m-d',$time);






        $commandes=$em->getRepository('UfmcpBundle:Commande')->findAllByMonthAndYear($commandeReqMois,$commandeReqAnnee);


        foreach ($commandes as $item){
            $em->remove($item);
        }
        $em->flush();


        return $this->redirectToRoute('commande_planification_gere',[
            'gere'=>'gere les command',
            'mois'=>$mois,
            'annee'=>$annee,
            'planificationPrescripteur'=>$result
        ]);

    }





    public  function group_by($key, $data) {
        $result = array();

        asort($data);
        foreach($data as $val) {
            if(array_key_exists($key, $val)){
                $result[$val[$key]][] = $val;
            }else{
                $result[""][] = $val;
            }
        }
        ksort($result);

        return $result;
    }

    public  function isSameCommande($arr,$value,$commande=2){
        $item = '';
        foreach($arr as $struct) {

            if ($value == $struct->getJourHeure()->format('d/m/Y H:i') && $commande == $struct->getCommande()) {
                $item = 'red';
                break;
            }
        }
     //   dump($item);die;
        return $item;

    }

    public function organiseData($semaines,$mois,$datePrescripteur,$agenceDate,$commande){




        $jourOut=[];
        $jArray=[];
        $wArray=[];
        $i=1;
        foreach ($semaines as $w){
            $wArray[]=['Semaine'.$w[1]->format('W'),"8h00", "8h15", "8h30", "8h45", "9h00",
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
                    $j->format('d/m/Y'),
                    array_key_exists($j->format('d/m/Y').' '.'08:00',$datePrescripteur) && ($j->format('m')==$mois) ? $datePrescripteur[$j->format('d/m/Y').' '.'08:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'08:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'08:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'08:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'08:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'08:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'08:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'09:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'09:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'09:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'09:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'09:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'09:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'10:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'10:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'10:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'10:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'10:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'10:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'11:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'11:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'11:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'11:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'11:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'11:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'12:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'12:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'12:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'12:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'12:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'12:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'13:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'13:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'13:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'13:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'13:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'13:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'14:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'14:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'14:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'14:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'14:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'14:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'15:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'15:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'15:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'15:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'15:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'15:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'16:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'16:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'16:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'16:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'16:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'16:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'17:00',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:15',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:15'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'17:15',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:30',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:30'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'17:30',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'17:45',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'17:45'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'17:45',$commande) : ' ',
                    array_key_exists($j->format('d/m/Y').' '.'18:00',$datePrescripteur)&& ($j->format('m')==$mois)? $datePrescripteur[$j->format('d/m/Y').' '.'18:00'].$this->isSameCommande($agenceDate,$j->format('d/m/Y').' '.'18:00',$commande) : ' '

                ];

                // find jour out mois


                if ($j->format('m')!=$mois){
                    array_push($jourOut,$j->format('d/m/Y'));
                }

                //
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
           // die;
            $i++;



        }

        $wArray[]=['TOTAL Plages'];
        $sumCol = 0;
        $sumTotalAll=0;
        // finding the column sum
        for ($i = 1; $i < 42; ++$i) {
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

        $territoireArray=[
          ["Raison Sociale du Titulaire du Marché (mandataire)","ONLINEFORMAPRO"] ,
          ["N° du Marché","14103 (Franche-Comté)"] ,
          ["Adresse du Titulaire du Marché","19 rue du Praley - Espace de la Motte"] ,
          ["Code Postal et Ville","70000"] ,
          ["Téléphone","VESOUL"] ,
          ["Adresse électronique Titulaire du Marché","acp@onlineformapro.com"] ,
          ["Nom et téléphone de la personne en charge de la planification","Vanessa MAQUET, 03 84 78 68 20"] ,
          ["Site demandeur","MONTBELIARD Hexagone"] ,
          ["PRESTATAIRE Intervenant","ONLINEFORMAPRO"] ,
          ["Adresse du lieu de réalisation","57 Avenue chabaud Latour 25200 MONTBELIARD"] ,

        ];

      //  dump($wArray);die;
        return  [$wArray,$jourOut,$territoireArray];

    }





}

