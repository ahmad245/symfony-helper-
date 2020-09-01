<button  onclick="simplePopup.openWindow('{$view->actionUrl('menu','mission-pdf-api',$mission.id,'mission')}', 'ratio:0.7', '100%', true); return false;" class="current_week_btn " >
            Télécharger la fiche

        </button>

        <a onclick="simplePopup.openWindow('{{ path('mission_generer', {"id": mission.id}) }}', 'ratio:0.7', '100%', true); return false;" class="btn_action btn_grey btn_download_documents" >
					Télécharger la fiche

                </a>     
                


<div id="example1"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfobject/2.1.1/pdfobject.min.js" integrity="sha512-4ze/a9/4jqu+tX9dfOqJYSvyYd5M6qum/3HpCLr+/Jqf0whc37VUbkpNGHR7/8pSnCFw47T1fmIpwBV7UySh3g==" crossorigin="anonymous"></script>
<script>
         PDFObject.embed('{/literal} data:application/pdf;base64,{$pdf}{literal}');
</script>



// send pdf 

    // TCPDF 
    public function showApi( $fileName = '',\UfmcpBundle\Entity\Mission $mission)
    {
        $this->missionObject=$mission;
        $this->setPrintHeader(true);
        $this->missionObject=$mission;
        $this->Content($mission);
        /**
         * Output
         */
        $this->Output($upload_dir = $this->container->getParameter('upload_directory').'/'.$mission->getId().'.pdf','F');
    }


    // using php controller 

    try{
                $pdf = $this->get('ufmcp.pdf.mission');
                $pdf->showApi('facture.pdf',$mission);
                $upload_dir = $this->getParameter('upload_directory').'/'.$mission->getId().'.pdf';
                $stream = fopen($upload_dir, 'rb');
                $logo = base64_encode(stream_get_contents($stream));
                fclose($stream);
            // dump($logo);die;
                return $this->json($logo);
            }catch (\Exception $ex ){
                dump($ex);die;
            }


            return new Response();



// inside service.yml

ufmcp.pdf.mission:
        class: UfmcpBundle\PDF\Mission
        arguments: [ "@service_container", "@doctrine.orm.entity_manager" ]
        shared: false            