public  function  dateFromString($date){
        $date=str_replace('/','-',$date);
        $dateTime=strtotime($date);

        $dateFormate =date("Y/m/d",$dateTime);
        $result=new DateTime($dateFormate);
       // dump($dateTime,$dateFormate,$result,$date);die;
        return $result;
    }