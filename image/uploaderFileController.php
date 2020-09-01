<?php

namespace UfmcpBundle\Controller;

use UfmcpBundle\Service\FileUploader;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class UploadFileController extends Controller
{
    private $correspondances = [
        'lieu_formation' => [
            'name' => 'plan_acces',
            'ext' => 'png',
            'mime' => 'image',
            'dir_dest' => 'lieu_formation',
        ],
        'territoire_header' => [
            'name' => 'pdf_header',
            'ext' => 'png',
            'mime' => 'image',
            'dir_dest' => 'medias',
        ],
        'territoire_footer' => [
            'name' => 'pdf_footer',
            'ext' => 'png',
            'mime' => 'image',
            'dir_dest' => 'medias',
        ],
        'territoire_tampon' => [
            'name' => 'pdf_tampon',
            'ext' => 'png',
            'mime' => 'image',
            'dir_dest' => 'medias',
        ],
        'organisme_rib' => [
            'name' => 'rib',
            'ext' => '*',
            'mime' => '*',
            'dir_dest' => '.',
        ],
        'organisme_logo' => [
            'name' => 'logo',
            'ext' => '*',
            'mime' => '*',
            'dir_dest' => '.',
        ],
        'entreprise_logo' => [
            'name' => 'entrepriselogo',
            'ext' => '*',
            'mime' => '*',
            'dir_dest' => '.',
        ],
        'frais_deplacement_rib' => [
            'name' => 'rib',
            'ext' => '*',
            'mime' => '*',
            'dir_dest' => 'fraisdepl_documents',
        ],
    ];

    /**
    * @Route("/uploadTempFile/{type}",
    *       name="upload_temp_file",
    *       options={"expose": true}
    * )
    * @param Request $request
    * @param string $type
    * @return Response
     */
    public function uploadTempFileAction(Request $request, $type = '') {
        if(array_key_exists($type, $this->correspondances)) {
            $token = $request->request->get('token');


            if(!empty($token)) {
                $base_dir = $this->getParameter('upload_directory');
    
                if($type == 'organisme_rib' || $type == 'organisme_logo') {
                    $id = $this->getUser()->getOrganisme()->getId();
                    $type_dir = 'organisme';
                } else if($type == 'frais_deplacement_rib') {
                    $id = $request->request->get('id_stagiaire');
                    if(empty($id)) {
                        return $this->json([
                            'success' => false,
                            'message' => "Une erreur est survenue lors du chargement du fichier"
                        ]);
                    }
                    $type_dir = 'stagiaire';
                }else if($type == 'entreprise_logo') {
                    $session = new Session();
                    $id = $session->get('id_territoire');
                    if(empty($id)) {
                        return $this->json([
                            'success' => false,
                            'message' => "Une erreur est survenue lors du chargement du fichier"
                        ]);
                    }
                    $type_dir = 'mission';
                }

                else {
                    $session = new Session();
                    $id = $session->get('id_territoire');
                    $type_dir = 'territoire';
                }
                $dir_dest = $this->correspondances[$type]['dir_dest'];
                $path = $base_dir . '/' . $type_dir . '/' . $id . '/' . $dir_dest . '/tmp/' . $token;

                $uploader = new FileUploader($path);
                $file = $request->files->get('file');
                if($this->correspondances[$type]['mime'] != '*'
                && strpos($file->getMimeType(), $this->correspondances[$type]['mime']) === false) {
                    return $this->json([
                        'success' => false,
                        'message' => "Le fichier n'est pas au bon format"
                    ]);
                }
                $returnname = $uploader->upload($file, $token);

                $filepath = $path . '/' . $returnname;
                $ext = $this->correspondances[$type]['ext'];
                if($ext == '*') {
                    $ext = $file->getClientOriginalExtension();
                }
                $real_name = $this->correspondances[$type]['name'] . '.' . $ext;

                // Uniquement pour les images => conversion au format PNG
                if($this->correspondances[$type]['mime'] == 'image') {
                    imagepng(imagecreatefromstring(file_get_contents($filepath)), $path . '/' . $real_name);
                    // redimensionnement pour un affichage correct dans les pdf générés
                    switch($type) {
                        case 'territoire_footer':
                            $this->get('ufmcp.image.resizer')->resizeTerritoireFooter($path . '/' . $real_name);
                            break;
                        case 'territoire_header':
                            $this->get('ufmcp.image.resizer')->resizeTerritoireHeader($path . '/' . $real_name);
                            break;
                        case 'territoire_tampon':
                            $this->get('ufmcp.image.resizer')->resizeTerritoireTampon($path . '/' . $real_name);
                            break;
                        case 'lieu_formation':
                            $this->get('ufmcp.image.resizer')->resizeTerritoirePlan($path . '/' . $real_name);
                            break;
                    }

                    unlink($filepath);
                } else {
                    $fs = new Filesystem();
                    if(is_file($path . '/' . $real_name)) {
                        unlink($path . '/' . $real_name);
                    }
                    $fs->rename($filepath, $path . '/' . $real_name);
                    $file = new File($path . '/' . $real_name);
                    $file->move($path);
                }
                $filepath = $path . '/' . $real_name;

                $response = new StreamedResponse(function () use ($filepath) {
                    $stream = fopen($filepath, 'rb');
                    echo base64_encode(stream_get_contents($stream));
                    fclose($stream);
                });
                $response->headers->set('Content-Type', 'application/octet-stream');
                return $response;
            } else {
                return $this->json([
                    'success' => false,
                    'message' => "Un problème est survenu dans le formulaire, le fichier n'a pas été téléchargé"
                ]);
            }
        }

        return $this->json([
            'success' => false,
            'message' => "Le type n'a pas été reconnu"
        ]);
    }

    /**
     * @Route("/deleteTempFile/{type}",
     *      name="delete_temp_file",
     *      options={"expose": true}
     * )
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteTempFileAction(Request $request, $type = '') {
        if(array_key_exists($type, $this->correspondances)) {
            $token = $request->request->get('token');
            if(!empty($token)) {
                if($type == 'organisme_rib' || $type == 'organisme_logo') {
                    $id_dir = $this->getUser()->getOrganisme()->getId();
                    $type_dir = 'organisme';
                } else if($type == 'frais_deplacement_rib') {
                    $id = $request->request->get('id_stagiaire');
                    if(empty($id)) {
                        return $this->json([
                            'success' => false,
                            'message' => "Une erreur est survenue lors de la suppression du fichier"
                        ]);
                    }
                    $type_dir = 'stagiaire';
                } else {
                    $session = new Session();
                    $id_dir = $session->get('id_territoire');
                    $type_dir = 'territoire';
                }
                $base_dir = $this->getParameter('upload_directory');
                $dir_dest = $this->correspondances[$type]['dir_dest'];
                $path = $base_dir.'/'.$type_dir.'/'.$id_dir.'/'.$dir_dest.'/tmp/'.$token;

                $ext = $this->correspondances[$type]['ext'];
                $real_name = $this->correspondances[$type]['name'] . '.' . $ext;
                if($type == 'organisme_rib') {
                    $finder = new Finder();
                    $finder->files()->in($path)->name('rib.*');
                    if($finder->count() == 1) {
                        foreach($finder as $f) {
                            $real_name = $f->getFilename();
                        }
                    }
                }

                if(is_file($path.'/'.$real_name)) {
                    unlink($path.'/'.$real_name);
                    $finder = new Finder();
                    $finder->in($path);
                    if(count($finder->files()) == 0) {
                        rmdir($path);
                    }
                    return $this->json([
                        'success' => true,
                        'message' => "Le fichier a bien été supprimé"
                    ]);
                }
                return $this->json([
                    'success' => true,
                    'message' => "Aucun fichier à supprimer"
                ]);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => "Un problème est survenu dans le formulaire, l'image n'a pas été supprimée"
                ]);
            }
        }

        return $this->json([
            'success' => false,
            'message' => "Le type n'a pas été reconnu"
        ]);
    }

    /**
     * @Route("/deleteFile/{type}/{id}",
     *      name="delete_file",
     *      options={"expose": true}
     * )
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteFileAction(Request $request, $type = '', $id = 0) {
        if(array_key_exists($type, $this->correspondances)) {
            if($type == 'organisme_rib' || $type == 'organisme_logo') {
                $id_dir = $this->getUser()->getOrganisme()->getId();
                $type_dir = 'organisme';
            } else if($type == 'frais_deplacement_rib') {
                $id = $request->request->get('id_stagiaire');
                if(empty($id)) {
                    return $this->json([
                        'success' => false,
                        'message' => "Une erreur est survenue lors de la suppression du fichier"
                    ]);
                }
                $type_dir = 'stagiaire';
            } else {
                $session = new Session();
                $id_dir = $session->get('id_territoire');
                $type_dir = 'territoire';
            }

            if ($id == $id_dir) {
                $base_dir = $this->getParameter('upload_directory');
                $dir_dest = $this->correspondances[$type]['dir_dest'];
                $path = $base_dir . '/'.$type_dir.'/'.$id_dir.'/'.$dir_dest;
                $ext = $this->correspondances[$type]['ext'];
                $real_name = $this->correspondances[$type]['name'] . '.' . $ext;
                if(in_array($type, ['organisme_rib', 'frais_deplacement_rib'])) {
                    $finder = new Finder();
                    $finder->files()->in($path)->name('rib.*');
                    if($finder->count() == 1) {
                        foreach($finder as $f) {
                            $real_name = $f->getFilename();
                        }
                    }
                }
                
                if (is_file($path . '/' . $real_name)) {
                    unlink($path . '/' . $real_name);
                    if(is_dir($path) && !in_array($type, ['organisme_rib', 'frais_deplacement_rib', 'organisme_logo'])) {
                        rmdir($path);
                    }
                    return $this->json([
                        'success' => true,
                        'message' => "Le fichier a bien été supprimé"
                    ]);
                }
                return $this->json([
                    'success' => true,
                    'message' => "Aucun fichier à supprimer"
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => "Vous n'avez pas les droits pour supprimer ce fichier"
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => "Le type n'a pas été reconnu"
        ]);
    }
}