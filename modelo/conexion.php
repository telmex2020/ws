<?php 
	
	//CONEXION MYSQL CON PDO

	require 'datos_conexion.php';

	try 
	{
		$conexion=new PDO("mysql:host=$host;dbname=$db;charset=utf8",$user,$pass);		
	}
	catch (PDOException $e)
	{
		print "Error! :". $e->getMessage()."<br>";
		die();
	}	

	

 ?>