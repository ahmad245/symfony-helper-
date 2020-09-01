// assign            
            $this->view->assign('mission',$result['mission']);
            $this->view->assign('logo',$result['logo']);
            $this->view->assign('isInteresse',$result['isInteresse']);



//forward
            $this->forward("mission-api","menu",null,["mission"=>$this->getParam('mission')]);

//param 
$this->getParam('mission') 

// href path
<a   href="{$view->actionUrl( 'controller ( ex :menu)',' action (ex : add-interesse-api)', param :$mission.id,paramName :'mission')}" class="right-align current_week_btn">Je veux y aller</a>