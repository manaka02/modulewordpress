<?php
	require_once('Util.php' );
		require_once('Paiement.php' );

		$target = new Util();

		$input =  'Toavina Ralambosoa';
		$key ='a64cb7a89423dcd193f40a442dd3fa10555eeb765cddd487be';
		$crypted = bin2Hex($target->crypter($key, $input));
		$response = $target->decrypter($key,hex2Bin($crypted));
		assertAreEqual($response, $input);
		

		$expected = "626";
		$input = "5412fc5c273d9d775c5c";
		assertAreEqualByDecrypt($expected,hex2Bin($input),$key);

		$expected = "wc_order_5a098b54893fb";
		$input = "80c9786cded6f1b7ec2f4c53b25c27ff75fa2ed33f40666344";
		assertAreEqualByDecrypt($expected,hex2Bin($input),$key);

		$expected = "Toavina Ralambosoa";
		$input = "f95d8a869afa9a055c273ef4740b92e4341e6cd781bc7f8ff9";
		assertAreEqualByDecrypt($expected,hex2Bin($input),$key);

		$expected = "PAI131111585969";
		$input = "80c9786cded6f1b75bdbb75bf465fe958f993cdc1390625c30";
		assertAreEqualByDecrypt($expected,hex2Bin($input),$key);


		$input = "wc_order_5a0ab7a3c7274";
		$expected = "80c9786cded6f1b75bdbb75bf465fe958f993cdc1390625c30";
		assertAreEqualByCrypt($expected, $input, $key);

		$input = "wc_order_5a0ab7a3c7274";
		$expected = "80c9786cded6f1b75bdbb75bf465fe958f993cdc1390625c30";
		assertAreEqualByCrypt($expected, $input, $key);

		$input = "647";
		$expected = "19ef8b5c30e6f7ee15";
		assertAreEqualByCrypt($expected, $input,"ec4f42b6d4bd00c12a3df903062c7ff6266aeab2a1363d51a8");

		$input = "9512";
		$expected = "f73d5c5ca54a982648";
		assertAreEqualByCrypt($expected, $input, $key);

		$input = "wc_order_5a098b54893fb";
		$expected = "80c9786cded6f1b7ec2f4c53b25c27ff75fa2ed33f40666344";
		assertAreEqualByCrypt($expected, $input, $key);




		// echo "ici : ".$target->decrypter($key, hex2bin('80c9786cded6f1b7ec2f4c53b227ff75fa2ed33f40666344'));
		
	function assertAreEqual($result, $source) {
		if ($result == $source)
			echo "[Pass] $result \n";
		else{
			echo "[Fail] '$result' est different de '$source'\n";
		}
	}

	


	function assertAreEqualByCrypt($result, $input, $key){
		$target = new Util();
		$output = bin2Hex($target->crypter($key, $input));
		if ($result == $output)
			echo "[Pass] $result \n";
		else{
			echo "[Fail] $input ---> '$output' est different de '$result'\n";
		}
	}

	function assertAreEqualByDecrypt($result,$input, $key){
		$output = decryptWithFixBug($key,$input);
		if ($result == $output)
			echo "[Pass] $result \n";
		else{
			echo "[Fail] '$output' est different de '$result'\n";
		}
	}

	function decryptWithFixBug($key, $input){
		$target = new Util();
		$output = $target->decrypter($key, $input);
		if(!$output){
			$fixedValue = fixBug($input);
			$output = $target->decrypter($key,$fixedValue);
		}
		return $output;
	}

	function fixBug($cryptedValue){
		$hexData = bin2Hex($cryptedValue);
		$tempvalue = $hexData;
		$tempvalue = str_replace('5c5c', "ZZ", $tempvalue); 
		$tempvalue = str_replace('5c30', "00", $tempvalue); 
		$tempvalue = str_replace('5c', "", $tempvalue); 
		$tempvalue = str_replace('ZZ', "5c", $tempvalue); 
		return hex2Bin($tempvalue);
	}

