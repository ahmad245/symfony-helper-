<?php

namespace UfmcpBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class MailAbsencesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('daq:mail-absences')
            ->setDescription('Notifie par mail les référents des stagiaires si une absence a été ajoutée récemment')
            ->addArgument(
                'territoire',
                InputArgument::OPTIONAL,
                'ID du territoire sur lequel effectuer la vérification.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $idTerritoire = $input->getArgument('territoire');
        $idTerritoires = [];
        if(empty($idTerritoire)) {
            $query = $em->getConnection()->prepare('
                SELECT id FROM commun.territoire 
            ');
            $query->execute();
            $idTerritoires = $query->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            foreach(explode(',', $idTerritoire) as $id) {
                $idTerritoires[] = (int)$id;
            }
        }
        $idTerritoires = array_unique($idTerritoires);
        sort($idTerritoires);

        foreach($idTerritoires as $idTerritoire) {
            $territoire = $em->getRepository('UfmcpBundle:Territoire')->find($idTerritoire);
            if(empty($territoire)) {
                $output->writeln("Erreur : le territoire ".$idTerritoire." n'existe pas.");
            } else {
                $output->writeln([
                    "================================================================================================",
                    "Vérification des absences à envoyer aux référents sur le territoire : "
                    . $territoire->getNom() . " (".$idTerritoire.")"
                ]);

                $session = new Session();
                $session->set('id_territoire', $territoire->getId());

                $result = $this->check($em, $territoire);
                $output->writeln(array_merge($result, [
                    "================================================================================================",
                    ""
                ]));
            }
        }
    }

    protected function check(EntityManager $em, \UfmcpBundle\Entity\Territoire $territoire)
    {
        $data = $return = [];
        $mailer = $this->getContainer()->get('mailer');
       // $now = (new \DateTime())->modify('-5 minutes')->format('Y-m-d H:i:s');
      //  $now = (new \DateTime('2021-01-12'))->format('Y-m-d H:i:s');
        $now = (new \DateTime())->modify('-1 day')->format('Y-m-d H:i:s');

        $date_formatter = new \IntlDateFormatter(\Locale::getDefault(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
        $date_formatter->setPattern("EEEE d LLLL yyyy");

//        $query = $em->getConnection()->prepare('
//            SELECT s.id, a.id
//            FROM territoire_:id_territoire.absence a
//            INNER JOIN territoire_:id_territoire.stagiaire s
//                ON s.id = a.id_stagiaire
//            WHERE a.date_modification <= :now
//        ');

        $query = $em->getConnection()->prepare('
            SELECT a.id_stagiaire, a.jour,a.demi_journee,a.presence
            FROM territoire_:id_territoire.stagiaire_programmation a

            WHERE a.jour = :now
            AND a.presence IS NOT null
            AND a.presence != 0
        ');
        $query->bindValue(':id_territoire', $territoire->getId(), \PDO::PARAM_INT);
        $query->bindValue(':now', $now, \PDO::PARAM_STR);
        $query->execute();
        $rs = $query->fetchAll(\PDO::FETCH_GROUP);
       // dump($rs);die;
        foreach($rs as $id_stagiaire => $arr_ids_absence) {
            /* @var $stagiaire \UfmcpBundle\Entity\Stagiaire */
            $stagiaire = $em->getRepository('UfmcpBundle:Stagiaire')->find($id_stagiaire);
            $nomStagiaire=($stagiaire->getCivilite() == '0' ? 'M.' : 'Mme.').' '.$stagiaire->getPrenom().' '.$stagiaire->getNom();
            /* @var $referent \UfmcpBundle\Entity\Formateur */
            $referent = $stagiaire->getReferent();
            if(!empty($referent)) {
                if(!isset( $data[$referent->getId()] )) {
                    $data[$referent->getId()] = [
                        'nom_referent' => ($referent->getCivilite() == '0' ? 'M.' : 'Mme.').' '.$referent->getPrenom().' '.$referent->getNom(),
                        'mail_referent' => $referent->getEmail(),
                        'lib_territoire' => $territoire->getNom(),
                        'has_referent' => true,
                        'stagiaires' => []
                    ];
                }
                if(!isset( $data[$referent->getId()]['stagiaires'][$stagiaire->getId()] )) {
                    $data[$referent->getId()]['stagiaires'][$stagiaire->getId()] = [
                        'nom_stagiaire' => ($stagiaire->getCivilite() == '0' ? 'M.' : 'Mme.').' '.$stagiaire->getPrenom().' '.$stagiaire->getNom(),
                        'absences' => []
                    ];
                }
            }
            // Si aucun référent n'est assigné au stagiaire & territoire BELFORT / MONTBELIARD
            //      => envois uniquement à Karine et Valérie
            elseif(empty($referent) && $territoire->getId() == 1) {
                if(!isset( $data['karine'] )) {
                    $data['karine'] = [
                        'nom_referent' => 'Mme. Karine MAEGERLIN',
                        'mail_referent' => 'k.maegerlin@onlineformapro.com',
                        'lib_territoire' => $territoire->getNom(),
                        'has_referent' => false,
                        'stagiaires' => []
                    ];
                }
                if(!isset( $data['valerie'] )) {
                    $data['valerie'] = [
                        'nom_referent' => 'Mme. Valérie DIDIER',
                        'mail_referent' => 'v.didier@onlineformapro.com',
                        'lib_territoire' => $territoire->getNom(),
                        'has_referent' => false,
                        'stagiaires' => []
                    ];
                }
                if(!isset( $data['karine']['stagiaires'][$stagiaire->getId()] )) {
                    $data['karine']['stagiaires'][$stagiaire->getId()] = [
                        'nom_stagiaire' => ($stagiaire->getCivilite() == '0' ? 'M.' : 'Mme.').' '.$stagiaire->getPrenom().' '.$stagiaire->getNom(),
                        'absences' => []
                    ];
                }
                if(!isset( $data['valerie']['stagiaires'][$stagiaire->getId()] )) {
                    $data['valerie']['stagiaires'][$stagiaire->getId()] = [
                        'nom_stagiaire' => ($stagiaire->getCivilite() == '0' ? 'M.' : 'Mme.').' '.$stagiaire->getPrenom().' '.$stagiaire->getNom(),
                        'absences' => []
                    ];
                }
            }

            foreach($arr_ids_absence as $id_absence) {
              //  $id_absence = (int)$id_absence['id'];
                /* @var $absence \UfmcpBundle\Entity\Absence */
//                $absence = $em->getRepository('UfmcpBundle:Absence')->find($id_absence);
//                /* @var $absence_jours \Doctrine\Common\Collections\Collection */
//                $first_aj = $absence->getFirstAbsenceJour();
//                $last_aj = $absence->getLastAbsenceJour();
//                if($first_aj->getJour()->format('d/m/Y') == $last_aj->getJour()->format('d/m/Y')) {
//                    if($first_aj->getDemiJournee() == $last_aj->getDemiJournee()) {
//                        $lib_dates_absence = 'Le '.$date_formatter->format($first_aj->getJour()).' '.((int)$first_aj->getDemiJournee() == 0 ? 'matin' : 'après-midi');
//                    } else {
//                        $lib_dates_absence = 'Le '.$date_formatter->format($first_aj->getJour()).' toute la journée';
//                    }
//                } else {
//                    $lib_dates_absence =
//                        'Du '.$date_formatter->format($first_aj->getJour()).' '.((int)$first_aj->getDemiJournee() == 0 ? 'matin' : 'après-midi').
//                        ' au '.$date_formatter->format($last_aj->getJour()).' '.((int)$last_aj->getDemiJournee() == 0 ? 'matin' : 'après-midi');
//                }
//                if(!is_null($absence->getMotif())){
//                    $lib_motif = $absence->getMotif()->getIntitule();
//                }

            //   dump( (new \DateTime($id_absence['jour']))->format('d/m/Y'));die;
                $dateFormat=(new \DateTime($id_absence['jour']))->format('d/m/Y');
                $ampm=$id_absence['demi_journee'] == 0 ? ' au matin ' : ' l’après-midi ';
                $lib_dates_absence = 'Absences du '.$dateFormat .$ampm;
              //  dump($id_absence['jour'],$lib_dates_absence);die;
                if(empty($lib_motif)) {
                    $lib_motif = "Motif d'absence non renseigné";
                }

                if(!empty($referent)) {
                    if(!isset( $data[$referent->getId()]['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] )) {
                        $data[$referent->getId()]['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] = [
                            'date_modif_absence' => 'le '.$id_absence['jour'],
                            'lib_motif' => $lib_motif,
                            'lib_date_absence' => $lib_dates_absence,
                            'nom_stagiaire'=>$nomStagiaire
                        ];
                    }
                }
                // Si aucun référent n'est assigné au stagiaire & territoire BELFORT / MONTBELIARD
                //      => envois uniquement à Karine et Valérie
                elseif(empty($referent) && $territoire->getId() == 1) { // BELFORT / MONTBELIARD uniquement
                    if(!isset( $data['karine']['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] )) {
                        $data['karine']['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] = [
                            'date_modif_absence' => 'le '.$id_absence['jour'],
                            'lib_motif' => $lib_motif,
                            'lib_date_absence' => $lib_dates_absence,
                            'nom_stagiaire'=>$nomStagiaire
                        ];
                    }
                    if(!isset( $data['valerie']['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] )) {
                        $data['valerie']['stagiaires'][$stagiaire->getId()]['absences'][$id_absence['jour'].'/'.$id_absence['demi_journee']] = [
                            'date_modif_absence' => 'le '.$id_absence['jour'],

                            'lib_motif' => $lib_motif,
                            'lib_date_absence' => $lib_dates_absence,
                            'nom_stagiaire'=>$nomStagiaire

                        ];
                    }
                }
            }
        }


       // dump(count($data));die;

        foreach($data as $d) {
            $dataDate=[];

            foreach ($d['stagiaires'] as $keyResult=>$absences){

                foreach ($absences['absences'] as $keyAbsence=>$valueAbsence){
                    //   dump($keyAbsence);
                    if(array_key_exists($keyAbsence,$dataDate)){
                        array_push($dataDate[$keyAbsence],$valueAbsence);

                    }else{
                        $dataDate[$keyAbsence]=[];
                        array_push($dataDate[$keyAbsence],$valueAbsence);

                    }

                }
            }


//            foreach ($dataDate as $dataFr){
//                dump($dataFr[0]['lib_date_absence']);
//                foreach ($dataFr as $result){
//                    dump($result['nom_stagiaire']);
//                }
//            }
           // dump('qsdqsd');
           // die();
          //  $d['dataFormat']=$dataDate;
          //  dump($dataDate);die;

            $body = $this->getContainer()->get('templating')->render('mails/mail-absences.html.twig', ["data" => $d,'dataFormat'=>$dataDate]);
            $message = \Swift_Message::newInstance()
                ->setSubject("DAQ 2.0 - Nouvelles absences enregistrées")
                ->setFrom($this->getContainer()->getParameter("mailer_sender_mail"), $this->getContainer()->getParameter("mailer_sender_name"))
               // ->setFrom('basimahagothman123@gmail.com')
                ->setTo($d['mail_referent'])
               // ->setTo('a.almasri@onlineformapro.com')
                ->setBody($body, 'text/html')
            ;
            if($territoire->getId() == 1 && $d['has_referent']) { // BELFORT / MONTBELIARD
//                $message->setBcc([
//                    'k.maegerlin@onlineformapro.com',
//                    'v.didier@onlineformapro.com'
//                ]);
            }
            $sent = $mailer->send($message);

            if($sent) {
                $return[] = "La notification a bien été délivrée à : \n\t- ".$d['mail_referent'];
            } else {
                $return[] = "La notification n'a pas pu être délivrée à : \n\t- ".$d['mail_referent'];
            }

            if($territoire->getId() == 1 && $d['has_referent']) { // BELFORT / MONTBELIARD
                $return[] = "\t- k.maegerlin@onlineformapro.com (en copie)";
                $return[] = "\t- v.didier@onlineformapro.com (en copie)";
            }

        }

        return $return;
    }

    public function group_by($key, $data) {
        $result = array();

        foreach($data as $val) {
            if(array_key_exists($key, $val)){
                $result[$val[$key]][] = $val;
            }else{
                $result[""][] = $val;
            }
        }
        Ksort($result);

        return $result;
    }
}
