<?php 
	header('Content-Type:json');

	

	require __DIR__ . '/vendor/autoload.php';
	require 'modelo/modelo_ws.php';
	use Phroute\Phroute\RouteCollector;
	use Phroute\Phroute\Dispatcher;
	use Phroute\Phroute\Exception\HttpRouteNotFoundException;
	use Phroute\Phroute\Exception\HttpMethodNotAllowedException;

	$collector=new RouteCollector();

	$collector->get("/",function(){

		return json_encode(array("WEB SERVICE TELMEX COPE HUAUCHINANGO"));
	});


	/////////MATERIALES////////

	//LISTA DE TODOS LOS MATERIALES CON TODOS LOS CAMPOS
	$collector->get("/materiales",function(){
		return json_encode(getAllMateriales());		
	});	

	//MATERIAL X CODIGO
	$collector->get("/materiales/{codigo}",function($codigo){
		return json_encode(getMaterialByCodigo($codigo));
	});	

	//MATERIAL X DESCRIPCION
	$collector->post("/materialesxdescripcion",function(){

		//descripcion se envía via post

		return json_encode(getMaterialByDescripcion($_POST['descripcion']));
	});	



	////////// USUARIOS //////////////////////
	$collector->get("/login/{user}/{pass}",function($user,$pass){
		return json_encode(getLogin($user,$pass)); 
	});


	////////// EMPLEADOS /////////////////////

	$collector->get("/empleados/lista_simple",function(){
		return json_encode(getEmpleadosListaSimple()); 
	});


	///////////////VALES//////////////

	$collector->get("/vales/nuevo_vale/{id_empleado}/{user}",function($id_empleado,$user){
		return json_encode(setNuevoVale($id_empleado,$user)); 
	});

	$collector->get("/vales/descontar/{id_vale}/{usuario}",function($id_vale,$usuario){
		return json_encode(setDescontarValeDeInventario($id_vale,$usuario));
	});

	$collector->get("/vales/cancelar/{id_vale}/{usuario}",function($id_vale,$usuario){
		return json_encode(setCancelarVale($id_vale,$usuario));
	});


	$collector->get("/vales/consulta/dia",function(){
		return json_encode(getValesDia());
	});

	$collector->get("/vales/consulta/mes",function(){
		return json_encode(getValesMes());
	});

	$collector->get("/vales/consulta/rango/{fecha_de}/{fecha_a}",function($fecha_de,$fecha_a){
		return json_encode(getValesRango($fecha_de,$fecha_a));
	});	

	$collector->get("/vales/consulta/id/{id}",function($id){
		return json_encode(getValesID($id));
	});	


	$collector->get("/vales/consulta/detalle/{id}",function($id){
		return json_encode(getValesDetalle($id));
	});

	$collector->post("/vales/actualizar_empleado",function(){
		return json_encode(getValesActualizarEmpleado($_POST['id_vale'],$_POST['empleado']));
	});


	//////////VALES_MATERIALES
	$collector->post("/insertar_item",function(){

		//descripcion se envía via post

		return json_encode(setInsertarItem($_POST['id_vale'],$_POST['codigo'],$_POST['cantidad']));
	});	

	$collector->get("/detalle_vale/{id_vale}",function($id_vale){		

		return json_encode(getDetalleVale($id_vale));
	});

	$collector->get("/vales_materiales/eliminar_item/{id_item}",function($id_item){		

		return json_encode(getEliminarItem($id_item));
	});	







	$despachador = new Dispatcher($collector->getData());
	$rutaCompleta = $_SERVER["REQUEST_URI"];
	$metodo = $_SERVER['REQUEST_METHOD'];
	$rutaLimpia = processInput($rutaCompleta);	

	


	try 
	{
	    echo $despachador->dispatch($metodo, $rutaLimpia); # Mandar sólo el método y la ruta limpia
	} 
	catch (HttpRouteNotFoundException $e)
	{
	    echo "Error: Ruta no encontrada";
	}
	catch (HttpMethodNotAllowedException $e)
	{
	    echo "Error: Ruta encontrada pero método no permitido";
	}

	function processInput($uri)
	{		
	    return implode('/',array_slice(explode('/', $uri), 3));
	}	

 ?>