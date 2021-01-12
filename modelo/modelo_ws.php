<?php 
	require 'conexion.php';

	date_default_timezone_set("America/Mexico_city");
	$fecha=date("Y-m-d",time());


	function getAllMateriales()
	{
		global $conexion;
		$consulta=$conexion->query("select * from materiales");
		$datos=$consulta->fetchAll(PDO::FETCH_ASSOC);
		return $datos;		
	}

	function getMaterialByCodigo($codigo)
	{
		global $conexion;
		$consulta=$conexion->query("select * from materiales where codigo='$codigo'");
		$datos=$consulta->fetchAll(PDO::FETCH_ASSOC);

		if (sizeof($datos)>0)
		{
			return ['status'=>'1','registro'=>$datos[0]];
		}
		else
		{
			return ['status'=>'0','mensaje'=>'no existe'];
		}
	
		return $datos;		
	}

	function getMaterialByDescripcion($descripcion)
	{
		global $conexion;
		$descripcion=urldecode($descripcion);
		$consulta=$conexion->query("select * from materiales where descripcion='$descripcion'");
		$datos=$consulta->fetchAll(PDO::FETCH_ASSOC);

		if (sizeof($datos)>0)
		{
			return ['status'=>'1','registro'=>$datos[0]];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"$descripcion"];
		}
	
		return $datos;			
	}

	function setDescontarMaterialDeInventario($codigo,$cantidad,$id_vale,$id_usuario)
	{
		global $conexion;
		global $fecha;
		$hora=date("H:i:s",time());

		$existencia_inicial=$conexion->query("select existencia from materiales where codigo='$codigo'")->fetchAll()[0][0];		
		$existencia_final=$existencia_inicial-$cantidad;
		$conexion->query("insert into movimientos values('0','$fecha','$hora','$id_usuario','VALE','$id_vale','$codigo','0','$cantidad','$existencia_inicial','$existencia_final','DESCTO VALE $id_vale')");
		$conexion->query("update materiales set existencia='$existencia_final' where codigo='$codigo'");		
	}


	//////// USUARIOS //////////////

	function getLogin($user,$pass)
	{
		global $conexion;
		$consulta=$conexion->query("select*from usuarios where nombre_usuario='$user' and password_usuario='$pass'");
		$datos=$consulta->fetchAll(PDO::FETCH_ASSOC);


		if ($datos)
		{
			$respuesta=array(["status"=>1,"mensaje"=>"existe"]);
			return $respuesta;
		}
		else
		{
			$respuesta=array(["status"=>0,"mensaje"=>"Usuario o Password Equivocado"]);
			return $respuesta;
		}

	}


	///////////////EMPLEADOS/////////////

	function getEmpleadosListaSimple()
	{
		global $conexion;
		$consulta=$conexion->query("select ID_EMPLEADO,CONCAT(NOMBRE_EMPLEADO,' ',AP_PATERNO_EMPLEADO,' ',IFNULL(AP_MATERNO_EMPLEADO,'-')) as NOMBRE_COMPLETO from empleados where status='ACTIVO'");
		$datos=$consulta->fetchAll(PDO::FETCH_ASSOC);


		return $datos;	
	}


	//////////VALES/////////////////

	function setNuevoVale($id_empleado,$user)
	{
		global $conexion;
		global $fecha;
		$hora=date("H:i:s",time());

		$id_usuario=$conexion->query("select ID_USUARIO from usuarios where NOMBRE_USUARIO='$user'")->fetchAll()[0][0];

		$conexion->query("insert into vales values('0','$fecha','$hora','$id_usuario','$id_empleado','PENDIENTE','')");

		$id_vale=$conexion->query("select LAST_INSERT_ID()")->fetchAll()[0][0];

		return "$id_vale";

	}

	function setDescontarValeDeInventario($id_vale,$usuario)
	{
		global $conexion;

		$id_usuario=$conexion->query("select id_usuario from usuarios where nombre_usuario='$usuario'")->fetchAll()[0][0];
		$registros=$conexion->query("select*from vales_materiales where id_vale='$id_vale'")->fetchAll(PDO::FETCH_ASSOC);
		$statusVale=$conexion->query("select status_vale from vales where id_vale='$id_vale'")->fetchAll()[0][0];

		if ($statusVale=='PENDIENTE')
		{
			if (sizeof($registros)>0)
			{
				foreach ($registros as $registro)
				{		
					setDescontarMaterialDeInventario($registro['CODIGO'],$registro['CANTIDAD'],$registro['ID_VALE'],$id_usuario);
				}

				$conexion->query("update vales set status_vale='SURTIDO' where id_vale='$id_vale'");

				return ["status"=>'1',"mensaje"=>"Se desconto vale de inventario"];
			}
			else
			{
				return ["status"=>"0","mensaje"=>"No existen registros para este vale"];
			}			
		}
		else
		{
			return ["status"=>"0","mensaje"=>"El vale tiene status $statusVale no se puede continuar"];
		}


	}


	function setCancelarVale($id_vale,$usuario)
	{
		global $conexion;
		global $fecha;
		$hora=date('H:i:s',time());

		$status_vale=$conexion->query("select status_vale from vales where id_vale='$id_vale'")->fetchAll()[0][0];

		switch ($status_vale)
		{
			case 'PENDIENTE':
				//UNICAMENTE SE MARCA COMO CANCELADO EL VALE EN LA TABLA VALES
				$conexion->query("update vales set status_vale='CANCELADO', MOTIVO_CANCELACION='CANCELADO $fecha' where id_vale='$id_vale'");
				return ['status'=>'1','mensaje'=>'VALE CANCELADO'];
				break;
			case 'SURTIDO':
				//SE MARCA COMO CANCELADO EL VALE EN LA TABLA VALES Y SE REGRESA MATERIAL EN TABLA MOVIMIENTOS Y EN TABLA MATERIALES SE ACTUALIZA EXISTENCIA
				$id_usuario=$conexion->query("select id_usuario from usuarios where nombre_usuario='$usuario'")->fetchAll()[0][0];				
				$materiales=$conexion->query("select*from vales_materiales where id_vale='$id_vale'")->fetchAll(PDO::FETCH_ASSOC);

		
				foreach ($materiales as $material)
				{
					//devolvemos al inventario
					$codigo=$material['CODIGO'];
					$existencia_inicial=$conexion->query("select existencia from materiales where codigo='$codigo'")->fetchAll()[0][0];
					$cantidad=$material['CANTIDAD'];
					$existencia_final=$existencia_inicial+$cantidad;

					$conexion->query("insert into movimientos values('0','$fecha','$hora','$id_usuario','VALE','$id_vale','$codigo','$cantidad','0','$existencia_inicial','$existencia_final','DEVOLUCION POR CANCELACION')");
					$conexion->query("update materiales set existencia='$existencia_final' where codigo='$codigo'");				
				}

				$conexion->query("update vales set status_vale='CANCELADO' where id_vale='$id_vale'");
				
				return ['status'=>'1','mensaje'=>'VALE CANCELADO, SE DEVOLVIO MATERIAL AL INVENTARIO'];		
				break;


			case 'CANCELADO':
				//SE ADVIERTE QUE NO SE PUEDE CANCELAR POR QUE YA ESTA CANCELADO
				return ['status'=>'0','mensaje'=>'ERROR! ESTE VALE YA ESTA CANCELADO'];
				break;		

		}

	}

	function getValesDia()
	{
		global $conexion;
		global $fecha;

		$listaVales=$conexion->query("select vales.ID_VALE,vales.FECHA,vales.HORA,usuarios.NOMBRE_USUARIO,concat(empleados.NOMBRE_EMPLEADO,' ',empleados.AP_PATERNO_EMPLEADO,' ',IFNULL(empleados.AP_MATERNO_EMPLEADO,'-')) as NOMBRE_EMPLEADO,vales.STATUS_VALE from vales inner join usuarios on vales.ID_USUARIO=usuarios.ID_USUARIO inner join empleados on vales.ID_EMPLEADO=empleados.ID_EMPLEADO where vales.fecha='$fecha' order by vales.ID_VALE asc")->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($listaVales)>0)
		{
			return ['status'=>'1','registros'=>$listaVales];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"No existen registros"];
		}
	}


	function getValesMes()
	{
		global $conexion;
		$mes=date('m',time());

		$listaVales=$conexion->query("select vales.ID_VALE,vales.FECHA,vales.HORA,usuarios.NOMBRE_USUARIO,concat(empleados.NOMBRE_EMPLEADO,' ',empleados.AP_PATERNO_EMPLEADO,' ',IFNULL(empleados.AP_MATERNO_EMPLEADO,'-')) as NOMBRE_EMPLEADO,vales.STATUS_VALE from vales inner join usuarios on vales.ID_USUARIO=usuarios.ID_USUARIO inner join empleados on vales.ID_EMPLEADO=empleados.ID_EMPLEADO where MONTH(vales.fecha)='$mes' order by vales.ID_VALE asc")->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($listaVales)>0)
		{
			return ['status'=>'1','registros'=>$listaVales];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"No existen registros"];
		}
	}	

	function getValesRango($fecha_de,$fecha_a)
	{
		global $conexion;
		$mes=date('m',time());

		$listaVales=$conexion->query("select vales.ID_VALE,vales.FECHA,vales.HORA,usuarios.NOMBRE_USUARIO,concat(empleados.NOMBRE_EMPLEADO,' ',empleados.AP_PATERNO_EMPLEADO,' ',IFNULL(empleados.AP_MATERNO_EMPLEADO,'-')) as NOMBRE_EMPLEADO,vales.STATUS_VALE from vales inner join usuarios on vales.ID_USUARIO=usuarios.ID_USUARIO inner join empleados on vales.ID_EMPLEADO=empleados.ID_EMPLEADO where vales.fecha>='$fecha_de' and vales.fecha<='$fecha_a' order by vales.ID_VALE asc")->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($listaVales)>0)
		{
			return ['status'=>'1','registros'=>$listaVales];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"No existen registros"];
		}
	}

	function getValesID($id)
	{
		global $conexion;	

		$listaVales=$conexion->query("select vales.ID_VALE,vales.FECHA,vales.HORA,usuarios.NOMBRE_USUARIO,concat(empleados.NOMBRE_EMPLEADO,' ',empleados.AP_PATERNO_EMPLEADO,' ',IFNULL(empleados.AP_MATERNO_EMPLEADO,'-')) as NOMBRE_EMPLEADO,vales.STATUS_VALE from vales inner join usuarios on vales.ID_USUARIO=usuarios.ID_USUARIO inner join empleados on vales.ID_EMPLEADO=empleados.ID_EMPLEADO where vales.ID_VALE='$id'  order by vales.ID_VALE asc")->fetchAll(PDO::FETCH_ASSOC);
		if (sizeof($listaVales)>0)
		{
			return ['status'=>'1','registros'=>$listaVales];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"No existen registros"];
		}
	}

	function getValesDetalle($id)
	{
		global $conexion;		

		$info_vale=$conexion->query("select vales.ID_VALE,vales.FECHA,vales.HORA,usuarios.NOMBRE_USUARIO,CONCAT(empleados.NOMBRE_EMPLEADO,' ',empleados.AP_PATERNO_EMPLEADO,' ',IFNULL(empleados.AP_MATERNO_EMPLEADO,'-')) as NOMBRE_EMPLEADO,vales.STATUS_VALE from vales inner join usuarios on vales.ID_USUARIO=usuarios.ID_USUARIO inner join empleados on vales.ID_EMPLEADO=empleados.ID_EMPLEADO where vales.ID_VALE='$id'")->fetchAll(PDO::FETCH_ASSOC);		

		if (sizeof($info_vale)>0)
		{
			$detalle_vale=$conexion->query("select vales_materiales.ID_MOVIMIENTO,vales_materiales.ID_VALE,vales_materiales.CODIGO,materiales.DESCRIPCION,vales_materiales.CANTIDAD,materiales.UNIDAD_DE_MEDIDA from vales_materiales inner join materiales on vales_materiales.CODIGO=materiales.CODIGO where vales_materiales.ID_VALE='$id'")->fetchAll(PDO::FETCH_ASSOC);

			return ['status'=>'1','info_vale'=>$info_vale[0],'detalle_vale'=>$detalle_vale];
		}
		else
		{
			return ['status'=>'0','mensaje'=>"No existe vale"];
		}
	}

	function getValesActualizarEmpleado($id_vale,$empleado)
	{
		global $conexion;
		$id_empleado=$conexion->query("select id_empleado from empleados where concat(NOMBRE_EMPLEADO,' ',AP_PATERNO_EMPLEADO,' ',IFNULL(AP_MATERNO_EMPLEADO,'-'))='$empleado'")->fetchAll()[0][0];

		$conexion->query("update vales set id_empleado='$id_empleado' where id_vale='$id_vale'");
		return ['status'=>'1','mensaje'=>'REGISTRO ACTUALIZADO'];
	}			
	//////////// VALES_MATERIALES ////////////////////////

	function setInsertarItem($id_vale,$codigo,$cantidad)
	{
		global $conexion;

		$conexion->query("insert into vales_materiales values('0','$id_vale','$codigo','$cantidad')");
		return ['status'=>'1','mensaje'=>'registro insertado','sentencia'=>"insert into vales_materiales values('0','$id_vale','$codigo','$cantidad')"];		
	}

	function getDetalleVale($id_vale)
	{
		global $conexion;
		$registros=$conexion->query("select vales_materiales.CODIGO,materiales.DESCRIPCION,vales_materiales.CANTIDAD,materiales.UNIDAD_DE_MEDIDA from vales_materiales inner join materiales on vales_materiales.CODIGO=materiales.CODIGO where vales_materiales.ID_VALE='$id_vale'")->fetchAll(PDO::FETCH_ASSOC);

		if (sizeof($registros)>0)
		{
			return ['status'=>'1','registros'=>$registros];
		}
		else
		{
			return ['status'=>'0','mensaje'=>'no hay registros'];
		}


	}	

	function getEliminarItem($id_item)
	{
		global $conexion;
		$conexion->query("delete from vales_materiales where ID_MOVIMIENTO='$id_item'");

		return ['status'=>'1','mensaje'=>'Item Eliminado'];
	}


 ?>