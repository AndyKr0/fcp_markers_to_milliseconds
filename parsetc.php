<?php
// parsetc - by Andy Kroner
//
// Converts Final Cut Pro 7 marker list export from SMPTE timecode values
// to milliseconds and displays results.


// For debugging
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);


// Splits our input at newline
function parseToLines($inputText)
{
	$splitLinesArray = explode("\n", $inputText);
	return $splitLinesArray;
}


//Splits each line at <tabs>. Returns the the two columns needed for our calculations.
function parseToTabs($textLines)
{
	$splitTabsArray = explode("\t", $textLines);
	$nameAndTimeArray = array('name' => $splitTabsArray[2], 'timecode' => $splitTabsArray[4], 'properName' => $splitTabsArray[2]);
	return $nameAndTimeArray;
}


function parseFile($inFile)
{
	$linesArray = parseToLines($inFile);
	for ($i = 0; $i < count($linesArray) - 1; $i++)
	{
		$splitTabsArray = explode("\t", $linesArray[$i]);
		$nameAndTimeArray[$i] = array('name' => $splitTabsArray[2], 'timecode' => $splitTabsArray[4], 'properName' => $splitTabsArray[2]);
	}

return $nameAndTimeArray;

}


//Returns an array with timecode data.
function splitTimecode($timecode)
{
	global $isDrop;

	$hh = (int)substr($timecode, 0, 2);
	$mm = (int)substr($timecode, 3, 5);
	$ss = (int)substr($timecode, 6, 8);
	$ff = (int)substr($timecode, 9, 11);

	if (substr($timecode, 8, 1) === ';' and $isDrop === False)
	{
		echo "<br>Error: Dropframe mismatch! Check settings.<br>";
	}
	if (substr($timecode, 8, 1) === ':' and $isDrop === True)
	{
		echo "<br>Error: Dropframe mismatch! Check settings.<br>";
	}

	$timecodeArray = array("hh" => $hh, "mm" => $mm, "ss" => $ss, "ff" => $ff);

	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected'
			and $timecodeArray["hh"] > 0)
		{
			$timecodeArray["hh"] = (int)$timecodeArray["hh"] - 1;

		}
	return $timecodeArray;

}


// Calculates the number of frames from a given timecode value + framerate and dropframe state.
function getFrameCount($timecode)
{
	global $projectFps, $isDrop;
	$framesDropped = 0;
	$localTimecode = splitTimecode($timecode);
	$totalMinutes = 60 * $localTimecode["hh"] + $localTimecode["mm"];

	if ($projectFps === 29.97)
	{
		$functionFps = 30;
	}
	else if ($projectFps === 23.976)
	{
		$functionFps = 24;
	}
	else
	{
		$functionFps = $projectFps;
	}

	if ($isDrop === True)
	{
		$framesDropped = 2 * ($totalMinutes - floor(($totalMinutes/10)));
	}
	$framesInSeconds = $functionFps * $localTimecode["ss"];

	$framesInMinutes = 60 * $functionFps * $localTimecode["mm"];
	$framesInHours = 60 * 60 * $functionFps * $localTimecode["hh"];
	$frameCount = $localTimecode["ff"] + $framesInSeconds + $framesInMinutes + $framesInHours - (int)$framesDropped;
	return $frameCount;
}


// Calculates a formated timecode input to a millisecond value.
function getMilliseconds($timecode)
{
	global $isDrop, $projectFps;
	$seconds = getFrameCount($timecode) * (1.0/$projectFps);
	return round($seconds, 3) * 1000; // our value in milliseconds
}



// Returns a string with framerate settings used
function printFramerateSettings()
{
	global $isDrop, $projectFps;
	if ($projectFps === 29.97 and $isDrop === True)
	{
		$localFpsSettings = "29.97 Drop Frame";
	}
	else if ($projectFps === 29.97 and $isDrop === False)
	{
		$localFpsSettings = "29.97 Non-Drop Frame";
	}
	else
	{
		$localFpsSettings = (string)$projectFps;
	}
	return $localFpsSettings;
}


function getRadioFps($x)
{
	global $isDrop, $projectFps;
	if ($x = '29.97df')
	{
		$isDrop = True;
		$projectFps = 29.97;
	}
	else
	{
		$isDrop = False;
		$projectFps = (float)$x;
	}
}


function printTable($array)
{
	echo "<div><p><h2>Output</h2>";
	echo "<table border = '1' >";
	echo "<tr><th>Name</th><th>Milliseconds</th><th>Timecode</th><th>Frame Count</th></tr>";

	for ($i = 1; $i < count($array) - 1; $i ++) // 'count($array) - 1' because upload seems to be adding an extra \n. Look into this.
	{
		$y = parseToTabs($array[$i]);
		echo "<tr>";
		echo "<td>";
		echo $y['name'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getMilliseconds($y['timecode']);
		echo "</td>";
		echo "<td align= 'right' >";
		echo $y['timecode'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getFrameCount($y['timecode']);
		echo "</td>";
		echo "</tr>";
	}
	echo "</table></p></div>";
}



function printAdvancedTable($megaArray)
{
	echo "<div><p><h2>Advanced Output</h2>";
	echo "<table border = '1' >";
	echo "<tr><th>Name</th><th>Milliseconds";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "*";
	}
	echo "</th><th>Timecode</th><th>Frame Count</th></tr>";
	$sortedArray = $megaArray; //sortMegaArray($megaArray);
	//print_r($sortedArray);
	echo '<br>Input Lines = ' . count($sortedArray) . '<br>';
	for ($i = 1; $i < count($sortedArray); $i ++)
	{
		echo "<tr>";
		echo "<td>";
		echo $sortedArray[$i]['properName'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getMilliseconds($sortedArray[$i]['timecode']);
		echo "</td>";
		echo "<td align= 'right' >";
		echo $sortedArray[$i]['timecode'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getFrameCount($sortedArray[$i]['timecode']);
		echo "</td>";
		echo "</tr>";
	}

	echo "</table></p></div>";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "(* = 1 hour subtracted from Millseconds value)<br>";
	}

}

function printSimpleTable($megaArray)
{
	echo "<div><p><h2>Advanced Output</h2>";
	echo "<table border = '1' >";
	echo "<tr><th>Name</th><th>Milliseconds";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "*";
	}
	//echo "</th><th>Timecode</th><th>Frame Count</th>";
	echo "</tr>";
	$sortedArray = $megaArray; //sortMegaArray($megaArray);
	//print_r($sortedArray);
	echo '<br>Input Lines = ' . count($sortedArray) . '<br>';
	for ($i = 1; $i < count($sortedArray); $i ++)
	{
		echo "<tr>";
		echo "<td>";
		echo $sortedArray[$i]['properName'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getMilliseconds($sortedArray[$i]['timecode']);
		echo "</td>";
		echo "<td align= 'right' >";
		echo $sortedArray[$i]['timecode'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getFrameCount($sortedArray[$i]['timecode']);
		echo "</td>";
		echo "</tr>";
	}

	echo "</table></p></div>";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "(* = 1 hour subtracted from Millseconds value)<br>";
	}

}


function printSortedFields($fieldArray)
{
    echo "<div><p><h2>Field Advanced Output</h2>";
	echo "<table border = '1' >";
	echo "<tr><th>Name</th><th>Milliseconds";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "*";
	}
	echo "</th><th>Timecode</th><th>Frame Count</th></tr>";
	echo '<br>Input Lines = ' . count($fieldArray) . '<br>';
	for ($i = 0; $i < count($fieldArray); $i ++)
	{
		echo "<tr>";
		echo "<td>";
		echo $fieldArray[$i]['properName'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getMilliseconds($fieldArray[$i]['timecode']);
		echo "</td>";
		echo "<td align= 'right' >";
		echo $fieldArray[$i]['timecode'];
		echo "</td>";
		echo "<td align= 'right' >";
		echo getFrameCount($fieldArray[$i]['timecode']);
		echo "</td>";
		echo "</tr>";
	}

	echo "</table></p></div>";
	if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
	{
		echo "(* = 1 hour subtracted from Millseconds value)<br>";
	}
}



function printSummary()
{
		echo "<div><h5>Framerate: ";
		echo printFramerateSettings();
		echo "</h5></div><br>";
		echo
			"<footer>&copy;A. Kroner v0.9 03/22/2013</footer>";
}





echo '<html>
<head>
	<title>Convert Timecode to Milliseconds</title>
	<link rel="stylesheet" type="text/css" href="web.css" />
	<!-- By the way, this is a comment -->
</head>
<body>';

global $isDrop, $projectFps;
$radioPOST = $_POST['fps'];

if (isset($_POST['advanced_mode']) and $_POST['advanced_mode'] == 'advancedModeSelected')
{
	echo '<br>Advanced Mode<br>';
}
else
{
	echo '<br>Simple Mode<br>';
}

if (isset($_POST['offset_hour']) and $_POST['offset_hour'] == 'offsetHourSelected')
{
	echo '<br>Offset Hour<br>';
}
else
{
	echo '<br>No offset<br>';
}

if ($radioPOST === '29.97df')
	{
		$isDrop = True;
		$projectFps = 29.97;
	}
	else
	{
		$isDrop = False;
		$projectFps = (float)$_POST['fps'];
	}


$allowedExts = array("txt");
$extension_tmp = (explode(".", $_FILES["file"]["name"]));
$extension = end($extension_tmp);
if (($_FILES["file"]["type"] == "text/plain") 	&& ($_FILES["file"]["size"] < 20000) && in_array($extension, $allowedExts))
{
  	if ($_FILES["file"]["error"] > 0)
	{
		echo "Error: " . $_FILES["file"]["error"] . "<br>";
	}
	else
    {
    	echo "Upload: " . $_FILES["file"]["name"] . "<br>";
    	echo "Type: " . $_FILES["file"]["type"] . "<br>";
    	echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
    	echo "Stored in: " . $_FILES["file"]["tmp_name"];
    	$inputData = file_get_contents($_FILES["file"]["tmp_name"]);
    	$inputLines = parseToLines($inputData);
		//printTable($inputLines);
		echo '<br>';

		//parseFile($inputData);
	  printAdvancedTable(parseFile($inputData));
		//printSimpleTable(parseFile($inputData));
		//printSortedFields(parseFile($inputData));

    }
}
else
{
	echo "Invalid file. Please upload a valid file.";
}



unset($_POST);
unset($_FILES);
unset($inputData);


printSummary();
echo "</body>	</html>"
?>
