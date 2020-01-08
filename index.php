<?php
	session_start();
	
	$op = (isset($_GET["op"]))? $_GET["op"] : "";
	
	switch($op){
		case "new": createNewFile($_GET["name"]); fileTable(); break;
		case "open": openFile($_GET["name"]); break;
		case "delete": deleteFile($_GET["name"]); fileTable(); break;
		case "save": saveFile($_GET["name"]); break;
		case "openSheet": prepareSheet($_SESSION["sheets"], $_GET["sheet"], $_SESSION["name"]); break;
		case "addSheet": prepareSheet(addSheet(), $_SESSION["no"], $_SESSION["name"]); break;
		case "delSheet": prepareSheet(delSheet($_GET["sheet"]), $_SESSION["no"], $_SESSION["name"]); break;
		case "addRow": prepareSheet(addRow(), $_SESSION["no"], $_SESSION["name"]); break;
		case "delRow": prepareSheet(delRow($_GET["row"]), $_SESSION["no"], $_SESSION["name"]); break;
		case "addCol": prepareSheet(addCol(), $_SESSION["no"], $_SESSION["name"]); break;
		case "delCol": prepareSheet(delCol($_GET["col"]), $_SESSION["no"], $_SESSION["name"]); break;
		case "sortRow": sortRow($_GET["row"], $_GET["asc"]); break;
		case "sortCol": sortCol($_GET["col"], $_GET["asc"]); break;
		default: fileTable();
	}
	
	function fileTable(){
		$filenames = array_slice(scandir("files"), 2);
		
		echo "<div style='margin: 100; display: inline-block; width: 30%'>
				<h3>Files</h3>
				<div class='list-group'>";
		foreach($filenames as $name)
			echo "<div class='row m-0'>
						<a href='?op=open&name=" . $name . "' class='col-9 list-group-item list-group-item-primary list-grup-item-action' style='text-align: left; white-space: nowrap'>$name</a>
						<a href='?op=delete&name=" . $name . "' class='col-3 list-group-item list-group-item-danger list-grup-item-action' style='text-align: center'>Delete</a>
					</div>";
		echo "<form method='GET'>
				<div class='row m-0'>
					<input type='text' name='name' class='col-6 list-group-item' style='text-align: left' />
					<button type='submit' name='op' value='new' class='col-3 list-group-item list-group-item-success list-grup-item-action'>New File</button>
				</div>
			  </form>
			 </div>
			</div>";
	}
	
	function createNewFile($name){
			
		$_SESSION["changes"] = [];
		$_SESSION["sheets"] = [["row" => 1, "column" => 1, [""]]];
		$_SESSION["name"] = $name;
		
		header("Location: ?op=openSheet&sheet=0");
		
	}
	
	function openFile($name){
		$sheet = [];
		$sheets = [];
		$table = [];
		
		$_SESSION["changes"] = [];
		
		if(($fo = fopen("files/$name", "r")) !== false){
			$data = fgetcsv($fo, 1000, ",");
			$rowCount = $data[1];
			$sheet["row"] = $data[1];
			$sheet["column"] = $data[2];
			$table[] = $sheet;
			
			while(($data = fgetcsv($fo, 1000, ",")) !== false){
				if($rowCount != 0){
					$sheet[] = $data;
					$rowCount--;
				}
				else{
					$rowCount = $data[1];
					$sheets[] = $sheet;
					$sheet = [];
					$sheet["row"] = $data[1];
					$sheet["column"] = $data[2];
					$table[] = $sheet;
				}
				
			}
			$sheets[] = $sheet;
			fclose($fo);
		}
		$_SESSION["table"] = $table;
		prepareSheet($sheets, 0, substr($name, 0, strlen($name) - 4));
	}
	
	function prepareSheet($sheets, $no, $name){
		
		$_SESSION["no"] = $no;
		$_SESSION["name"] = $name;
				
		$_SESSION["sheets"] = $sheets;
		
		$changes = $_SESSION["changes"];
		
		foreach($changes as $change){
			$s = $change["sheet"];
			$r = $change["row"];
			$c = $change["column"];
			$sheets[$s][$r][$c] = $change["data"];
		}
		
		
		if($no == count($sheets))
			$_SESSION["no"] = $no = 0;
		
		$sheet = $sheets[$no];
		$column = ord('A') - 1;
		echo "<table style='text-align: center;'>
			    <tr>
				  <td><a href='#' class='link' onclick='checkBeforeBack()'>&lt; Back</a></td>
				  <td>
					<div>
					  " . $name . "<br>
					  <a href='#' class='btn btn-success' onclick='checkBeforeSave(\"$name\")'>Save</a>
					</div>
				  </td>";
				  for($i = 0; $i < $sheet["column"]; $i++)
					  echo "<td>
						<div>
						  <a href='?op=delCol&col=" . $i . "' class='btn btn-danger'>Delete Column</a><br>
						  <a href='?op=sortCol&col=" . $i . "&asc=" . ((isset($_GET["asc"]))? !$_GET["asc"] : true) . "' class='link'>" . chr(++$column) . "</a>
						</div>
					  </td>";
				  echo "<td><a href='?op=addCol' class='btn btn-primary'>Add Column</a></td>
				</tr>";
				for($i = 0; $i < $sheet["row"]; $i++){
					echo "<tr>
							  <td><a href='?op=delRow&row=" . $i . "' class='btn btn-danger'>Delete Row</a></td>
							  <td><a href='?op=sortRow&row=" . $i . "&asc=" . ((isset($_GET["asc"]))? !$_GET["asc"] : true) . "' class='link'>" . ($i + 1) . "</a></td>";
							  for($j = 0; $j < $sheet["column"]; $j++)
								echo "<td class='p-0'><input type='text' value='" . ((!empty($sheet[$i][$j]))? $sheet[$i][$j] : "") . "' onchange='saveTemporary($no, $i, $j, this.value)' /></td>";
						echo "</tr>";
				}
				echo "<tr>
				  <td><a href='?op=addRow' class='btn btn-primary'>Add Row</a></td>
				  <td></td>";
				  for($i = 0; $i < count($sheets); $i++){
					  echo "<td>
								<div>";
								if($no == $i)
								   echo "<a href='#' class='btn btn-secondary disabled'>Sheet" . ($i + 1) . "</a><br>";
								else
								   echo "<a href='?op=openSheet&sheet=" . $i . "' class='btn btn-secondary'>Sheet" . ($i + 1) . "</a><br>";
								echo "<a href='?op=delSheet&sheet=" . $i . "' class='btn btn-danger'>Delete Sheet</a><br>
								</div>
							  </td>";
				  }
				  echo "<td><a href='?op=addSheet' class='btn btn-primary'>Add Sheet</a></td>
				</tr>
			  </table>";
	}
	
	function addSheet(){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		
		$sheets[] = ["row" => 1, "column" => 1, [""]];
		
		return $sheets;
	}
	function delSheet($sheet){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(count($sheets) != 1)
			array_splice($sheets, $sheet, 1);
		
		return $sheets;
	}
	
	function addRow(){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		
		$rowCount = ++$sheets[$no]["row"];
		
		for($i = 0; $i < $sheets[$no]["column"]; $i++)
			$sheets[$no][$rowCount - 1][$i] = "";
		
		return $sheets;
	}
	function delRow($row){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		
		if($sheets[$no]["row"] != 1){
			$sheets[$no]["row"]--;
			array_splice($sheets[$no], $row + 2, 1);
		}
		
		return $sheets;
	}
	
	function addCol(){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		
		$columnCount = ++$sheets[$no]["column"];
		
		for($i = 0; $i < $sheets[$no]["row"]; $i++)
			$sheets[$no][$i][$columnCount - 1] = "";
		
		return $sheets;
	}
	function delCol($col){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		if($sheets[$no]["column"] != 1){
			$sheets[$no]["column"]--;
			
			for($i = 0; $i < count($sheets[$no]) - 2; $i++){
				if(!empty($sheets[$no][$i][$col])) array_splice($sheets[$no][$i], $col, 1);
			}
		}
		return $sheets;
	}
	
	function saveFile($name){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		if(isset($_SESSION["changes"]))
			$changes = $_SESSION["changes"];
		
		foreach($changes as $change){
			$s = $change["sheet"];
			$r = $change["row"];
			$c = $change["column"];
			$sheets[$s][$r][$c] = $change["data"];
		}
		
		$table = [];
		
		$file = fopen("files/" . $name . ".csv", "wb");
		
		for($i = 0; $i < count($sheets); $i++){
			$sheet = $sheets[$i];
			$table[] = ["row" => $sheet["row"], "column" => $sheet["column"]];
			
			$fields = ["sheet" . ($i + 1), $sheet["row"], $sheet["column"]];
			
			fputcsv($file, $fields);
			
			for($j = 0; $j < count($sheet) - 2; $j++)
				fputcsv($file, $sheet[$j]);
		}
		
		fclose($file);
		
		$_SESSION["table"] = $table;
		$_SESSION["changes"] = [];
		
		prepareSheet($sheets, $no, $name);
	}
	
	function deleteFile($name){
		unlink("files/$name");
	}
	
	function sortRow($row, $asc){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		if(isset($_SESSION["name"]))
			$name = $_SESSION["name"];
		
		if($asc)
			sort($sheets[$no][$row]);
		else
			rsort($sheets[$no][$row]);
		
		prepareSheet($sheets, $no, $name);
	}
	
	function sortCol($col, $asc){
		if(isset($_SESSION["sheets"]))
			$sheets = $_SESSION["sheets"];
		if(isset($_SESSION["no"]))
			$no = $_SESSION["no"];
		if(isset($_SESSION["name"]))
			$name = $_SESSION["name"];
		
		$column = [];
		
		for($i = 0; $i < count($sheets[$no]) - 2; $i++)
			if(!empty($sheets[$no][$i][$col])) $column[] = $sheets[$no][$i][$col];
		
		if($asc)
			sort($column);
		else
			rsort($column);
		
		for($i = 0; $i < count($sheets[$no]) - 2; $i++)
			if(!empty($sheets[$no][$i][$col])) $sheets[$no][$i][$col] = $column[$i];
		
		prepareSheet($sheets, $no, $name);
	}
		
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Excel App</title>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
		<style>
			table,tr,td{
				position: relative;
				border: solid 1px black;
			}
			a{
				width: 100%;
			}
			input{
				position: absolute;
				display: block;
				top: 0;
				left: 0;
				margin: 0;
				padding: 10px;
				height: 100%;
				width: 100%;
				border: none;
				box-sizing: border-box;
			}
		</style>
		<script>
			function checkBeforeBack(){
				var changed = false;
				
				var xhr = new XMLHttpRequest();
				xhr.open("GET", "save.php?op=check", true);
				xhr.onreadystatechange = function(){
					if(this.readyState == 4 && this.status == 200){
						changed = this.responseText;
						
						if(changed == "1")
							if(!confirm("Quit without saving?"))
								return ;
							
						location.href = "/";
					}
				};
				xhr.send();
					
			}
			
			function checkBeforeSave(name){
				
				if(name == ""){
					name = prompt("Enter a name for the file");
					
					if(name == null || name == "")
						return ;
				}
				
				location.href = "?op=save&name=" + name;
			}
			
			function saveTemporary(sheet, row, column, data){
				var xhr = new XMLHttpRequest();
				xhr.open("GET", "save.php?sheet=" + sheet + "&row=" + row + "&column=" + column + "&data=" + data, true);
				xhr.send();
			}
		</script>
	</head>
</html>