<?php
	session_start();

	switch(isset($_GET["op"])? $_GET["op"] : ""){
		case "check": checkChanges(); break;
		default: saveChanges();
	}
	
	function saveChanges(){
		$sheet = $_GET["sheet"];
		$row = $_GET["row"];
		$column = $_GET["column"];
		$data = $_GET["data"];
		
		$add = -1;
		$index = 0;
		
		$changes = $_SESSION["changes"];
		
		foreach($changes as $change){
			
			if($change["sheet"] == $sheet && $change["row"] == $row && $change["column"] == $column){
				$add = $index;
				break;
			}
			$index++;
		}
		if($add == -1)
			$_SESSION["changes"][] = ["sheet" => $sheet, "row" => $row, "column" => $column, "data" => $data];
		else
			$_SESSION["changes"][$add]["data"] = $data;
		
	}
	
	function checkChanges(){
		$sheets = $_SESSION["sheets"];
		
		$changes = $_SESSION["changes"];
		
		$table = $_SESSION["table"];
		
		if(count($table) != count($sheets)){
			echo "1";
			return;
		}
		
		$index = 0;
		foreach($table as $sheet)
			if($sheet["row"] != $sheets[$index]["row"] || $sheet["column"] != $sheets[$index++]["column"]){
				echo "1";
				return;
			}
		
		foreach($changes as $change){
			$s = $change["sheet"];
			$r = $change["row"];
			$c = $change["column"];
			if($sheets[$s][$r][$c] != $change["data"]){
				echo "1";
				return;
			}
		}
		
		echo "0";
	}
?>