<?php
/**
 * Created by PhpStorm.
 * User: a.almasri
 * Date: 05/08/2020
 * Time: 09:59
 */



namespace UfmcpBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\CallbackTransformer;

use UfmcpBundle\Entity\Module;
use UfmcpBundle\Entity\Domaine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class Mission extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("libelle", TextType::class, ["label" => "Libellé de la mission","required" => true, "attr" => ["class" => "toUppercase"]])
            ->add("description", TextareaType::class, ["label" => "Description de la mission","required" => true, "attr" => ['style' => 'height: 100px',"class" => ""]])
            ->add("adresse", TextType::class, ["label" => "Adresse de la mission","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("cp", TextType::class, ["label" => "Code postal","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("commune", TextType::class, ["label" => "Commune","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("duree", TextType::class, ["label" => "Durée (heures, jours, fréquence)","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("nombreParticipants", IntegerType::class, ["label" => "Nombre de participants","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("dateDebut",null, [
                "label" => "Date de début",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,

                'attr' => ['class' => 'input-date']
            ])
            ->add("dateFin",null, [
                "label" => "Date de fin",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,

                'attr' => ['class' => 'input-date']
            ])
            ->add("dureeGlobaleEstimee",TextType::class, ["label" => "Durée globale estimée / récurrence du besoin","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("commentaire", TextareaType::class, ["label" => "Commentaires",'required' => false, "attr" => ['style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("equipementsFournisEntreprise",  TextareaType::class, ["label" => "Fournis par l’entreprise",'required' => false, "attr" => [	'style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("equipementsFournisAssociation",  TextareaType::class, ["label" => "Fournis par l’Association Intermédiaire",'required' => false, "attr" => ['style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("tenueTravailExigee",  TextareaType::class, ["label" => "Tenue de travail exigée",'required' => false, "attr" => [	'style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("pointsVigilance",  TextareaType::class, ["label" => "Contraintes / difficultés de l’activité",'required' => false, "attr" => [	'style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])

            ->add("fomationRDV",null, [
                "label" => "Rendez-vous d’intégration",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,

                'attr' => ['class' => 'input-date','disabled'=>'disabled', 'required' => false]
            ])
            ->add("formationCommentaire",  TextareaType::class, ["label" => "Commentaires",'required' => false, "attr" => [	'style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("infosComplementaires",  TextareaType::class, ["label" => "",'required' => false, "attr" => [	'style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])
            ->add("relationsInternes",  TextType::class, ["label" => "Relations internes",'required' => false, "attr" => ["class" => "toUppercase", 'required' => false]])
            ->add("relationsExternes",  TextType::class, ["label" => "Relations externes",'required' => false, "attr" => ["class" => "toUppercase", 'required' => false]])


            ->add('facturationCivilite', ChoiceType::class, [
                'label' => 'Civilité',
                'expanded' => true,
                'required' => true,
                'choices' => [
                    'Monsieur' => \UfmcpBundle\Entity\Mission::CIVILITE_M,
                    'Madame' => \UfmcpBundle\Entity\Mission::CIVILITE_MME,
                ]
            ])
            ->add("facturationNom",  TextType::class, ["label" => "Nom", "attr" => ["class" => "toUppercase"]])
            ->add("facturationPrenom",  TextType::class, ["label" => "Prénom", "attr" => ["class" => "toUppercase"]])
            ->add("facturationFonction",  TextType::class, ["label" => "Fonction", "attr" => ["class" => "toUppercase"]])
            ->add("facturationAdresse",  TextType::class, ["label" => "Adresse","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationCp",  TextType::class, ["label" => "Code postal","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationCommune",  TextType::class, ["label" => "Commune","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationTelFixe",  TextType::class, ["label" => "Téléphone fixe","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationTelPortable",  TextType::class, ["label" => "Téléphone portable","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationEmail",  TextType::class, ["label" => "E-mail","required" => false, "attr" => ["class" => "toUppercase"]])
            ->add("facturationCommentaire",  TextareaType::class, ["label" => "Commentaire",'required' => false, "attr" => ['style' => 'height: 100px',"class" => "toUppercase", 'required' => false]])



            ->add('entreprise',CollectionType::class, [
                'entry_type'=>Entreprise::class,
                'allow_add'=>true,
                'allow_delete'=>true,
                'label'=>false
            ])
            ->add('serviceRattachement',CollectionType::class,[
                'entry_type'=>ServiceRattachement::class,
                'allow_add'=>true,
                'allow_delete'=>true,
                'label'=>false
            ])

        ;


        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder) {
                $data = $event->getData();
                $builderData = $builder->getData();

                $dataEntreprise =$data['entreprise'];
              //  $builderData->setEntreprise($dataEntreprise);

                $entreprisRepo = $this->em->getRepository('UfmcpBundle:Entreprise')->findOneBy ([
                    'nom' =>$dataEntreprise[0]['nom']
                ]);

                if(!empty($entreprisRepo)){

                    $entrepris=$entreprisRepo;
                    $entrepris->setNom($dataEntreprise[0]['nom']);
                    $entrepris->setNumSiret($dataEntreprise[0]['numSiret']);
                    $entrepris->setStatutJuridique($dataEntreprise[0]['statutJuridique']);
                    $entrepris->setActivite($dataEntreprise[0]['activite']);
                    $no=$dataEntreprise[0]['nombreSalaries'];
                    $entrepris->setNombreSalaries($no ? $no : null);
                    $entrepris->setConventionCollective($dataEntreprise[0]['conventionCollective']);
                    $entrepris->setDateDebut(new  \DateTime());
                    $entrepris->setDateFin(new  \DateTime());

                    $entrepris->setFacturationNom($data['facturationNom']);
                    $entrepris->setFacturationPrenom($data['facturationPrenom']);
                    $entrepris->setFacturationTelFixe($data['facturationTelFixe']);
                    $entrepris->setFacturationTelPortable($data['facturationTelPortable']);
                    $entrepris->setFacturationEmail($data['facturationEmail']);
                    $entrepris->setFacturationAdresse($data['facturationAdresse']);
                    $entrepris->setFacturationCp($data['facturationCp']);
                    $entrepris->setFacturationCommune($data['facturationCommune']);
                    $entrepris->setFacturationFonction($data['facturationFonction']);
                    $entrepris->setFacturationCivilite($data['facturationCivilite']);
                    $entrepris->setFacturationCommentaire($data['facturationCommentaire']);


                }
                else{
                    $entrepris=new \UfmcpBundle\Entity\Entreprise();
                    $entrepris->setNom($dataEntreprise[0]['nom']);
                    $entrepris->setNumSiret($dataEntreprise[0]['numSiret']);
                    $entrepris->setStatutJuridique($dataEntreprise[0]['statutJuridique']);
                    $entrepris->setActivite($dataEntreprise[0]['activite']);
                    $entrepris->setNombreSalaries($dataEntreprise[0]['nombreSalaries']);
                    $entrepris->setConventionCollective($dataEntreprise[0]['conventionCollective']);
                    $entrepris->setDateDebut(new  \DateTime());
                    $entrepris->setDateFin(new  \DateTime());

                    $entrepris->setFacturationNom($data['facturationNom']);
                    $entrepris->setFacturationPrenom($data['facturationPrenom']);
                    $entrepris->setFacturationTelFixe($data['facturationTelFixe']);
                    $entrepris->setFacturationTelPortable($data['facturationTelPortable']);
                    $entrepris->setFacturationEmail($data['facturationEmail']);
                    $entrepris->setFacturationAdresse($data['facturationAdresse']);
                    $entrepris->setFacturationCp($data['facturationCp']);
                    $entrepris->setFacturationCommune($data['facturationCommune']);
                    $entrepris->setFacturationFonction($data['facturationFonction']);
                    $entrepris->setFacturationCivilite($data['facturationCivilite']);
                    $entrepris->setFacturationCommentaire($data['facturationCommentaire']);
                    $this->em->persist($entrepris);

                }
                if(isset($entrepris)){
                    $data['entreprise']=$entrepris?? null;
                }

               $builderData->setEntreprise( $entrepris);
                $event->setData($data);

                $this->em->flush();

            }
        );
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA ,
            function (FormEvent $event) use ($builder) {
                $mission = $event->getData();
                $form = $event->getForm();
               $mission->setEntreprise([$mission->getEntreprise()]);

            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'            => 'UfmcpBundle\Entity\Mission',
            'allow_extra_fields' => true,
        ));
    }

    public function getName()
    {
        return 'ufmcp_bundle_mission';
    }
}
