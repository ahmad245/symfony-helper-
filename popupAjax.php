<td class="text_center ">
    <a class="ajax fancybox fancybox.ajax" href="{{ path('mission_candidats',{'id':mission.id}) }}" style="width: 5%; cursor: help; padding-left: 4px; vertical-align: middle;" title="{{ mission.interessePar|length }}">
        <span class="icon-group" style="font-size: 12px;"></span>{{ mission.interessePar|length }}
    </a>
</td>

/**
     * @Route("/mission/candidats/{id}", name="mission_candidats")
     * @param Request $request
     * @return Response
     */
    public function candidats(Mission $mission){
        $em = $this->getDoctrine()->getManager();

        $template = 'mission/candidatsMission.html.twig';
        return $this->render($template,[
            'mission'=>$mission
        ]);
    }





    <script>
         $(".fancybox").fancybox({
            maxWidth	: 800,
            maxHeight	: 600,
            fitToView	: false,
            width		: '70%',
            height		: '70%',
            autoSize	: false,
            closeClick	: false,
            openEffect	: 'none',
            closeEffect	: 'none',
            closeBtn		: true,
            scrolling: 'auto',
        });
    </script>