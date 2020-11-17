<?
// inside UserBundle
namespace App\UserBundle\Entity;

use App\OtherBundle\Entity\UserInterface as Other1UserInterface;
use App\OtherBundle2\Entity\UserInterface as Other2UserInterface;

class UserEntity implements Other1UserInterface,Other2UserInterface{

    //......
}

////////////////////////////////////////////////////////////////

// inside OtherBundle1 
namespace App\OtherBundle1\Entity;
interface UserInterface{

}

// inside OtherBundle1 
namespace App\OtherBundle1\Entity;

class OtherEntity{
   /**
     * @ORM\ManyToOne(targetEntity="UserInterface")
     * @Assert\NotNull
     */
    protected $User;

    // add other fields as required
} 

////////////////////////////////////////////////////////////////


# app/config/config.yml
doctrine:
    # ...
    orm:
        # ...
        resolve_target_entities:
        App\OtherBundle1\Entity\UserInterface: App\UserBundle\Entity\User
        App\OtherBundle2\Entity\UserInterface: App\UserBundle\Entity\User