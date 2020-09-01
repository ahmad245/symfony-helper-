// get images :  get directory , get image path and get image as stream
    // get directory 
    $upload_dir =$this->container->getParameter('upload_directory');

    // get image path
    $path = $upload_dir.'/mission/'.$mission->getEntreprise()->getId();

    // get image
    $logo_path = $path.'/entrepriselogo.png';
            if(is_file($logo_path)) {    
                $stream = fopen($logo_path, 'rb');
                $logo = base64_encode(stream_get_contents($stream));
                fclose($stream);
                return $logo;
            }
            else{
                return '';
    }

// stor image in server  : get directory path , specific the path where you want to stor image ,get image from temperary directory ,stor image in path

  //get directory path
  $upload_dir=$this->getParameter('upload_directory');
 
 //specific the path where you want to stor image
 $path=$upload_dir.'/mission/'.$mission->getEntreprise()->getId();

 //get image from temperary directory
 $token=$req->request->get('mission')['upload_token'];
 $id = $session->get('id_territoire');
 $pathM=$upload_dir.'/mission/'.$id;
 $temp_path = $pathM.'/tmp/'.$token;

 $uploader = new FileUploader($path);
 if(is_dir($temp_path)) {
    $fs = new Filesystem();
    $finder = new Finder();
    if ($fs->exists($temp_path.'/entrepriselogo.png')) {
                   // dump($temp_path);die;
                    $file = new File($temp_path.'/entrepriselogo.png');
               //     $file2 = new File($temp_path.'/focale.png');
                    // rename file
                    $file->move($path);
                 //   $file2->move($pathFocale);
                    $fs->remove($temp_path);
                }
 }

// temlete 
 <img class="responsive-img"  src="data:image/png;base64,{{image}}" id="fake_upload_logo" >




