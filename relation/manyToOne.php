
// mission and entreprise : entreprise have many mission 
// inside mision 
//inversedBy missions 
/**
    * @var \UfmcpBundle\Entity\Entreprise
     *
     * @ORM\ManyToOne(targetEntity="UfmcpBundle\Entity\Entreprise", inversedBy="missions",cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_entreprise", referencedColumnName="id")
     * })
     */
    private $entreprise;


    /**
     * @return Entreprise
     */
    public function getEntreprise()
    {

        return $this->entreprise;
    }

    /**
     * @param Entreprise $entreprise
     */
    public function setEntreprise( $entreprise): void
    {
        $this->entreprise = $entreprise;
    }