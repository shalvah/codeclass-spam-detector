<?php

$messages = [["Sale today!", "2837273"],
["Unique offer!", "3873827"],
["Only today and only for you!", "2837273"],
["Sale today!", "2837273"],
["Unique offer!", "3873827"]];
$messages2 = [["Check CodeClass out", "7284736"],
["Check CodeClass out", "7462832"],
["Check CodeClass out", "3625374"],
["Check CodeClass out", "7264762"]];
$spamSignals = ["sale", "discount", "offer"];

print_r(spamDetection($messages, $spamSignals));
echo "<br>";
print_r(spamDetection($messages2, $spamSignals));

function spamDetection(array $messages, array $spamSignals)
{
	return array(
		checkCriterionLessThanFiveWords($messages),
		checkCriterionSameUserSameContent($messages),
		checkCriterionSameContent($messages),
		checkCriterionWordsInSpamSignals($messages, $spamSignals)
		);
}

/**
 * Checks for the first criterion: do > 90% of messages have less than five words?
**/
function checkCriterionLessThanFiveWords(array $messages)
{
	if (count($messages)===0)  {
		//impossible to check criterion
		return "passed";
	} 

	$lessThanFive=0; //number of messages with <5 words
	foreach ($messages as $message) {
		if (str_word_count($message[0])<5) {
			$lessThanFive++;
		}
	}

	$percentage=$lessThanFive/count($messages);
	if($percentage<0.9) {
		return "passed";
	} else {
		$result=reducedFraction($lessThanFive, count($messages));
		return "failed: $result";
	}
}

/**
 * Reduces a fraction to lowest terms using the hcf of numerator and denominator
**/
function reducedFraction($numerator, $denominator)
{
	$hcf = hcf($numerator,$denominator);
	$numerator=$numerator/$hcf;
	$denominator=$denominator/$hcf;
	return "$numerator/$denominator";
}

/**
 * Obtains the highest common factor using Euclid's algorithm
**/
function hcf($a, $b)
{
	if( $a < $b)
		list($b,$a) = array($a,$b);
	if( $b == 0) 
		return $a;
	$r = $a % $b;
	while($r > 0) {
		$a = $b;
		$b = $r;
		$r = $a % $b;
	}
	return $b;
}

/**
 * Checks for the first criterion: do > 50% of messages to any single user have the same content?
**/
function checkCriterionSameUserSameContent(array $messages)
{
	if (count($messages)===0)  {
		//impossible to check criterion
		return "passed";
	} 

	$usersMessages=groupMessagesByUser($messages);
	$recipients=[];

	foreach ($usersMessages as $userId => $usersMessage) {
		if (checkSame($usersMessage, true)==="failed") {
			$recipients[]=$userId;
		}
	}
	if (count($recipients)==0) {
		return "passed";
	} else {
		$result=implode(" ", $recipients);	
		return "failed: $result";
	}
}

/**
 * Groups messages by user id, sorted in numeric order
**/
function groupMessagesByUser(array $messages) 
{
	$sorted=[];
	foreach ($messages as $key => $message) {
		$sorted[$message[1]][]=$message[0];
	}
	ksort($sorted, SORT_NUMERIC);
	return $sorted;
}

/**
 * Checks for the third criterion: do > 50% of all messages have the same content?
**/
function checkCriterionSameContent(array $messages)
{
	if (count($messages)===0)  {
		//impossible to check criterion
		return "passed";
	} 
	$messages=array_column($messages, 0);
	return checkSame($messages);
}

/**
 * Checks for the for messages that have the same content
**/
function checkSame(array $messages, $perUser=false)
{
	$repeated=0;
	$repeatedMessages=[];
	$frequencies=array_count_values($messages);
	$length=count($messages);
	if ($length<2) {
		return "passed";
	}
	foreach ($frequencies as $message => $frequency) {
		if ($frequency > 1 && ($frequency/$length) >0.5) {
			$repeated++;
			$repeatedMessages[]=$message;
		}
	}
	if ($repeated==0) {
		return "passed";
	} else {
		if (!$perUser) {
			$result=implode(",", $repeatedMessages);
			return "failed: $result";
		}
		else {
			return "failed";
		}
		
	}
}

/**
 * Checks for the fourth criterion: do > 50% of messages contain at least one of the spam signals?
**/
function checkCriterionWordsInSpamSignals(array $messages, array $spamSignals)
{
	if (count($messages)===0)  {
		//impossible to check criterion
		return "passed";
	} 

	//tracks which messages have the signals
	$present= array_fill(0, count($messages), false);

	//tracks which signals have appeared
	$words=[];

	foreach ($messages as $messageIndex => $message) {
		foreach ($spamSignals as $signal) {
			//if the signal appears
			if(stripos($message[0], $signal) !== false) {
				//record 
				$present[$messageIndex]=true;
				$words[]=$signal;
			}
		}
	}

	$present=array_filter($present, function($item) {
		return $item===true;
	});

	$percentage=(count($present)/count($messages));
	if($percentage<0.5) {
		return "passed";
	}
	else {
		//remove duplicates
		$words=array_unique($words);

		//sort signals alphabetically
		sort($words);

		//collapse into a string
		$words=implode(" ", $words);
		return "failed: $words";
	}
}
