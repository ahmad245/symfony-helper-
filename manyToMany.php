
    //user//////////////
    /**
     * @ORM\ManyToMany(targetEntity="UfmcpBundle\Entity\Mission" , inversedBy="interessePar")
     * @ORM\JoinTable(name="territoire_base.mission_interesse",
     *                joinColumns={@ORM\JoinColumn(name="id_stagiaire",referencedColumnName="id")},
     *                inverseJoinColumns={@ORM\JoinColumn(name="id_mission",referencedColumnName="id")}
     * )
     */
    private $missionInteresses;


// mission 
/**
     * @ORM\ManyToMany(targetEntity="UfmcpBundle\Entity\Stagiaire",mappedBy="missionInteresses", fetch="LAZY")

     *
 */
    private $interessePar;    