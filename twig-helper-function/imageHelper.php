<?php
/**
 * Created by PhpStorm.
 * User: a.almasri
 * Date: 17/08/2020
 * Time: 16:12
 */

namespace UfmcpBundle\Service;


use UfmcpBundle\Entity\Mission;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageHelper
{
    private $container;

    public function __construct(ContainerInterface  $container)
    {
        $this->container = $container;
    }

    public function getUrl(Mission $mission)
    {

        $upload_dir =$this->container->getParameter('upload_directory');
        $path = $upload_dir.'/mission/'.$mission->getEntreprise()->getId();


        // récupération du logo
        $logo_path = $path.'/entrepriselogo.png';
        if(is_file($logo_path)) {
            // dump($logo_path);die;
            $stream = fopen($logo_path, 'rb');
            $logo = base64_encode(stream_get_contents($stream));
            fclose($stream);
            return $logo;
        }
        else{
            return '';
        }



    }
}