<?php
/**
 * Created by PhpStorm.
 * User: a.almasri
 * Date: 11/08/2020
 * Time: 10:46
 */

namespace UfmcpBundle\PDF;

use UfmcpBundle\Entity\Parcours;
use UfmcpBundle\Entity\Stagiaire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;

class Mission extends \TCPDF {

    public $missionObject;
    public $enrepriseLibels=[
        'Nom de l’Entreprise',
        'SIRET',
        'Statut juridique',
        'Convention collective',
        'Activité',
        'Nombre de salariés permanents',
        'Adresse de facturation',
        'Contact'
        ];

    public $missionHORAIRES  =[
        'Début de la commande',
        'Durée de la mission',
        'Horaires de travail',
        'Lieu de travail',
        'Durée globale estimée / récurrence du besoin'
    ];
    public $missionSERVICE   =[
        'Service',
        'Référent du salarié en entreprise',
        'Supérieur hiérarchique ',
        'Relations internes',
        'Relations externes'
    ];
    public $missionEQUIPEMENTS   =[
        'Fournis par l’entreprise',
        'Fournis par l’Association Intermédiaire',
        'Tenue de travail exigée'

    ];
    public $data            = array();
    public $_h              = 4.5; // hauteur de ligne
    public $aTheme          = array(42,174,98);
    public $aTheme2         = array(39,215,253); // Couleurs des encacrés
    public $default_font    = 'helvetica';
    public $calibri_font    = 'helvetica';
    public $agencyfb_font   = 'dejavusanscondensed';
    public $logo_path = '';
    public $logo_focale = '';
    public $focale_text = '';
    public $bandeau_header  = '';
    public $bandeau_footer  = '';
    public $img_tampon  = '';
    public $image_centre    = '';
    public $title           = "";

    /**
     * @var EntityManager
     */
    public $em = NULL;

    /**
     * @var ContainerInterface
     */
    private $container;

    private  $upload_dir;


    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false, false);

        $this->SetMargins(18, 0, 18, true);
        $this->SetDisplayMode('fullpage');
        $this->AddFont('dejavusans');
        $this->SetTextColor(0, 0, 0);
        $this->setPrintHeader(false);
        $this->SetPrintFooter(true);
        $this->SetAutoPageBreak(true, 35);
        $this->SetTopMargin(25);
        $this->AddPage();
        $this->container = $container;


    }
    public function Footer() {
        // Position at 15 mm from bottom

        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $footerImg= dirname(__DIR__).'/Resources/public/img/footer.png';

        $this->Cell(0, 0,   $this->Image($footerImg, $x=$this->getX()-20, $y=$this->getY()-15, '', '25', 'PNG'), 0, false, 'C', 0, '', 0, false, 'T', 'M');

    }



    public function Header(){
      //  $this->SetMargins(18, 50, 18, true);

        $upload_dir = $this->container->getParameter('upload_directory');
        $path = $upload_dir.'/mission/'.$this->missionObject->getEntreprise()->getId();

        // récupération du logo
        $this->logo_path = $path.'/entrepriselogo.png';
//        if(is_file($this->logo_path)) {
//            $this->Image($this->logo_path, 10, 4, 20, 0, 'PNG');
//        }

        $this->logo_focale=dirname(__DIR__).'/Resources/public/img/focale.png';
        $this->focale_text=dirname(__DIR__).'/Resources/public/img/focaleText.png';

        $css = '
            <style type="text/css">
            
              
            table tr  th{
             font-weight:bolder;
              }
              
            
                .header {
                    font-size: 3.6mm;
                }
                .center {
                    text-align:center;
                }
                .title {
                    font-size: 9px;
                }
                .small {
                    font-size:7px;
                }
                .date-font {
                    font-size: 8px;
                }
                .font-bold {
                    font-weight: bold;
                }
                .border-bottom{
                  border-bottom: 1px solid black;
                }
                .header-spacer {
                    height: 8mm;
                    line-height: 8mm;
                }
                .bg_grey{
                   background-color:#CDCDCD;
                }
                .redFont{
                  color: #FF0000;
                }
                .ggg{
                padding: 3rem;
                }
            </style>
        ';




        //////////////////////////////////////////////////////////
        ///



        $this->Image($this->logo_path, $x='22', $y='37', 25, 19, 'PNG');
        $this->Image($this->logo_focale, $x='150', $y='26', 41, 20, 'PNG');
        $this->Image($this->focale_text, $x='150', $y='48', 41, 10, 'PNG');

        $header =  ' 
                 <tr >
                       <th colspan="3" style="text-align: right" class="" >'.date('d/m/Y').'</th>
                 </tr>';

        $bufferHeader = $css.'<br><br><br><br><br><br> <table class="header" border="1"  cellpadding="6" cellspacing="1" style="width: 75%">'.$header;
        $rowsHeader   ='

                  <tr >
                    <td class="center ggg" cellpadding="20" width="80" height="60"></td>
                    <td class="redFont center center-align" colspan="2" style="height: 55px;font-size: 14px;font-weight: bolder; width: 78%;" ><br><br>AMBASSADEUR DU TRI F/H</td>
                  </tr>
            ';
        $para='<p>ACTIVITE REMUNEREE A LA CARTE</p>';


        // writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true)
        $this->writeHTMLCell('', '', '19', '0', $bufferHeader . $rowsHeader. '</table>', 0, 0, 0, true, 'J', true);

      // $this->writeHTML($bufferHeader . $rowsHeader. '</table>', true, false, false, false, '');


      //  $this->Cell(0, 0,  $bufferHeader . $rowsHeader. '</table>', 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }




    public function show( $fileName = '',\UfmcpBundle\Entity\Mission $mission)
    {
        $this->missionObject=$mission;
        $this->setPrintHeader(true);
        $this->missionObject=$mission;

        $this->Content($mission);

        /**
         * Output
         */
        $this->Output(!empty($fileName)? $fileName : 'Plan_Individuel_Formation.pdf','I');
    }
    public function showApi( $fileName = '',\UfmcpBundle\Entity\Mission $mission)
    {


        //  dump($mission);die();
        /**
         * Construction du Doc
         */
        // $this->WriteTitle('');

        $this->missionObject=$mission;
        $this->setPrintHeader(true);
        $this->missionObject=$mission;
        $this->Content($mission);
//        $this->Footer();

        /**
         * Output
         */
        $this->Output($upload_dir = $this->container->getParameter('upload_directory').'/'.$mission->getId().'.pdf','F');
    }
    public function Content(\UfmcpBundle\Entity\Mission $mission){





        $upload_dir = $this->container->getParameter('upload_directory');
        $path = $upload_dir.'/mission/'.$mission->getEntreprise()->getId();

        // récupération du logo
        $this->logo_path = $path.'/entrepriselogo.png';
//        if(is_file($this->logo_path)) {
//            $this->Image($this->logo_path, 10, 4, 20, 0, 'PNG');
//        }

        $this->logo_focale=dirname(__DIR__).'/Resources/public/img/focale.png';
        $this->focale_text=dirname(__DIR__).'/Resources/public/img/focaleText.png';
        // dump($this->logo_focale);die;

//        table
//              {
//                  padding: 20px;
//                border: 0.1px solid black;
//                border-collapse: collapse;
//
//              }
        $css = '
            <style type="text/css">
            
              
            table tr  th{
             font-weight:bolder;
              }
              
            
                .header {
                    font-size: 3.6mm;
                }
                .center {
                    text-align:center;
                }
                .title {
                    font-size: 9px;
                }
                .small {
                    font-size:7px;
                }
                .date-font {
                    font-size: 8px;
                }
                .font-bold {
                    font-weight: bold;
                }
                .border-bottom{
                  border-bottom: 1px solid black;
                }
                .header-spacer {
                    height: 8mm;
                    line-height: 8mm;
                }
                .bg_grey{
                   background-color:#CDCDCD;
                }
                .redFont{
                  color: #FF0000;
                }
                .ggg{
                padding: 3rem;
                }
            </style>
        ';




        //////////////////////////////////////////////////////////
        ///



        $this->Image($this->logo_path, $x='20', $y='35', 25, 20, 'PNG');
        $this->Image($this->logo_focale, $x='150', $y='25', 40, 20, 'PNG');
        $this->Image($this->focale_text, $x='150', $y='47', 40, 10, 'PNG');

        $header = '<tr >
                       <th colspan="3" style="text-align: right" class="" >'.date('d/m/Y').'</th>
                 </tr>';

        $bufferHeader = $css.'<table class="header" border="1"  cellpadding="6" cellspacing="1" style="width: 75%">'.$header;
        $rowsHeader   ='
                
                  <tr > 
                    <td class="center ggg" cellpadding="20" width="80" height="60"></td> 
                    <td class="redFont center center-align" colspan="2" style="height: 55px;font-size: 14px;font-weight: bolder; width: 78%;" ><br><br>AMBASSADEUR DU TRI F/H</td> 
                  </tr>
            ';
        $para='<p>ACTIVITE REMUNEREE A LA CARTE</p>';

        $this->writeHTML($bufferHeader . $rowsHeader. '</table>', true, false, false, false, '');



        ///
        ///
        //  dump($mission);die();
        $headerEntreprise = '<tr >
                  <th colspan="3" align="center" class="bg_grey" >Entreprise</th>
                 </tr>';

        $headerHORAIRES  = '<tr>
                  <th colspan="3" align="center">HORAIRES ET LIEU DE TRAVAIL</th>
                 </tr>';
        $bufferEntreprise = $css.'<table class="header" border="1" cellpadding="6" cellspacing="1" >'.$headerEntreprise;

        $rowsEntreprise = '
                    <tr > <td class="center ">'.$this->enrepriseLibels[0].'</td> <td class="redFont" colspan="2">'.$mission->getEntreprise() ->getNom().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[1].'</td> <td colspan="2">'.$mission->getEntreprise() ->getNumSiret().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[2].'</td> <td colspan="2">'.$mission->getEntreprise() ->getStatutJuridique().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[3].'</td> <td colspan="2" class="redFont">'.$mission->getEntreprise() ->getConventionCollective().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[4].'</td> <td colspan="2">'.$mission->getEntreprise() ->getActivite().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[5].'</td> <td colspan="2">'.$mission->getEntreprise() ->getNombreSalaries().'</td> </tr>
                    <tr > <td class="center ">'.$this->enrepriseLibels[6].'</td> <td colspan="2">'.$mission->getEntreprise() ->getFacturationAdresse().'</td> </tr>
                    <tr style=""> 
                     <td class=" " style="height: 100%; horiz-align: center; text-align: center; vertical-align: middle;" ><br><br><br><br><br> '.$this->enrepriseLibels[7].'</td> 
                     <td colspan="2">
                       <table border="0" class="nob"  cellpadding="2" cellspacing="1"> 
                         <tr> <td class="border-bottom">'.$mission->getEntreprise() ->getFacturationNom().'</td> </tr>
                         <tr> <td class="border-bottom">'.$mission->getEntreprise() ->getFacturationPrenom().'</td> </tr>
                         <tr> <td class="border-bottom">'.$mission->getEntreprise() ->getFacturationFonction().'</td> </tr>
                         <tr> <td class="border-bottom">'.$mission->getEntreprise() ->getFacturationTelPortable().'</td> </tr>
                         <tr> <td class="border-bottom">'.$mission->getEntreprise() ->getFacturationEmail().'</td> </tr>
                         <tr> <td>'.$mission->getEntreprise() ->getFacturationAdresse().'</td> </tr>
                         
                       </table>  
                     </td> 
                     </tr>
                    
                     ';

        $rowsHoraires='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">HORAIRES ET LIEU DE TRAVAIL</th>
                 </tr>
                 <tr > <td class="center ">'.$this->missionHORAIRES[0].'</td> <td colspan="2" class="redFont">'.($mission->getDateDebut() ? $mission->getDateDebut()->format('d/m/Y') : " " ).'</td> </tr>
                    <tr > <td class="center ">'.$this->missionHORAIRES[1].'</td> <td colspan="2"  class="redFont">'.$mission->getDuree().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionHORAIRES[2].'</td> <td colspan="2"  class="redFont">'.$mission->getCommentaire().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionHORAIRES[3].'</td> <td colspan="2"  class="redFont">'.$mission->getAdresse().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionHORAIRES[4].'</td> <td colspan="2">'.$mission->getDureeGlobaleEstimee().'</td> </tr>
                 
            ';

        $rowsSERVICE ='
 
                <tr>
                   <th class="bg_grey" colspan="3" align="center">SERVICE ET RATTACHEMENT HIERARCHIQUE</th>
                 </tr>
                 <tr > <td class="center ">'.$this->missionSERVICE[0].'</td> <td colspan="2">'.$mission->getServiceRattachement()[0]->getService().'</td> </tr>
                 
                 </table>
                
                          <br><br><br><br><br><br><br><br>
                        
                    <table  border="1" cellpadding="6" cellspacing="1">
                    <tr > <td class="center ">'.$this->missionSERVICE[1].'</td> <td colspan="2">'.$mission->getServiceRattachement()[0]->getFonction().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionSERVICE[2].'</td> <td colspan="2">'.$mission->getServiceRattachement()[0]->getRole().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionSERVICE[3].'</td> <td colspan="2">'.$mission->getRelationsInternes().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionSERVICE[4].'</td> <td colspan="2">'.$mission->getRelationsExternes().'</td> </tr>
                  
                 
            ';

        $rowsEQUIPEMENTS  ='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">EQUIPEMENTS DE PROTECTION INDIVIDUELLE</th>
                 </tr>
                 <tr > <td class="center ">'.$this->missionEQUIPEMENTS[0].'</td> <td colspan="2">'.$mission->getEquipementsFournisEntreprise().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionEQUIPEMENTS[1].'</td> <td colspan="2">'.$mission->getEquipementsFournisAssociation().'</td> </tr>
                    <tr > <td class="center ">'.$this->missionEQUIPEMENTS[2].'</td> <td colspan="2">'.$mission->getTenueTravailExigee().'</td> </tr>
                   
                 
            ';
        $rowsPOINTS  ='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">POINTS DE VIGILANCE</th>
                 </tr>
                 <tr > <td class="center ">	Contraintes / difficultés de l’activité</td> <td colspan="2">'.$mission->getPointsVigilance().'</td> </tr>
            ';
        $this->writeHTML($bufferEntreprise . $rowsEntreprise .$rowsHoraires.$rowsSERVICE.$rowsEQUIPEMENTS.$rowsPOINTS. '</table>', true, false, false, false, '');
        $this->addPage();

        $rowsMISSIONS  = $css.'
               <br><br><br><br><br><br><br><br>
               <table class="header" border="1" cellpadding="6" cellspacing="1">
                <tr>
                   <th class="bg_grey" colspan="3" align="center">MISSIONS</th>
                 </tr>
                 <tr > <td colspan="3" class="redFont">'.$mission->getDescription().'</td> </tr>
                 
                 
            ';

        if(strlen($mission->getDescription()) > 2928){
            $this->writeHTML($rowsMISSIONS. '</table>', true, false, false, false, '');
            $this->addPage();

            $rowsFORMATION   =' <br><br><br><br><br><br><br><br>
               <table class="header" border="1" cellpadding="6" cellspacing="1">
                <tr>
                   <th class="bg_grey" colspan="3" align="center">FORMATION ET SECURITE</th>
                 </tr>
                 <tr > <td colspan="3">'.$mission->getFormationCommentaire().'</td> </tr>
            ';
            $rowsCOMPETENCES    ='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">COMPETENCES REQUISES</th>
                 </tr>
                 <tr > <td colspan="3" class="redFont">Je ne sais pas </td> </tr>
            ';
            $rowsINFOS='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">INFOS COMPLEMENTAIRES</th>
                 </tr>
                 <tr > <td colspan="3">'.$mission->getInfosComplementaires().' </td> </tr>
            ';
            $this->writeHTML($rowsFORMATION.$rowsCOMPETENCES.$rowsINFOS. '</table>', true, false, false, false, '');
        }
        else{
            $rowsFORMATION   ='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">FORMATION ET SECURITE</th>
                 </tr>
                 <tr > <td colspan="3">'.$mission->getFormationCommentaire().'</td> </tr>
            ';
            $rowsCOMPETENCES    ='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">COMPETENCES REQUISES</th>
                 </tr>
                 <tr > <td colspan="3" class="redFont">Je ne sais pas </td> </tr>
            ';
            $rowsINFOS='
                <tr>
                   <th class="bg_grey" colspan="3" align="center">INFOS COMPLEMENTAIRES</th>
                 </tr>
                 <tr > <td colspan="3">'.$mission->getInfosComplementaires().' </td> </tr>
            ';

            $this->writeHTML($rowsMISSIONS.$rowsFORMATION.$rowsCOMPETENCES.$rowsINFOS. '</table>', true, false, false, false, '');
        }


      //  return $this;
        // $this->writeHTMLCell(0, 0, '', '', $bufferEntreprise . $rows . '</table>',false,true,false,true, '', false);
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////



        //  $this->writeHTMLCell(0, 0, '', '', $bufferHORAIRES . $rowsHORAIRES . '</table>',false,true,false,true, '', false);

        ///

        // $this->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $bufferEntreprise . $rowsEntreprise .$rowsHoraires.$rowsSERVICE.$rowsEQUIPEMENTS.$rowsPOINTS.$rowsMISSIONS.$rowsFORMATION.$rowsCOMPETENCES.$rowsINFOS. '</table>', $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);
        // $this->writeHTML($text,'',false,'',true);
        //  $this->writeHTML($tbl, true, false, false, false, '');
    }

}




