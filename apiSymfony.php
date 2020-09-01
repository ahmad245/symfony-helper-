/**
     * @Rest\View()
     * @Rest\Get("/api/v1/generer/mission/{id}")
     * @param Request $request
     * @return Response
*/


$session = new Session();
$session->set('api-id_territoire', 1);