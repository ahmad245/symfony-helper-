<?php

namespace UfmcpBundle\PDF;

use Symfony\Component\DependencyInjection\Container;
use UfmcpBundle\Controller\ReunionController;
use UfmcpBundle\Entity\Motif;
use UfmcpBundle\Entity\Parcours;
use UfmcpBundle\Entity\Stagiaire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;
use Doctrine\ORM\EntityManager;
use UfmcpBundle\Service\Utils;
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

        $pdf->AddPage();

        $projDir = $this->container->get('kernel')->getProjectDir();

        $pdf->setSourceFile($projDir . '/src/UfmcpBundle/PDF/Templates/bilan.pdf');
        $pdf->SetFontSize(9);


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
        $pdf->SetXY(38.8, 57.8);
        $pdf->Cell(60, 0, $stagiaire->getNom().' '.$stagiaire->getPrenom(), $drawBorders);

        // Identifiant PE
        $pdf->SetXY(38.8, 65.7);
        $pdf->Cell(60, 0, $stagiaire->getIdPe(), $drawBorders);

        // Tel
        if(!empty($stagiaire->getTelephone())) {
            $pdf->SetXY(23.8, 79.6);
            $pdf->Cell(45.2, 0, str_replace(' ', '', trim($stagiaire->getTelephone())), $drawBorders, 0, '', false, '', 4);
        }

        // Mail
        $splitMail = explode('@', $stagiaire->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(22.5, 87.5);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(72.3, 87.5);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }

        // OF
        // Nom
        $pdf->SetXY(117.8, 57.8);
        $pdf->Cell(70, 0, $organisme->getNom(true), $drawBorders);

        // Lieu
        $pdf->SetXY(107, 70.4);
        $pdf->Cell(90, 0, $site->getAdresse().' '.$site->getAdresseCplt(), $drawBorders);

        // Code postal
        $pdf->SetXY(107, 75);
        $pdf->Cell(22, 0, $site->getCp(), $drawBorders, 0, '', false, '', 4);

        // Ville
        $pdf->SetXY(137, 75);
        $pdf->Cell(60, 0, $site->getVille(), $drawBorders);

        // Tel
        $lOrgSiteLieu = $em->getRepository('UfmcpBundle:LOrganismeLieu')->findOneBy([
            'organismeSite' => $orgSite,
            'lieuFormation' => $site
        ]);

        if(!empty($lOrgSiteLieu)) {
            $pdf->SetXY(117.6, 83);
            $pdf->Cell(45.6, 0, str_replace(' ', '', trim($lOrgSiteLieu->getTel1())), $drawBorders, 0, '', false, '', 4);
        }

        // Mail
        $splitMail = explode('@', $orgSite->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(116.2, 90.5);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(166, 90.5);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }

        // Prescripteur
        // Nom prénom
        $pdf->SetXY(39.5, 108.8);
        $pdf->Cell(70, 0, $prescripteur->getNom().' '.$prescripteur->getPrenom(), $drawBorders);

        // Agence
        $pdf->SetXY(41.5, 116.9);
        $pdf->Cell(70, 0, $prescripteur->getLibelleStructure(), $drawBorders);

        // Référent
        // Nom prénom
        $pdf->SetXY(132, 108.8);
        $pdf->Cell(70, 0, $referent->getNom().' '.$referent->getPrenom(), $drawBorders);

        // Tel
        if(!empty($referent->getTelephone())) {
            $pdf->SetXY(117.6, 116.9);
            $pdf->Cell(45.6, 0, str_replace(' ', '', trim($referent->getTelephone())), $drawBorders, 0, '', false, '', 4);
        }

        $splitMail = explode('@', $referent->getEmail());

        if(count($splitMail) > 1) {
            $pdf->SetXY(116.2, 124.5);
            $pdf->Cell(45, 0, $splitMail[0], $drawBorders);
            $pdf->SetXY(166, 124.5);
            $pdf->Cell(30, 0, $splitMail[1], $drawBorders);
        }

        // Motif
        if($stagiaire->getStatut() == Stagiaire::STATUT_FIN_PARCOURS) {
            $pdf->SetXY(10.6, 145.8);
            $pdf->Cell(10, 10, 'X', $drawBorders, 0);
        } else {
            $pdf->SetXY(10.6, 164);
            $pdf->Cell(10, 10, 'X', $drawBorders, 0);

            $pdf->SetXY(126.5, 166.3);
            $pdf->Cell(7.5, 0, $dateFin->format('d'), $drawBorders, 0, '', false, '', 4);
            $pdf->SetX(139);
            $pdf->Cell(7.5, 0, $dateFin->format('m'), $drawBorders, 0, '', false, '', 4);
            $pdf->SetX(151.5);
            $pdf->Cell(7.5, 0, $dateFin->format('y'), $drawBorders, 0, '', false, '', 4);

            $drawCheck = false;
            if(!empty($stagiaire->getCodeMotifSortie())) {
                switch ($stagiaire->getCodeMotifSortie()->getCode()) {
                    case Motif::MOTIF_RETOUR_EMPLOI:
                        $pdf->SetXY(26.4, 181.8);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_CREATION_REPRISE_ENTREPRISE:
                        $pdf->SetXY(26.4, 187.8);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_ENTREE_FORMATION:
                        $pdf->SetXY(26.4, 193.9);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_ARRET_MALADIE_CONGE_MATERNITE:
                        $pdf->SetXY(26.4, 200.2);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_RAISON_MATERIELLE:
                        $pdf->SetXY(26.4, 206.4);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_DEMENAGEMENT:
                        $pdf->SetXY(26.4, 212.6);
                        $drawCheck = true;
                        break;
                    case Motif::MOTIF_AUTRE:
                        $pdf->SetXY(72, 221.8);
                        $pdf->Cell(100, 0, $stagiaire->getMotifSortie(), $drawBorders, 0);
                        $pdf->SetXY(26.4, 218.7);
                        $drawCheck = true;
                        break;
                }
            }

            if($drawCheck) {
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            }
        }

        // Page 2 - Entretien initial

        $pdf->AddPage();

        $tplId = $pdf->importPage(2);

        $pdf->useTemplate($tplId, 0, 0);

        $entInit = $stagiaire->getEntretienInitial();

        if(!empty($entInit)) {
            $pdf->SetXY(60.5, 35);
            $pdf->Cell(7.5, 0, $entInit->getDateHeure()->format('d'), $drawBorders, 0, '', false, '');
            $pdf->SetX(73.5);
            $pdf->Cell(7.5, 0, $entInit->getDateHeure()->format('m'), $drawBorders, 0, '', false, '');
            $pdf->SetX(84.5);
            $pdf->Cell(7.5, 0, $entInit->getDateHeure()->format('Y'), $drawBorders, 0, '', false, '');

            $pdf->SetXY(30.5, 39.5);
            $pdf->Cell(70, 0, $entInit->getFormateur()->getNom().' '.$entInit->getFormateur()->getPrenom(), $drawBorders);
        }

        $preinscription = $em->getRepository('UfmcpBundle:Preinscription')->findOneByStagiaire($stagiaire);
        if(!empty($preinscription)) {
            if(stripos($preinscription->getObjectifPrestation(), 'elaborer') !== false) {
                $pdf->SetXY(7.4, 55.7);
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            } else if(stripos($preinscription->getObjectifPrestation(), 'confirmer') !== false) {
                $pdf->SetXY(7.4, 63.2);
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            }
        }

        $pdf->SetXY(46, 76);
        if($stagiaire->getTypeProjetPro() > 0) {
            $pdf->MultiCell(149, 12, $stagiaire->getProjetPro(), $drawBorders, 'L');
        } else {
            $pdf->MultiCell(149, 12, "Aucune", $drawBorders, 'L');
        }

        if(!empty($entInit)) {
            $pdf->SetXY(13, 100);
            $pdf->MultiCell(177, 40, $entInit->getCommentaire(), $drawBorders, 'L');
        }

        $bilan = $stagiaire->getBilan();

        $iPlan = 1;
        $offsetPlan = 0;
        $pdf->SetXY(7.4, 195.3);
        $cellHeightRatio = $pdf->getCellHeightRatio();
        if(isset($bilan['plan-action-encours'])) {
            $pdf->setCellHeightRatio(0.8);

            foreach($bilan['plan-action-encours'] as $oPlan => $plan) {
                if(empty($plan['action'])) {
                    continue;
                }

                $pdf->SetFontSize(8);
                $pdf->SetX(7.4);
                $pdf->MultiCell(66.5, 12.1, $plan['action'], $drawBorders, 'L', false, 0);
                $pdf->SetFontSize(9);
                $pdf->SetX(73.9);
                $pdf->MultiCell(30, 12.1, $plan['date_previsionnelle'], $drawBorders, 'C', false, 0, '', '', true, 0, false, true, 12.1, 'M');
                $pdf->SetX(103.9);
                $pdf->SetFontSize(8);
                $pdf->MultiCell(62.5, 12.1, $plan['commentaire'], $drawBorders, 'L', false, 0);
                $pdf->SetFontSize(9);
                $pdf->SetX(166.4);
                $pdf->MultiCell(34, 12.1, $plan['date_realisation'], $drawBorders, 'C', false, 0, '', '', true, 0, false, true, 12.1, 'M');
                $pdf->Ln();
                $offsetPlan = $oPlan;
                $iPlan++;

                if($iPlan > 7) {
                    break;
                }
            }
        }
        $pdf->setCellHeightRatio($cellHeightRatio);

        // Page 3
        $pdf->AddPage();

        $tplId = $pdf->importPage(3);

        $pdf->useTemplate($tplId, 0, 0);

        $pdf->SetXY(7.4, 44.6);
        $cellHeightRatio = $pdf->getCellHeightRatio();
        if(isset($bilan['plan-action-encours'])) {
            $pdf->setCellHeightRatio(0.8);

            foreach($bilan['plan-action-encours'] as $oPlan => $plan) {
                if($oPlan <= $offsetPlan || empty($plan['action'])) {
                    continue;
                }

                $pdf->SetFontSize(8);
                $pdf->SetX(7.4);
                $pdf->MultiCell(66.5, 12.1, $plan['action'], $drawBorders, 'L', false, 0);
                $pdf->SetFontSize(9);
                $pdf->SetX(73.9);
                $pdf->MultiCell(30, 12.1, $plan['date_previsionnelle'], $drawBorders, 'C', false, 0, '', '', true, 0, false, true, 12.1, 'M');
                $pdf->SetX(103.9);
                $pdf->SetFontSize(8);
                $pdf->MultiCell(62.5, 12.1, $plan['commentaire'], $drawBorders, 'L', false, 0);
                $pdf->SetFontSize(9);
                $pdf->SetX(166.4);
                $pdf->MultiCell(34, 12.1, $plan['date_realisation'], $drawBorders, 'C', false, 0, '', '', true, 0, false, true, 12.1, 'M');
                $pdf->Ln();
                $offsetPlan = $oPlan;
                $iPlan++;
            }
        }

        // Page 4 - entretien mi-parcours
        $pdf->SetFontSize(9);

        $pdf->setCellHeightRatio($cellHeightRatio);

        $pdf->AddPage();

        $tplId = $pdf->importPage(4);

        $pdf->useTemplate($tplId, 0, 0);

        $entInter = $stagiaire->getEntretienIntermediaire();

        if(!empty($entInter) && $entInter->getDuree() > 0) {
            $pdf->SetXY(48.5, 37);
            $pdf->Cell(7.5, 0, $entInter->getDateHeure()->format('d'), $drawBorders, 0, '', false, '');
            $pdf->SetX(60.5);
            $pdf->Cell(7.5, 0, $entInter->getDateHeure()->format('m'), $drawBorders, 0, '', false, '');
            $pdf->SetX(72.3);
            $pdf->Cell(7.5, 0, $entInter->getDateHeure()->format('Y'), $drawBorders, 0, '', false, '');

            $pdf->SetXY(30.5, 46.5);
            $pdf->Cell(70, 0, $entInter->getFormateur()->getNom().' '.$entInter->getFormateur()->getPrenom(), $drawBorders);

            $cellHeightRatio = $pdf->getCellHeightRatio();
            $pdf->SetXY(13, 74.5);
            $pdf->MultiCell(177, 40, $entInter->getCommentaire(), $drawBorders, 'L');
        }

        // Page 5 - entretien final

        $pdf->AddPage();

        $tplId = $pdf->importPage(5);

        $pdf->useTemplate($tplId, 0, 0);

        $entBilan = $stagiaire->getEntretienBilan();

        if(!empty($entBilan) && $entBilan->getDuree() > 0) {
            $pdf->SetXY(48.5, 33.8);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('d'), $drawBorders, 0, '', false, '');
            $pdf->SetX(60.8);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('m'), $drawBorders, 0, '', false, '');
            $pdf->SetX(72.3);
            $pdf->Cell(7.5, 0, $entBilan->getDateHeure()->format('Y'), $drawBorders, 0, '', false, '');

            $pdf->SetXY(30.5, 43.2);
            $pdf->Cell(70, 0, $entBilan->getFormateur()->getNom().' '.$entBilan->getFormateur()->getPrenom(), $drawBorders);

            $pdf->SetXY(13, 70);
            $pdf->MultiCell(177, 40, $entBilan->getCommentaire(), $drawBorders, 'L');
        }

        if(!empty($bilan)) {
            if(!empty($bilan['points-autonomie'])) {
                $pdf->SetXY(10, 61);
                $pdf->MultiCell(182, 26, $bilan['points-autonomie'], $drawBorders, 'L');
            }

            if(!empty($bilan['projets-identifies'])) {
                $pdf->SetXY(10, 101);
                $pdf->MultiCell(182, 26, $bilan['projets-identifies'], $drawBorders, 'L');
            }

            if(!empty($bilan['points-a-travailler'])) {
                $pdf->SetXY(10, 141);
                $pdf->MultiCell(182, 26, $bilan['points-a-travailler'], $drawBorders, 'L');
            }

            if(isset($bilan['profil-competences-maj']) && $bilan['profil-competences-maj'] == 1) {
                $pdf->SetXY(95, 174.3);
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);
            } else {
                $pdf->SetXY(113.8, 174.3);
                $pdf->Cell(10, 10, 'X', $drawBorders, 0);

                if(!empty($bilan['profil_motif-non-maj'])) {
                    $pdf->SetXY(162, 177);
                    $pdf->MultiCell(32, 10, $bilan['profil_motif-non-maj'], $drawBorders, 'L');
                }
            }

            if(!empty($bilan['plan-action-final'])) {
                $iPlan = 1;
                $pdf->SetXY(13, 207.1);
                $cellHeightRatio = $pdf->getCellHeightRatio();
                if(isset($bilan['plan-action-final'])) {
                    $pdf->setCellHeightRatio(0.8);

                    foreach($bilan['plan-action-final'] as $oPlan => $plan) {
                        if(empty($plan['projets']) && empty($plan['actions'])) {
                            continue;
                        }

                        $pdf->SetFontSize(8);
                        $pdf->SetX(9.4);
                        $pdf->MultiCell(58, 10.2, $plan['projets'], $drawBorders, 'L', false, 0);
                        $pdf->SetX(67.4);
                        $pdf->MultiCell(61, 10.2, $plan['actions'], $drawBorders, 'L', false, 0);
                        $pdf->SetX(128.4);
                        $pdf->MultiCell(21.9, 10.2, $plan['echeance'], $drawBorders, 'L', false, 0);
                        $pdf->SetX(150.3);
                        $pdf->MultiCell(54, 10.2, $plan['commentaire'], $drawBorders, 'L', false, 0);
                        $pdf->Ln();

                        $pdf->SetFontSize(9);

                        $iPlan++;

                        if($iPlan > 6) {
                            break;
                        }
                    }
                }
                $pdf->setCellHeightRatio($cellHeightRatio);
            }
        }

        return $pdf;
    }
}