<?php 

require ('modelo/modelo_ws.php');

function getServidores()
{

	echo json_encode(getAllServidores());
}

 ?>