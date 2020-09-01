<td class="text_center desc">
                        <a href="#pop{{ mission.id }}" class="btn_remarques lien icon-visible various" style="vertical-align: sub" data-title="mission description"  ></a>
                             <div id="pop{{ mission.id }}" style="display: none">
                                 <h1>Description de la mission</h1>
                                 <p class="description">  {{ mission.description }} </p>
                             </div>
                    </td>
                   
                    

<script>
    
    $(".various").fancybox({
            maxWidth	: 800,
            fitToView	: false,
            width		: '70%',
            height      : 'auto',
            autoSize	: false,
            closeClick	: false,
            openEffect	: 'none',
            closeEffect	: 'none',
            scrolling: 'auto',
        });
</script>                    